<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$staff_id = $_SESSION['staff_id'] ?? null;
$conn->select_db("hotel_management_db");

// Get URL parameters for specific customer/booking
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
$booking_id_param = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

// Handle check-in/check-out API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $booking_id = isset($_POST['booking_id']) && is_numeric($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    
    if (!$booking_id || !$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing booking_id or action.']);
        exit;
    }
    
    // Verify staff is logged in
    if (!$staff_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied. Staff login required.']);
        exit;
    }
    
    // Fetch booking
    $stmt = $conn->prepare("SELECT booking_id, booking_status FROM bookings WHERE booking_id = ? LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }
    
    // Handle check-in
    if ($action === 'checkin') {
        if ($booking['booking_status'] !== 'booked') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be in booked status to check in.']);
            exit;
        }
        
        // Fetch the booking details including room_type_id
        $bookingDetails = $conn->prepare("
            SELECT b.booking_id, b.room_id, b.check_in_date, b.check_out_date, b.room_type_id 
            FROM bookings b 
            WHERE b.booking_id = ? LIMIT 1
        ");
        
        if (!$bookingDetails) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $bookingDetails->bind_param("i", $booking_id);
        $bookingDetails->execute();
        $bookingResult = $bookingDetails->get_result();
        $bookingData = $bookingResult->fetch_assoc();
        $bookingDetails->close();
        
        if (!$bookingData) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not fetch booking details.']);
            exit;
        }
        
        $room_type_id = $bookingData['room_type_id'];
        $check_in = $bookingData['check_in_date'];
        $check_out = $bookingData['check_out_date'];
        
        // Find an available room of the same type that is not occupied during the stay dates
        // Only 'checked_in' bookings block a room since 'booked' ones don't have a real room yet
        $availableRoom = $conn->prepare("
            SELECT r.room_id, r.room_number 
            FROM rooms r 
            WHERE r.room_type_id = ? 
            AND r.room_id NOT IN (
                SELECT b.room_id 
                FROM bookings b 
                WHERE b.booking_status = 'checked_in'
                AND b.check_in_date < ? 
                AND b.check_out_date > ?
            )
            ORDER BY r.room_id
            LIMIT 1
        ");
        
        if (!$availableRoom) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $availableRoom->bind_param("iss", $room_type_id, $check_out, $check_in);
        $availableRoom->execute();
        $roomResult = $availableRoom->get_result();
        $room = $roomResult->fetch_assoc();
        $availableRoom->close();
        
        // If no available room found, send error
        if (!$room) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'The booked room type is not currently available. Please contact management or find an alternative room type.']);
            exit;
        }
        
        // Update booking with the assigned room and check-in status
        $update = $conn->prepare("UPDATE bookings SET room_id = ?, booking_status='checked_in', actual_check_in = NOW() WHERE booking_id = ?");
        $update->bind_param("ii", $room['room_id'], $booking_id);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked in successfully. Room assigned: #' . $room['room_number'], 'booking_id' => $booking_id, 'room_number' => $room['room_number']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();
    }
    // Handle check-out
    elseif ($action === 'checkout') {
        if ($booking['booking_status'] !== 'checked_in') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be checked in to check out.']);
            exit;
        }
        
        $update = $conn->prepare("UPDATE bookings SET booking_status='checked_out' WHERE booking_id = ?");
        $update->bind_param("i", $booking_id);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked out successfully.', 'booking_id' => $booking_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Check-in - The Bee Hotel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
    :root { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-white shadow-lg sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
      <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel</h1>
      <nav class="space-x-4 flex items-center">
        <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('admin_dashboard.php')">
          &larr; Back to Dashboard
        </button>
        <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
          Log Out
        </button>
      </nav>
    </div>
  </header>

  <main class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <h2 class="text-4xl font-bold text-gray-900 mb-2">
      Check-in Management
      <?php if ($customer_id && $booking_id_param && isset($customer_name)): ?>
        <span class="text-amber-600 text-2xl font-semibold"> - <?php echo htmlspecialchars($customer_name); ?></span>
      <?php endif; ?>
    </h2>
    <p class="text-gray-600 text-lg mb-8">
      <?php if ($customer_id && $booking_id_param): ?>
        Check in or check out this guest.
      <?php else: ?>
        Check in guests on their behalf.
      <?php endif; ?>
    </p>

    <?php if (!$staff_id): ?>
      <div class="bg-white p-8 rounded-2xl shadow-xl border-t-4 border-amber-500">
        <p class="text-lg text-gray-700 mb-4">Admin access required. Please log in.</p>
        <a href="stafflogin.php" class="inline-block px-6 py-3 bg-amber-500 text-gray-900 font-semibold rounded-lg hover:bg-amber-600 transition">Log In</a>
      </div>
    <?php else: ?>
      <?php
        // If customer_id and booking_id are provided, fetch only that specific booking
        if ($customer_id && $booking_id_param) {
          $stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, c.first_name, c.last_name, r.room_number FROM bookings b JOIN customers c ON b.customer_id = c.customer_id JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_id = ? AND c.customer_id = ? LIMIT 1");
          $stmt->bind_param("ii", $booking_id_param, $customer_id);
        } else {
          // Otherwise, fetch all bookings that are booked or checked_in
          $stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, c.first_name, c.last_name, r.room_number FROM bookings b JOIN customers c ON b.customer_id = c.customer_id JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_status IN ('booked', 'checked_in') ORDER BY b.check_in_date ASC");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $bookings = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get customer info if specific customer selected
        $customer_name = '';
        if ($customer_id && $booking_id_param) {
          $cust_stmt = $conn->prepare("SELECT first_name, last_name FROM customers WHERE customer_id = ? LIMIT 1");
          $cust_stmt->bind_param("i", $customer_id);
          $cust_stmt->execute();
          $cust_res = $cust_stmt->get_result();
          if ($cust_row = $cust_res->fetch_assoc()) {
            $customer_name = $cust_row['first_name'] . ' ' . $cust_row['last_name'];
          }
          $cust_stmt->close();
        }
      ?>

      <div class="bg-white p-8 rounded-2xl shadow-xl border-t-4 border-amber-500">
        <?php if (empty($bookings)): ?>
          <p class="text-gray-700 text-lg">No pending bookings to check in.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="border-b-2 border-gray-300">
                <tr>
                  <th class="py-3 px-2 font-bold">Booking ID</th>
                  <th class="py-3 px-2 font-bold">Customer Name</th>
                  <th class="py-3 px-2 font-bold">Room</th>
                  <th class="py-3 px-2 font-bold">Check-in Date</th>
                  <th class="py-3 px-2 font-bold">Check-out Date</th>
                  <th class="py-3 px-2 font-bold">Status</th>
                  <th class="py-3 px-2 font-bold">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookings as $b): ?>
                  <tr class="border-b hover:bg-gray-50 transition">
                    <td class="py-3 px-2 font-semibold">#<?= htmlspecialchars($b['booking_id']) ?></td>
                    <td class="py-3 px-2"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                    <td class="py-3 px-2"><?= htmlspecialchars($b['room_number']) ?></td>
                    <td class="py-3 px-2"><?= htmlspecialchars($b['check_in_date']) ?></td>
                    <td class="py-3 px-2"><?= htmlspecialchars($b['check_out_date']) ?></td>
                    <td class="py-3 px-2">
                      <span id="status-<?= $b['booking_id'] ?>" class="px-3 py-1 rounded-full text-xs font-semibold 
                        <?php 
                          if ($b['booking_status'] === 'checked_in') echo 'bg-green-200 text-green-800';
                          elseif ($b['booking_status'] === 'booked') echo 'bg-blue-200 text-blue-800';
                          else echo 'bg-gray-200 text-gray-800';
                        ?>
                      ">
                        <?php 
                          $status_text = [
                            'booked' => 'Booked',
                            'checked_in' => 'Checked In',
                            'checked_out' => 'Checked Out'
                          ];
                          echo $status_text[$b['booking_status']] ?? ucfirst($b['booking_status']);
                        ?>
                      </span>
                    </td>
                    <td class="py-3 px-2">
                      <?php if ($b['booking_status'] === 'booked'): ?>
                        <button onclick="adminCheckIn(<?= $b['booking_id'] ?>, this)" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                          Check In
                        </button>
                      <?php elseif ($b['booking_status'] === 'checked_in'): ?>
                        <button onclick="adminCheckOut(<?= $b['booking_id'] ?>, this)" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                          Check Out
                        </button>
                      <?php else: ?>
                        <span class="text-gray-500 text-sm">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Check-in Success Modal -->
  <div id="checkinModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center mx-4">
      <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <h3 class="text-2xl font-bold text-gray-900 mb-2">Checked In!</h3>
      <p id="checkinModalText" class="text-gray-600 mb-6"></p>
      <button onclick="location.reload()" class="w-full px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
        Close
      </button>
    </div>
  </div>

  <!-- Check-out Success Modal -->
  <div id="checkoutModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center mx-4">
      <svg class="w-16 h-16 text-blue-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <h3 class="text-2xl font-bold text-gray-900 mb-2">Checked Out!</h3>
      <p id="checkoutModalText" class="text-gray-600 mb-6"></p>
      <button onclick="location.reload()" class="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
        Close
      </button>
    </div>
  </div>

  <script>
    function goToPage(filename) {
      window.location.href = filename;
    }
    function handleLogout() {
      window.location.href = 'stafflogin.php';
    }

    async function adminCheckIn(bookingId, btn) {
      if (!confirm('Check in booking #' + bookingId + ' on behalf of customer?')) return;

      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = 'Checking in...';

      try {
        const form = new FormData();
        form.append('booking_id', bookingId);
        form.append('action', 'checkin');

        const res = await fetch('admin_checkin.php', { method: 'POST', body: form });
        const data = await res.json();

        if (res.ok && data.success) {
          document.getElementById('status-' + bookingId).textContent = 'Checked In';
          document.getElementById('status-' + bookingId).className = 'px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800';
          document.getElementById('checkinModalText').textContent = 'Booking #' + bookingId + ' checked in successfully.';
          document.getElementById('checkinModal').classList.remove('hidden');
          btn.remove();
        } else {
          alert(data.message || 'Check-in failed.');
          btn.disabled = false;
          btn.textContent = originalText;
        }
      } catch (err) {
        console.error(err);
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
      }
    }

    async function adminCheckOut(bookingId, btn) {
      if (!confirm('Check out booking #' + bookingId + ' on behalf of customer?')) return;

      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = 'Checking out...';

      try {
        const form = new FormData();
        form.append('booking_id', bookingId);
        form.append('action', 'checkout');

        const res = await fetch('admin_checkin.php', { method: 'POST', body: form });
        const data = await res.json();

        if (res.ok && data.success) {
          document.getElementById('status-' + bookingId).textContent = 'Checked Out';
          document.getElementById('status-' + bookingId).className = 'px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800';
          document.getElementById('checkoutModalText').textContent = 'Booking #' + bookingId + ' checked out successfully.';
          document.getElementById('checkoutModal').classList.remove('hidden');
          btn.remove();
        } else {
          alert(data.message || 'Check-out failed.');
          btn.disabled = false;
          btn.textContent = originalText;
        }
      } catch (err) {
        console.error(err);
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
      }
    }
  </script>
</body>
</html>
?>
