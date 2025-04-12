<?php
// filepath: c:\xampp\htdocs\Rapidstay1\dashboard\admin\roommate.php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Process actions
$message = '';
$error = '';

// Delete roommate record
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_sql = "DELETE FROM roommates WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Roommate record has been deleted successfully.";
    } else {
        $error = "Failed to delete roommate record: " . $conn->error;
    }
}

// Approve roommate request
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $approve_sql = "UPDATE roommates SET status = 'approved' WHERE id = ?";
    $stmt = $conn->prepare($approve_sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Send notification to users
        $get_users_sql = "SELECT requester_id, requested_id FROM roommates WHERE id = ?";
        $users_stmt = $conn->prepare($get_users_sql);
        $users_stmt->bind_param("i", $id);
        $users_stmt->execute();
        $users_result = $users_stmt->get_result();
        
        if ($row = $users_result->fetch_assoc()) {
            // Send notification to both users
            $notify_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, 'Your roommate request has been approved by the admin.', 'roommate')";
            $notify_stmt = $conn->prepare($notify_sql);
            
            // Notify requester
            $notify_stmt->bind_param("i", $row['requester_id']);
            $notify_stmt->execute();
            
            // Notify requested
            $notify_stmt->bind_param("i", $row['requested_id']);
            $notify_stmt->execute();
        }
        
        $message = "Roommate request has been approved successfully.";
    } else {
        $error = "Failed to approve roommate request: " . $conn->error;
    }
}

// Reject roommate request
if (isset($_GET['action']) && $_GET['action'] === 'reject' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $reject_sql = "UPDATE roommates SET status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($reject_sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Roommate request has been rejected.";
    } else {
        $error = "Failed to reject roommate request: " . $conn->error;
    }
}

