<?php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Function to get booking details
function getBookingDetails($conn, $booking_id = null) {
    // Base query with joins to get user and listing information
    $sql = "SELECT b.*, 
                  l.title as property_name, l.price as property_price, l.address as location,
                  u.name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
           FROM bookings b
           LEFT JOIN listings l ON b.listing_id = l.id
           LEFT JOIN users u ON b.user_id = u.id";
    
    // If specific booking requested
    if ($booking_id) {
        $sql .= " WHERE b.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
    } else {
        // For owner, only show bookings for their listings
        if ($_SESSION['user_type'] == 'owner') {
            // First get the owner's listings
            $listings_sql = "SELECT id FROM listings WHERE user_id = ?";
            $listings_stmt = $conn->prepare($listings_sql);
            $listings_stmt->bind_param("i", $_SESSION['user_id']);
            $listings_stmt->execute();
            $listings_result = $listings_stmt->get_result();
            
            if ($listings_result->num_rows > 0) {
                $listing_ids = [];
                while ($listing = $listings_result->fetch_assoc()) {
                    $listing_ids[] = $listing['id'];
                }
                
                // Now get bookings for these listings
                $sql .= " WHERE b.listing_id IN (" . implode(',', $listing_ids) . ")";
                $sql .= " ORDER BY b.created_at DESC";
                $stmt = $conn->prepare($sql);
            } else {
                // No listings, so no bookings
                return [];
            }
        } else {
            // For admin, show all bookings
            $sql .= " ORDER BY b.created_at DESC";
            $stmt = $conn->prepare($sql);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we have results
    if ($result->num_rows > 0) {
        if ($booking_id) {
            // Return single booking if specific ID requested
            return $result->fetch_assoc();
        } else {
            // Return all bookings
            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
            return $bookings;
        }
    } else {
        return null;
    }
}

// Get booking status counts
function getBookingStatusCounts($conn) {
    $user_id = $_SESSION['user_id'];
    $counts = [
        'total' => 0,
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'completed' => 0
    ];
    
    // Get owner's listings
    $listings_sql = "SELECT id FROM listings WHERE user_id = ?";
    $listings_stmt = $conn->prepare($listings_sql);
    $listings_stmt->bind_param("i", $user_id);
    $listings_stmt->execute();
    $listings_result = $listings_stmt->get_result();
    
    if ($listings_result->num_rows > 0) {
        $listing_ids = [];
        while ($listing = $listings_result->fetch_assoc()) {
            $listing_ids[] = $listing['id'];
        }
        
        // Get counts by status
        $sql = "SELECT status, COUNT(*) as count FROM bookings 
                WHERE listing_id IN (" . implode(',', $listing_ids) . ")
                GROUP BY status";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = $row['count'];
                $counts['total'] += $row['count'];
            }
        }
    }
    
    return $counts;
}

// Process URL parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get booking details
if (isset($_GET['id'])) {
    $booking = getBookingDetails($conn, $_GET['id']);
} else {
    // Get all bookings matching filters
    $bookings = getBookingDetails($conn);
    
    // Apply status filter
    if ($filter_status !== 'all' && !empty($bookings)) {
        $bookings = array_filter($bookings, function($booking) use ($filter_status) {
            return $booking['status'] === $filter_status;
        });
    }
    
    // Apply search filter
    if (!empty($search_term) && !empty($bookings)) {
        $bookings = array_filter($bookings, function($booking) use ($search_term) {
            return (
                strpos(strtolower($booking['property_name']), strtolower($search_term)) !== false ||
                strpos(strtolower($booking['tenant_name']), strtolower($search_term)) !== false ||
                strpos((string)$booking['id'], $search_term) !== false
            );
        });
    }
}

// Get status counts for summary boxes
$status_counts = getBookingStatusCounts($conn);

