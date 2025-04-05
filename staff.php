<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rapid_stay";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$id = $name = $position = $department = $email = $phone = "";
$error = $success = "";
$search = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new employee
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $position = $_POST['position'];
        $department = $_POST['department'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        $sql = "INSERT INTO employees (name, position, department, email, phone) 
                VALUES ('$name', '$position', '$department', '$email', '$phone')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "New employee added successfully";
        } else {
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
    
    // Update employee
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $position = $_POST['position'];
        $department = $_POST['department'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        $sql = "UPDATE employees SET 
                name='$name', 
                position='$position', 
                department='$department', 
                email='$email', 
                phone='$phone' 
                WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Employee information updated successfully";
        } else {
            $error = "Error updating record: " . $conn->error;
        }
    }
    
    // Delete employee
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        
        $sql = "DELETE FROM employees WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Employee deleted successfully";
        } else {
            $error = "Error deleting record: " . $conn->error;
        }
    }
    
    // Search employees
    if (isset($_POST['search'])) {
        $search = $_POST['search_term'];
    }
}

// Edit employee
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM employees WHERE id=$id";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $position = $row['position'];
        $department = $row['department'];
        $email = $row['email'];
        $phone = $row['phone'];
    }
}

// Fetch all employees or search results
$sql = "SELECT * FROM employees";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR position LIKE '%$search%' OR department LIKE '%$search%'";
}
$sql .= " ORDER BY name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management System</title>
    <style>
        /* CSS Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        
        header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px 5px 0 0;
        }
        
        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .form-section {
            flex: 1;
            min-width: 300px;
        }
        
        .table-section {
            flex: 2;
            min-width: 500px;
        }
        
        form {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], 
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .search-bar {
            margin-bottom: 20px;
            display: flex;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }
        
        .search-bar button {
            border-radius: 0 4px 4px 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #3498db;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }
        
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            
            .form-section, .table-section {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Staff Management System</h1>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Staff</h3>
                <?php
                $countSql = "SELECT COUNT(*) as total FROM employees";
                $countResult = $conn->query($countSql);
                $totalEmployees = $countResult->fetch_assoc()['total'];
                ?>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Departments</h3>
                <?php
                $deptSql = "SELECT COUNT(DISTINCT department) as total FROM employees";
                $deptResult = $conn->query($deptSql);
                $totalDepartments = $deptResult->fetch_assoc()['total'];
                ?>
                <p><?php echo $totalDepartments; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Recently Added</h3>
                <?php
                $recentSql = "SELECT COUNT(*) as total FROM employees WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $recentResult = $conn->query($recentSql);
                $recentEmployees = $recentResult->fetch_assoc()['total'] ?? 0;
                ?>
                <p><?php echo $recentEmployees; ?></p>
            </div>
        </div>
        
        <div class="content">
            <div class="form-section">
                <h2><?php echo $id ? "Edit Employee" : "Add New Employee"; ?></h2>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php if ($id): ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="<?php echo $position; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="" disabled <?php echo empty($department) ? 'selected' : ''; ?>>Select Department</option>
                            <option value="HR" <?php echo $department == 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                            <option value="IT" <?php echo $department == 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                            <option value="Finance" <?php echo $department == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="Marketing" <?php echo $department == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Operations" <?php echo $department == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                            <option value="Sales" <?php echo $department == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="Research" <?php echo $department == 'Research' ? 'selected' : ''; ?>>Research & Development</option>
                            <option value="Customer Support" <?php echo $department == 'Customer Support' ? 'selected' : ''; ?>>Customer Support</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $phone; ?>" required>
                    </div>
                    
                    <?php if ($id): ?>
                        <button type="submit" name="update" class="btn btn-warning">Update Employee</button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-primary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn btn-success">Add Employee</button>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-section">
                <h2>Employee List</h2>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="search-bar">
                    <input type="text" name="search_term" placeholder="Search by name, position or department..." value="<?php echo $search; ?>">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-warning">Clear</a>
                    <?php endif; ?>
                </form>
                
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row["id"]; ?></td>
                                    <td><?php echo $row["name"]; ?></td>
                                    <td><?php echo $row["position"]; ?></td>
                                    <td><?php echo $row["department"]; ?></td>
                                    <td><?php echo $row["email"]; ?></td>
                                    <td><?php echo $row["phone"]; ?></td>
                                    <td class="action-buttons">
                                        <a href="?edit=<?php echo $row["id"]; ?>" class="btn btn-warning">Edit</a>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No employees found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>