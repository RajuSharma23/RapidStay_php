<?php
// Start session and perform all header-related operations first
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/booking-requests.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Initialize message variable
$message = "";

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Submit new booking request
    if (isset($_POST['submit_booking'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $listing_id = $_POST['room_id'];
        $move_in_date = $_POST['booking_date']; 
        $duration = isset($_POST['duration']) ? $_POST['duration'] : 1;
        $occupants = isset($_POST['occupants']) ? $_POST['occupants'] : 1;
        $message_text = $_POST['purpose']; 
        $total_amount = 0;
        
        // Get listing price
        $price_query = "SELECT price FROM listings WHERE id = ?";
        $stmt = $conn->prepare($price_query);
        $stmt->bind_param("i", $listing_id);
        $stmt->execute();
        $price_result = $stmt->get_result();
        
        if ($price_result && $price_row = $price_result->fetch_assoc()) {
            $total_amount = $price_row['price'] * $duration;
        }
        
        // Check if user exists in the database
        $check_user_sql = "SELECT id FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_user_sql);
        $check_stmt->bind_param("i", $_SESSION['user_id']);
        $check_stmt->execute();
        $user_result = $check_stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            $message = '<div class="alert alert-danger">Error: User account not found. Please contact support.</div>';
        } else {
            // User exists, proceed with booking
            $insert_sql = "INSERT INTO bookings (listing_id, user_id, move_in_date, duration, occupants, total_amount, message, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iisiids", $listing_id, $_SESSION['user_id'], $move_in_date, $duration, $occupants, $total_amount, $message_text);
            
            if ($insert_stmt->execute()) {
                $message = '<div class="alert alert-success">Booking request submitted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $insert_stmt->error . '</div>';
            }
        }
    }
    
    // Update booking status
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $booking_id = isset($_POST['approve']) ? $_POST['approve'] : $_POST['reject'];
        $status = isset($_POST['approve']) ? 'approved' : 'rejected';
        
        $sql = "UPDATE bookings SET status = '$status' WHERE id = $booking_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = '<div class="alert alert-success">Booking status updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating status: ' . $conn->error . '</div>';
        }
    }
}

// Fetch rooms for dropdown
$rooms_sql = "SELECT id, title as room_name FROM listings";
$rooms_result = $conn->query($rooms_sql);

// Fetch bookings for listing
$bookings_sql = "SELECT b.*, l.title as room_name, u.name as tenant_name 
                FROM bookings b 
                JOIN listings l ON b.listing_id = l.id
                JOIN users u ON b.user_id = u.id
                ORDER BY b.move_in_date DESC, b.created_at DESC";
$bookings_result = $conn->query($bookings_sql);

// Include header
include '../includes/owner_header.php';

// NOW we can start outputting HTML content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking Management</title>
    <style>
        
        
        
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin-left: 100px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 50px auto;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .nav-tabs .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        
        .nav-tabs .tab.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .tab-content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: #FF9800;
            font-weight: bold;
        }
        
        .status-approved {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #F44336;
            font-weight: bold;
        }
        
        .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
        }
        
        .approve-btn {
            background-color: #4CAF50;
        }
        
        .reject-btn {
            background-color: #F44336;
        }
        
        .view-btn {
            background-color: #2196F3;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                width: 95%;
                padding: 10px;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tabs .tab {
                border-radius: 0;
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body >
    
    <div class="container overflow-auto">
        <h1>Room Booking Management System</h1>
        
        <?php echo $message; ?>
        
        <div class="nav-tabs">
            <div class="tab active" onclick="openTab('booking-form')">New Booking</div>
            <div class="tab" onclick="openTab('booking-list')">Manage Bookings</div>
        </div>
        
        <div id="booking-form" class="tab-content">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="room_id">Room:</label>
                    <select class="form-control" id="room_id" name="room_id" required>
                        <option value="">Select a room</option>
                        <?php
                        if ($rooms_result->num_rows > 0) {
                            while($room = $rooms_result->fetch_assoc()) {
                                echo '<option value="' . $room["id"] . '">' . $room["room_name"] . '</option>';
                            }
                        } else {
                            echo '<option disabled>No rooms available</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="booking_date">Move-in Date:</label>
                    <input type="date" class="form-control" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="duration">Duration (Months):</label>
                    <select class="form-control" id="duration" name="duration" required>
                        <option value="1">1 Month</option>
                        <option value="3">3 Months</option>
                        <option value="6">6 Months</option>
                        <option value="12" selected>12 Months</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="occupants">Number of Occupants:</label>
                    <input type="number" class="form-control" id="occupants" name="occupants" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label for="purpose">Message:</label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="3"></textarea>
                </div>
                
                <button type="submit" name="submit_booking" class="btn">Submit Booking Request</button>
            </form>
        </div>
        
        <div id="booking-list" class="tab-content" style="display: none;">
            <h2>Booking Requests</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant</th>
                        <th>Property</th>
                        <th>Move-in Date</th>
                        <th>Duration</th>
                        <th>Occupants</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($bookings_result->num_rows > 0) {
                        while($booking = $bookings_result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $booking["id"] . '</td>';
                            echo '<td>' . $booking["tenant_name"] . '</td>';
                            echo '<td>' . $booking["room_name"] . '</td>';
                            echo '<td>' . date('d-m-Y', strtotime($booking["move_in_date"])) . '</td>';
                            echo '<td>' . $booking["duration"] . ' months</td>';
                            echo '<td>' . $booking["occupants"] . '</td>';
                            echo '<td>₹' . number_format($booking["total_amount"], 2) . '</td>';
                            echo '<td class="status-' . $booking["status"] . '">' . ucfirst($booking["status"]) . '</td>';
                            echo '<td>';
                            if ($booking["status"] == 'pending') {
                                echo '<form method="post" style="display:inline;">';
                                echo '<button type="submit" name="approve" value="' . $booking["id"] . '" class="action-btn approve-btn">Approve</button>';
                                echo '</form>';
                                echo '<form method="post" style="display:inline;">';
                                echo '<button type="submit" name="reject" value="' . $booking["id"] . '" class="action-btn reject-btn">Reject</button>';
                                echo '</form>';
                            }
                            echo '<button class="action-btn view-btn" onclick="viewDetails(' . $booking["id"] . ')">View</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center;">No booking requests found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            var i, tabContent, tabLinks;
            
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
            }
            
            tabLinks = document.getElementsByClassName("tab");
            for (i = 0; i < tabLinks.length; i++) {
                tabLinks[i].className = tabLinks[i].className.replace(" active", "");
            }
            
            document.getElementById(tabName).style.display = "block";
            event.currentTarget.className += " active";
        }
        
        function viewDetails(bookingId) {
            // This function would typically show a modal with booking details
            alert("View booking details for ID: " + bookingId);
            // In a real application, you would fetch the details via AJAX and display them
        }
        
        // Validate end time is after start time
        document.getElementById('end_time').addEventListener('change', function() {
            var startTime = document.getElementById('start_time').value;
            var endTime = this.value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert("End time must be after start time");
                this.value = '';
            }
        });
    </script>
</body>
</html>