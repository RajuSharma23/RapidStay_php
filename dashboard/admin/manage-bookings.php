<?php
// Start session
session_start();

// Include database connection and access control
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/manage-bookings.php");
    exit();
}

// Set default values for filtering and pagination
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Build SQL query
$sql = "SELECT b.*, u.name as user_name, l.title as listing_title
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN listings l ON b.listing_id = l.id
        WHERE 1=1";

// Add search condition if search term is provided
if (!empty($searchTerm)) {
    $sql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%' OR u.name LIKE '%$searchTerm%')";
}

// Add status filter if not 'all'
if ($filterStatus !== 'all') {
    $sql .= " AND b.status = '$filterStatus'";
}

// Add order by clause
$sql .= " ORDER BY b.created_at DESC";

// Get total records for pagination
$countSql = "SELECT COUNT(*) as total FROM bookings b LEFT JOIN users u ON b.user_id = u.id WHERE 1=1";

// Add the same search and filter conditions
if (!empty($searchTerm)) {
    $countSql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%' OR u.name LIKE '%$searchTerm%')";
}

if ($filterStatus !== 'all') {
    $countSql .= " AND b.status = '$filterStatus'";
}

// Execute count query
$totalResult = $conn->query($countSql);
$totalRecords = 0;
if ($totalResult && $totalRow = $totalResult->fetch_assoc()) {
    $totalRecords = $totalRow['total'];
}
$totalPages = ceil($totalRecords / $recordsPerPage);

// Add limit for pagination
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute query
$result = $conn->query($sql);

// Include header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
    <link rel="stylesheet" href="../../assets/css/managebook.css">
    <!-- CSS styling similar to owner booking-history.php -->
</head>
<body>
    <div class="container">
        <div class="contener-item">
            <div class="page-header">
                <h1>Booking Management</h1>
                <p>Admin Control Panel - Full Booking Management</p>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-container">
                <!-- Similar to owner booking-history.php -->
            </div>
            
            <!-- Bookings Table -->
            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Listing</th>
                                <th>Check-in</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo $row['listing_title']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['move_in_date'])); ?></td>
                                    <td><?php echo $row['duration'] . ' days'; ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_booking.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">View</a>
                                        <a href="edit_booking.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Edit</a>
                                        <a href="delete_booking.php?id=<?php echo $row['id']; ?>" 
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Are you sure you want to permanently delete this booking? This action cannot be undone.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <!-- Pagination links -->
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i>📅</i>
                        <h3>No booking records found</h3>
                        <p>No booking history matches your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>