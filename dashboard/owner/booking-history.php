<?php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/booking-history.php");
    exit();
}

// Set default values for filtering and pagination
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Build SQL query using the CORRECT column names from your database
$sql = "SELECT b.id, b.listing_id, b.user_id, b.move_in_date, 
               b.duration, b.occupants, b.total_amount, b.status,
               b.payment_status, b.created_at
        FROM bookings b
        WHERE 1=1";

// Add search condition if search term is provided
if (!empty($searchTerm)) {
    $sql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%')";
}

// Add status filter if not 'all'
if ($filterStatus !== 'all') {
    $sql .= " AND b.status = '$filterStatus'";
}

// Add order by clause
$sql .= " ORDER BY b.created_at DESC";

// Get total records for pagination - FIX THE COUNT QUERY
$countSql = "SELECT COUNT(*) as total FROM bookings b WHERE 1=1";

// Add the same search and filter conditions
if (!empty($searchTerm)) {
    $countSql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%')";
}

if ($filterStatus !== 'all') {
    $countSql .= " AND b.status = '$filterStatus'";
}

// Execute count query
$totalResult = $conn->query($countSql);

// Add error checking before accessing the total
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
include '../includes/owner_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>
    <style>
        * {
            margin: 0;
            padding: 0;
           
        }
        
        body {
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 95%;
            max-width: 1200px;
            
            padding: 20px;
            margin-left: 200px;
        }
        .contener-item{
            
            margin-top: 50px;
            
        }
        
        .page-header {
            /* display: flex; */
            /* justify-content: space-between; */
            /* align-items: center; */
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .filter-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .form-group {
            flex-grow: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #4a6fdc;
            color: #fff;
        }
        
        .btn-primary:hover {
            background-color: #3a5bb9;
        }
        
        .btn-reset {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
        }
        
        .btn-reset:hover {
            background-color: #e9ecef;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        thead {
            background-color: #f8f9fa;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 90px;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            text-decoration: none;
        }
        
        .view-btn {
            background-color: #28a745;
            color: white;
        }
        
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .delete-btn {
            background-color: #6c757d;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            text-decoration: none;
            color: #4a6fdc;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        
        .pagination a:hover, .pagination a.active {
            background-color: #4a6fdc;
            color: #fff;
            border-color: #4a6fdc;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .no-data i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        h1{
            /* color: #4a6fdc; */
            font-size: 24px;
            /* margin-bottom: 20px; */
            font-weight: 700;
            

        }
        p{
            font-size: 18px;
            color: #666;
        }
        
        .summary-container {
            display: flex;
            flex-direction: column;
            flex-wrap: wrap;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            gap: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .summary-box-conteiner {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .summary-box {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
            border-top:4px solid #4c57ef;
            
        }
        
        .summary-title {
            
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
        }
        .Total-Bookings{
            background-color: #EFF6FF;
        }
        .Total-Bookings-text{
            color: #1D4ED8;
            
        }
        .Confirmed{
            background-color: #ECFDF5;
        }
        .Confirmed-text{
            color: #047857;
            
        }
        .Pending{
            background-color: #FFFBEB;
        }
        .Pending-text{
            color: #BB5309;
            
        }
        .Completed{
            background-color: #E0F2FE;
        }
        .Completed-text{
            color: #0EA5E9;
            
        }
        .Cancelled{
            background-color: #FEF2F2;
        }
        .Cancelled-text{
            color: #C01C1C;
            
        }
        @media (max-width: 1200px) {
            .container {
                width: calc(100% - 250px);
                margin-left: 250px;
                padding: 15px;
            }
            
            .summary-box {
                min-width: 150px;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                width: calc(100% - 200px);
                margin-left: 200px;
            }
            
            .summary-box {
                min-width: 130px;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 14px;
            }
            
            .action-btn {
                padding: 5px 10px;
                font-size: 11px;
                display: block;
                margin-bottom: 5px;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
                width: 100%;
            }
            
            .page-header h1 {
                font-size: 24px;
                margin-bottom: 10px;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
                margin-bottom: 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .pagination a {
                margin-bottom: 5px;
            }
            
            .summary-box-conteiner {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            
            .summary-box {
                min-width: 150px;
                flex: 0 0 calc(50% - 8px);
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 8px;
            }
            
            .summary-box {
                flex: 0 0 100%;
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 13px;
            }
            
            .status {
                padding: 4px 8px;
                font-size: 11px;
                min-width: 80px;
            }
            
            .pagination a {
                padding: 6px 10px;
                font-size: 14px;
            }
            
            .summary-title {
                
            font-weight: 700;
            font-size: 16px;
            }
            
            .summary-value {
                font-size: 20px;
                font-weight: 700;
            }
            
            .form-control {
                padding: 8px;
                font-size: 14px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 14px;
                width: 100%;
                margin-bottom: 5px;
            }
            
            .filter-container, .no-data, .summary-container {
                padding: 15px;
            }
            
            .no-data i {
                font-size: 36px;
            }
            
            .no-data h3 {
                font-size: 18px;
            }
            
            .no-data p {
                font-size: 14px;
            }
        }
        
        @media (max-width: 375px) {
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 6px 4px;
            }
            
            .action-btn {
                padding: 4px 8px;
                font-size: 10px;
            }
            
            .pagination a {
                padding: 5px 8px;
                margin: 0 2px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="contener-item">
        <div class="page-header">
            <h1>Booking History</h1>
            <p>Manage your Booking History</p>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-container">
            <h1>Booking Overview</h1>
            <div class="summary-box-conteiner">
            <?php
            // Get booking statistics
            $stats = [
                'total' => 0,
                'confirmed' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'completed' => 0
            ];
            
            $statsSql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
            $statsResult = $conn->query($statsSql);
            
            while ($statsRow = $statsResult->fetch_assoc()) {
                $stats[$statsRow['status']] = $statsRow['count'];
                $stats['total'] += $statsRow['count'];
            }
            ?>
            <div class="summary-box Total-Bookings">
                <div class="summary-title Total-Bookings-text">Total Bookings</div>
                <div class="summary-value"><?php echo $stats['total']; ?></div>
            </div> 
            <div class="summary-box Confirmed">
                <div class="summary-title Confirmed-text">Confirmed</div>
                <div class="summary-value"><?php echo $stats['confirmed'] ?? 0; ?></div>
            </div>
            <div class="summary-box Pending">
                <div class="summary-title Pending-text">Pending</div>
                <div class="summary-value"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="summary-box Completed">
                <div class="summary-title Completed-text">Completed</div>
                <div class="summary-value"><?php echo $stats['completed'] ?? 0; ?></div>
            </div>
            <div class="summary-box Cancelled">
                <div class="summary-title Cancelled-text">Cancelled</div>
                <div class="summary-value"><?php echo $stats['cancelled'] ?? 0; ?></div>
            </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-container">
            <form class="filter-form" method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Search by booking ID or property ID" value="<?php echo $searchTerm; ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Bookings Table -->
        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Listing ID</th>
                        <th>Move In Date</th>
                        <th>Duration</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo "Listing #" . $row['listing_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['move_in_date'])); ?></td>
                            <td><?php echo $row['duration'] . ' days'; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <span class="status status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>
                                <a href="view_booking.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">View</a>
                                <?php if ($row['status'] === 'pending' || $row['status'] === 'confirmed'): ?>
                                    <a href="cancel_booking.php?id=<?php echo $row['id']; ?>" 
                                       class="action-btn cancel-btn"
                                       onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        Cancel
                                    </a>
                                <?php endif; ?>
                                <?php if (hasBookingPermission('delete', $_SESSION['user_type'])): ?>
                                    <a href="delete_booking.php?id=<?php echo $row['id']; ?>" 
                                       class="action-btn delete-btn"
                                       onclick="return confirm('Are you sure you want to PERMANENTLY DELETE this booking? This action cannot be undone.');">
                                        Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo $searchTerm; ?>&status=<?php echo $filterStatus; ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo $searchTerm; ?>&status=<?php echo $filterStatus; ?>" 
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo $searchTerm; ?>&status=<?php echo $filterStatus; ?>">Next</a>
                    <?php endif; ?>
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

    <script>
        // Simple JavaScript for enhancing user interaction
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight the current row when clicked
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger when clicking action buttons
                    if (e.target.tagName !== 'A') {
                        tableRows.forEach(r => r.classList.remove('selected'));
                        this.classList.toggle('selected');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>