// Process form submission for new roommate match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_match'])) {
    $requester_id = intval($_POST['requester_id']);
    $requested_id = intval($_POST['requested_id']);
    $listing_id = intval($_POST['listing_id']);
    $notes = $_POST['notes'];
    
    // Check if users exist
    $check_users_sql = "SELECT id FROM users WHERE id IN (?, ?)";
    $check_stmt = $conn->prepare($check_users_sql);
    $check_stmt->bind_param("ii", $requester_id, $requested_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 2) {
        // Create roommate match
        $create_sql = "INSERT INTO roommates (requester_id, requested_id, listing_id, notes, status, created_at) 
                      VALUES (?, ?, ?, ?, 'approved', NOW())";
        $create_stmt = $conn->prepare($create_sql);
        $create_stmt->bind_param("iiis", $requester_id, $requested_id, $listing_id, $notes);
        
        if ($create_stmt->execute()) {
            $message = "Roommate match created successfully.";
        } else {
            $error = "Failed to create roommate match: " . $conn->error;
        }
    } else {
        $error = "One or both users do not exist.";
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Search and filter
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';

// Base query
$sql = "SELECT r.*, 
         u1.name as requester_name, u1.email as requester_email,
         u2.name as requested_name, u2.email as requested_email,
         l.title as listing_title
        FROM roommates r
        LEFT JOIN users u1 ON r.requester_id = u1.id
        LEFT JOIN users u2 ON r.requested_id = u2.id
        LEFT JOIN listings l ON r.listing_id = l.id
        WHERE 1=1";

// Add search condition
if (!empty($searchTerm)) {
    $sql .= " AND (u1.name LIKE '%$searchTerm%' OR u2.name LIKE '%$searchTerm%' OR l.title LIKE '%$searchTerm%')";
}

// Add filter condition
if ($filterStatus !== 'all') {
    $sql .= " AND r.status = '$filterStatus'";
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM ($sql) as subquery";
$totalResult = $conn->query($countSql);
$totalRecords = 0;

if ($totalResult && $totalRow = $totalResult->fetch_assoc()) {
    $totalRecords = $totalRow['total'];
}

$totalPages = ceil($totalRecords / $recordsPerPage);

// Add limit for pagination
$sql .= " ORDER BY r.created_at DESC LIMIT $offset, $recordsPerPage";

// Execute query
$result = $conn->query($sql);

// Get users for dropdown
$users_sql = "SELECT id, name, email FROM users WHERE user_type = 'tenant' ORDER BY name";
$users_result = $conn->query($users_sql);

$users = [];
if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}

// Get listings for dropdown - MODIFIED TO REMOVE STATUS FILTER
$listings_sql = "SELECT id, title FROM listings ORDER BY title";
$listings_result = $conn->query($listings_sql);

$listings = [];
if ($listings_result) {
    while ($listing = $listings_result->fetch_assoc()) {
        $listings[] = $listing;
    }
}

// Include header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roommate Management</title>
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
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        /* Table Styles */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            margin-bottom: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        
        .table-container table {
            margin-bottom: 0;
        }
        
        thead {
            background-color: #f8fafc;
        }
        
        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 10;
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
        
        .status-approved {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .status-rejected {
            background-color: #FEE2E2;
            color: #B91C1C;
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
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 100;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 25px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        
        .modal-header h2 {
            font-size: 20px;
            margin: 0;
        }
        
        .close {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #475569;
        }
        
        .close:hover {
            color: #111;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #DCFCE7;
            color: #166534;
            border: 1px solid #86EFAC;
        }
        
        .alert-error {
            background-color: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
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
        
        /* Add Matchmaking Form */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 15px 20px;
            background-color: #f8fafc;
            border-bottom: 1px solid #eee;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .card-header h2 {
            font-size: 18px;
            margin: 0;
            color: #333;
        }
        
        .card-body {
            padding: 20px;
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
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
        
        /* Custom scrollbar */
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
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1>Roommate Management</h1>
            <p>Manage roommate requests and matches</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Create Roommate Match Card -->
        <div class="card">
            <div class="card-header">
                <h2>Create New Roommate Match</h2>
            </div>
            <div class="card-body">
                <form action="" method="post" class="filter-form">
                    <div class="form-group">
                        <label for="requester_id">Requester</label>
                        <select name="requester_id" id="requester_id" class="form-control" required>
                            <option value="">Select Requester</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="requested_id">Requested User</label>
                        <select name="requested_id" id="requested_id" class="form-control" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="listing_id">Listing</label>
                        <select name="listing_id" id="listing_id" class="form-control" required>
                            <option value="">Select Listing</option>
                            <?php foreach ($listings as $listing): ?>
                                <option value="<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes about this roommate match"></textarea>
                    </div>
                    
                    <div class="form-group" style="min-width: auto;">
                        <button type="submit" name="create_match" class="btn btn-success">Create Match</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-container">
            <form action="" method="get" class="filter-form">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by name or listing" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="form-group" style="min-width: auto;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Roommates Table -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Requester</th>
                            <th>Roommate</th>
                            <th>Listing</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['requester_name']); ?><br>
                                    <small><?php echo htmlspecialchars($row['requester_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['requested_name']); ?><br>
                                    <small><?php echo htmlspecialchars($row['requested_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['listing_title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="action-btn view-btn" onclick="viewRoommateDetails(<?php echo $row['id']; ?>)">View Details</a>
                                        
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <a href="roommate.php?action=approve&id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Approve</a>
                                            <a href="roommate.php?action=reject&id=<?php echo $row['id']; ?>" class="action-btn delete-btn">Reject</a>
                                        <?php endif; ?>
                                        
                                        <a href="roommate.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Are you sure you want to delete this roommate record? This action cannot be undone.');">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
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
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: #f8fafc; border-radius: 8px;">
                <i class="fas fa-users" style="font-size: 48px; color: #94a3b8; margin-bottom: 20px;"></i>
                <h3>No roommate records found</h3>
                <p>No roommate matches or requests match your search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal for Viewing Roommate Details -->
    <div id="roommate-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Roommate Details</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="roommate-details">
                Loading...
            </div>
            <div class="modal-footer">
                <button class="btn btn-reset" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal when clicking on X or outside the modal
        document.querySelector('.close').addEventListener('click', closeModal);
        
        window.onclick = function(event) {
            const modal = document.getElementById('roommate-modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    });
    
    function viewRoommateDetails(id) {
        // Open modal
        document.getElementById('roommate-modal').style.display = 'block';
        
        // Fetch roommate details via AJAX
        fetch('get_roommate_details.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                let detailsHtml = '';
                
                if (data.error) {
                    detailsHtml = `<div class="alert alert-error">${data.error}</div>`;
                } else {
                    detailsHtml = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h3>Requester</h3>
                                <p><strong>Name:</strong> ${data.requester_name}</p>
                                <p><strong>Email:</strong> ${data.requester_email}</p>
                            </div>
                            <div>
                                <h3>Potential Roommate</h3>
                                <p><strong>Name:</strong> ${data.requested_name}</p>
                                <p><strong>Email:</strong> ${data.requested_email}</p>
                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <h3>Listing Details</h3>
                            <p><strong>Title:</strong> ${data.listing_title}</p>
                            <p><strong>Status:</strong> <span class="status status-${data.status.toLowerCase()}">${data.status}</span></p>
                            <p><strong>Created At:</strong> ${data.created_at}</p>
                        </div>
                        <div style="margin-top: 20px;">
                            <h3>Notes</h3>
                            <p>${data.notes || 'No notes available'}</p>
                        </div>
                    `;
                }
                
                document.getElementById('roommate-details').innerHTML = detailsHtml;
            })
            .catch(error => {
                document.getElementById('roommate-details').innerHTML = `
                    <div class="alert alert-error">Failed to load details. Please try again later.</div>
                `;
                console.error('Error fetching roommate details:', error);
            });
    }
    
    function closeModal() {
        document.getElementById('roommate-modal').style.display = 'none';
    }
    </script>
</body>
</html>