// Include header
include '../includes/owner_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <style>
        .container {
            max-width: 1200px;
            margin-left: 250px;
            margin-top: 40px;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            border-top: 4px solid #4c57ef;
        }
        
        .summary-container {
            margin-bottom: 30px;
        }
        
        .summary-box-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .summary-box {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            min-width: 150px;
            flex: 1;
        }
        
        .summary-title {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .total-box { background-color: #F0F9FF; border-top: 4px solid #0284C7; }
        .pending-box { background-color: #FEF3C7; border-top: 4px solid #D97706; }
        .confirmed-box { background-color: #DCFCE7; border-top: 4px solid #16A34A; }
        .cancelled-box { background-color: #FEE2E2; border-top: 4px solid #DC2626; }
        .completed-box { background-color: #F1F5F9; border-top: 4px solid #475569; }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-label {
            margin-right: 8px;
            color: #64748b;
        }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
        }
        
        .search-input {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
            min-width: 200px;
        }
        
        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .booking-table th,
        .booking-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .booking-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }
        
        .booking-table tr:hover {
            background-color: #f8fafc;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-confirmed { background-color: #DCFCE7; color: #15803D; }
        .status-cancelled { background-color: #FEE2E2; color: #B91C1C; }
        .status-completed { background-color: #F1F5F9; color: #334155; }
        
        .booking-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            text-decoration: none;
            text-align: center;
        }
        
        .view-btn { background-color: #3b82f6; }
        .edit-btn { background-color: #10b981; }
        .delete-btn { background-color: #ef4444; }
        
        .booking-detail {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .detail-section {
            margin-bottom: 20px;
        }
        
        .detail-title {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-size: 14px;
            color: #64748b;
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .back-btn {
            padding: 8px 15px;
            background-color: #f1f5f9;
            color: #475569;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($booking)): ?>
            <!-- Single booking view -->
            <div class="detail-header">
                <h1>Booking #<?php echo htmlspecialchars($booking['id']); ?></h1>
                <span class="status-badge status-<?php echo $booking['status']; ?>">
                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                </span>
            </div>
            
            <div class="booking-detail">
                <div class="detail-section">
                    <div class="detail-title">Property Details</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Property</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['property_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Price</div>
                            <div class="detail-value">$<?php echo number_format($booking['property_price'], 2); ?> per month</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Booking Information</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Move-in Date</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['move_in_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['duration']); ?> months</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Occupants</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['occupants']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">₹<?php echo number_format($booking['total_amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Tenant Information</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['tenant_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['tenant_email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['tenant_phone']); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($booking['message'])): ?>
                <div class="detail-section">
                    <div class="detail-title">Message from Tenant</div>
                    <p><?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="booking-actions" style="margin-top: 20px;">
                    <?php if ($booking['status'] === 'pending'): ?>
                    <form method="post" action="update-booking-status.php">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="action-btn edit-btn">Confirm Booking</button>
                    </form>
                    
                    <form method="post" action="update-booking-status.php">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="action-btn delete-btn">Cancel Booking</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="booking-details.php" class="back-btn">Back to All Bookings</a>
            
        <?php else: ?>
            <!-- Bookings list view -->
            <h1>Booking Management</h1>
            
            <!-- Summary boxes -->
            <div class="summary-container">
                <div class="summary-box-container">
                    <div class="summary-box total-box">
                        <div class="summary-title">Total Bookings</div>
                        <div class="summary-value"><?php echo $status_counts['total']; ?></div>
                    </div>
                    
                    <div class="summary-box pending-box">
                        <div class="summary-title">Pending</div>
                        <div class="summary-value"><?php echo $status_counts['pending']; ?></div>
                    </div>
                    
                    <div class="summary-box confirmed-box">
                        <div class="summary-title">Confirmed</div>
                        <div class="summary-value"><?php echo $status_counts['confirmed']; ?></div>
                    </div>
                    
                    <div class="summary-box completed-box">
                        <div class="summary-title">Completed</div>
                        <div class="summary-value"><?php echo $status_counts['completed']; ?></div>
                    </div>
                    
                    <div class="summary-box cancelled-box">
                        <div class="summary-title">Cancelled</div>
                        <div class="summary-value"><?php echo $status_counts['cancelled']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form action="" method="get" class="filter-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label class="filter-label" for="status">Status:</label>
                        <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="flex-grow: 1;">
                        <input type="text" name="search" placeholder="Search by ID, property or tenant..." 
                               class="search-input" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" style="padding: 8px 15px; background: #4c57ef; color: white; border: none; border-radius: 5px; margin-left: 5px;">Search</button>
                        <?php if (!empty($search_term) || $filter_status !== 'all'): ?>
                        <a href="booking-details.php" style="padding: 8px 15px; background: #f1f5f9; color: #475569; border-radius: 5px; text-decoration: none; margin-left: 5px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($bookings)): ?>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Move-in Date</th>
                            <th>Duration</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['property_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?></td>
                            <td><?php echo $booking['duration']; ?> months</td>
                            <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td class="booking-actions">
                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="action-btn view-btn">View</a>
                                
                                <?php if ($booking['status'] === 'pending'): ?>
                                <a href="update-booking-status.php?id=<?php echo $booking['id']; ?>&status=confirmed" 
                                   class="action-btn edit-btn">Confirm</a>
                                <a href="update-booking-status.php?id=<?php echo $booking['id']; ?>&status=cancelled" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #64748b;">
                    <i style="font-size: 48px; color: #e2e8f0; margin-bottom: 20px;">📅</i>
                    <h3>No bookings found</h3>
                    <p>There are no bookings matching your search criteria.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>