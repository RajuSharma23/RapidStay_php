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
<<<<<<< HEAD
    <style>
        .container {
            max-width: 1200px;
            margin-left: 50px;
            margin-top: 40px;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            border-top: 4px solid #4c57ef;
        }
        
        .contener-item {
            /* margin-top: 20px; */
        }
        
        .page-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .page-header p {
            font-size: 16px;
            color: #666;
        }
        
        /* Filter Section */
        .filter-container {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex-grow: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #4c57ef;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 87, 239, 0.1);
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #4c57ef;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a46cc;
        }
        
        .btn-reset {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-reset:hover {
            background-color: #e2e8f0;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        thead {
            background-color: #f8fafc;
        }
        
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        th {
            font-weight: 600;
            color: #475569;
        }
        
        tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Status Badges */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 90px;
        }
        
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-confirmed {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .status-cancelled {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        
        .status-completed {
            background-color: #E0F2FE;
            color: #0C4A6E;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            margin-right: 6px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }
        
        .view-btn {
            background-color: #3B82F6;
            color: white;
        }
        
        .view-btn:hover {
            background-color: #2563EB;
        }
        
        .edit-btn {
            background-color: #10B981;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #059669;
        }
        
        .delete-btn {
            background-color: #EF4444;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #DC2626;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 8px 14px;
            margin: 0 4px 8px;
            border-radius: 5px;
            text-decoration: none;
            color: #475569;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover, 
        .pagination a.active {
            background-color: #4c57ef;
            color: white;
            border-color: #4c57ef;
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 48px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .no-data i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        
        .no-data h3 {
            font-size: 20px;
            color: #334155;
            margin-bottom: 8px;
        }
        
        .no-data p {
            font-size: 16px;
            color: #64748b;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                margin-left: 200px;
                padding: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                margin-left: 0;
                width: 100%;
                border-radius: 0;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 13px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .action-btn {
                padding: 5px 10px;
                font-size: 11px;
            }
        }
        
        /* Custom scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Table container with scrolling */
        .table-container {
            max-height: 500px; /* Reduced from 600px to ensure it activates */
            overflow-y: auto !important; /* Force scrolling */
            overflow-x: auto;
            position: relative; /* Create stacking context */
            margin-bottom: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Ensure table header stays fixed while scrolling */
        .table-container table {
            width: 100% !important;
            margin-bottom: 0;
            border-collapse: separate; /* Fix border-radius clipping */
            border-spacing: 0;
        }

        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 10;
            /* Add shadow to separate from body */
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
        }

        /* Cross-browser scrollbar styling */
        /* Webkit (Chrome, Safari, newer Edge) */
        .table-container::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 8px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Firefox */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
    </style>
=======
    <link rel="stylesheet" href="../../assets/css/managebook.css">
    <!-- CSS styling similar to owner booking-history.php -->
>>>>>>> 37a85f460eb036c1e1c57d0c89f76801360aa90b
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
                <form action="" method="get" class="filter-form">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                            placeholder="Search by ID, user or listing" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    
                    <div class="form-group" style="min-width: auto;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Bookings Table -->
<<<<<<< HEAD
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-container">
=======
            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
>>>>>>> 37a85f460eb036c1e1c57d0c89f76801360aa90b
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
<<<<<<< HEAD
                                    <td><?php echo $row['duration'] . ' months'; ?></td>
=======
                                    <td><?php echo $row['duration'] . ' days'; ?></td>
>>>>>>> 37a85f460eb036c1e1c57d0c89f76801360aa90b
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
<<<<<<< HEAD
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>" 
                               class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
                        <?php endif; ?>
=======
                    
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
>>>>>>> 37a85f460eb036c1e1c57d0c89f76801360aa90b
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Force minimum height on the table container to activate scrolling
        const tableContainer = document.querySelector('.table-container');
        const table = tableContainer.querySelector('table');
        
        // Ensure scrolling is activated
        function ensureScroll() {
            // Force min-height for testing
            if (table.offsetHeight < 501) {
                // If table is smaller than container max-height, add empty rows to force scrollbar
                console.log('Adding minimum height to ensure scrollbar appears');
                tableContainer.style.minHeight = '500px';
                
                // Add a small spacer to bottom of table if needed
                const spacer = document.createElement('tr');
                const spacerCell = document.createElement('td');
                spacerCell.setAttribute('colspan', '8');
                spacerCell.style.height = '1px';
                spacerCell.style.padding = '0';
                spacer.appendChild(spacerCell);
                
                // Only add spacer if we have at least one row of data
                if (table.querySelectorAll('tbody tr').length > 0) {
                    table.querySelector('tbody').appendChild(spacer);
                }
            }
            
            console.log('Table height:', table.offsetHeight);
            console.log('Container height:', tableContainer.offsetHeight);
        }
        
        // Run after a slight delay to ensure DOM is fully loaded
        setTimeout(ensureScroll, 100);
        
        // For debugging - click on container to check scrollability
        tableContainer.addEventListener('click', function() {
            console.log('Scrollable height:', table.offsetHeight);
            console.log('Container visible height:', tableContainer.offsetHeight);
            console.log('Is scrollable:', table.offsetHeight > tableContainer.offsetHeight);
        });
    });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>