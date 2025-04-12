<?php
// filepath: c:\xampp\htdocs\Rapidstay1\dashboard\admin\settings.php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Initialize variables
$message = '';
$error = '';

// Check if settings table exists, if not create it
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE settings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        $error = "Failed to create settings table: " . mysqli_error($conn);
    }
    
    // Insert default settings
    $default_settings = [
        ['site_name', 'RapidStay'],
        ['site_email', 'info@rapidstay.com'],
        ['site_phone', '+91 123 456 7890'],
        ['site_address', '123 Main Street, City, Country'],
        ['booking_approval', 'manual'],
        ['enable_payments', '1'],
        ['payment_gateway', 'razorpay'],
        ['razorpay_key_id', ''],
        ['razorpay_key_secret', ''],
        ['service_fee', '2'],
        ['enable_notifications', '1'],
        ['admin_email_notifications', '1'],
        ['user_email_notifications', '1'],
        ['maintenance_mode', '0']
    ];
    
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    
    foreach ($default_settings as $setting) {
        mysqli_stmt_bind_param($insert_stmt, "ss", $setting[0], $setting[1]);
        mysqli_stmt_execute($insert_stmt);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general'])) {
        // Update general settings
        $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
        $site_email = mysqli_real_escape_string($conn, $_POST['site_email']);
        $site_phone = mysqli_real_escape_string($conn, $_POST['site_phone']);
        $site_address = mysqli_real_escape_string($conn, $_POST['site_address']);
        
        $settings = [
            ['site_name', $site_name],
            ['site_email', $site_email],
            ['site_phone', $site_phone],
            ['site_address', $site_address]
        ];
        
        updateSettings($conn, $settings, $message, $error);
        
    } elseif (isset($_POST['update_booking'])) {
        // Update booking settings
        $booking_approval = mysqli_real_escape_string($conn, $_POST['booking_approval']);
        
        $settings = [
            ['booking_approval', $booking_approval]
        ];
        
        updateSettings($conn, $settings, $message, $error);
        
    } elseif (isset($_POST['update_payment'])) {
        // Update payment settings
        $enable_payments = isset($_POST['enable_payments']) ? '1' : '0';
        $payment_gateway = mysqli_real_escape_string($conn, $_POST['payment_gateway']);
        $razorpay_key_id = mysqli_real_escape_string($conn, $_POST['razorpay_key_id']);
        $razorpay_key_secret = mysqli_real_escape_string($conn, $_POST['razorpay_key_secret']);
        $service_fee = mysqli_real_escape_string($conn, $_POST['service_fee']);
        
        $settings = [
            ['enable_payments', $enable_payments],
            ['payment_gateway', $payment_gateway],
            ['razorpay_key_id', $razorpay_key_id],
            ['razorpay_key_secret', $razorpay_key_secret],
            ['service_fee', $service_fee]
        ];
        
        updateSettings($conn, $settings, $message, $error);
        
    } elseif (isset($_POST['update_notifications'])) {
        // Update notification settings
        $enable_notifications = isset($_POST['enable_notifications']) ? '1' : '0';
        $admin_email_notifications = isset($_POST['admin_email_notifications']) ? '1' : '0';
        $user_email_notifications = isset($_POST['user_email_notifications']) ? '1' : '0';
        
        $settings = [
            ['enable_notifications', $enable_notifications],
            ['admin_email_notifications', $admin_email_notifications],
            ['user_email_notifications', $user_email_notifications]
        ];
        
        updateSettings($conn, $settings, $message, $error);
        
    } elseif (isset($_POST['update_system'])) {
        // Update system settings
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
        
        $settings = [
            ['maintenance_mode', $maintenance_mode]
        ];
        
        updateSettings($conn, $settings, $message, $error);
    }
}

// Function to update settings
function updateSettings($conn, $settings, &$message, &$error) {
    $update_stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    if (!$update_stmt) {
        $error = "Failed to prepare statement: " . mysqli_error($conn);
        return;
    }
    
    $success = true;
    
    foreach ($settings as $setting) {
        mysqli_stmt_bind_param($update_stmt, "ss", $setting[0], $setting[1]);
        if (!mysqli_stmt_execute($update_stmt)) {
            $error = "Failed to update settings: " . mysqli_error($conn);
            $success = false;
            break;
        }
    }
    
    if ($success) {
        $message = "Settings updated successfully.";
    }
}

// Get all settings
$settings = [];
$settings_query = "SELECT setting_key, setting_value FROM settings";
$settings_result = mysqli_query($conn, $settings_query);

while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Include header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - RapidStay</title>
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
        
        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 24px;
            border-bottom: 1px solid #eee;
            overflow-x: auto;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .tab.active {
            color: #4c57ef;
            border-bottom-color: #4c57ef;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group .hint {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #4c57ef;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 87, 239, 0.1);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 12px;
        }
        
        .form-check input {
            margin-right: 8px;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #4c57ef;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a46cc;
        }
        
        .card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            margin-bottom: 16px;
            color: #333;
            font-weight: 600;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                margin-left: 200px;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
            }
            
            .tab {
                padding: 10px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>System Settings</h1>
            <p>Manage your RapidStay system configuration</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="general">General Settings</div>
            <div class="tab" data-tab="booking">Booking Settings</div>
            <div class="tab" data-tab="payment">Payment Settings</div>
            <div class="tab" data-tab="notifications">Notification Settings</div>
            <div class="tab" data-tab="system">System Settings</div>
        </div>
        
        <!-- General Settings -->
        <div id="general" class="tab-content active">
            <form action="" method="post">
                <div class="card">
                    <h3 class="card-title">Site Information</h3>
                    
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control" 
                               value="<?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_email">Contact Email</label>
                        <input type="email" id="site_email" name="site_email" class="form-control" 
                               value="<?php echo isset($settings['site_email']) ? htmlspecialchars($settings['site_email']) : ''; ?>" required>
                        <div class="hint">Used for system notifications and contact info</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_phone">Contact Phone</label>
                        <input type="text" id="site_phone" name="site_phone" class="form-control" 
                               value="<?php echo isset($settings['site_phone']) ? htmlspecialchars($settings['site_phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_address">Business Address</label>
                        <textarea id="site_address" name="site_address" class="form-control" rows="3"><?php echo isset($settings['site_address']) ? htmlspecialchars($settings['site_address']) : ''; ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="update_general" class="btn btn-primary">Save General Settings</button>
            </form>
        </div>
        
        <!-- Booking Settings -->
        <div id="booking" class="tab-content">
            <form action="" method="post">
                <div class="card">
                    <h3 class="card-title">Booking Configuration</h3>
                    
                    <div class="form-group">
                        <label for="booking_approval">Booking Approval</label>
                        <select id="booking_approval" name="booking_approval" class="form-control">
                            <option value="automatic" <?php echo (isset($settings['booking_approval']) && $settings['booking_approval'] == 'automatic') ? 'selected' : ''; ?>>Automatic - Accept all bookings</option>
                            <option value="manual" <?php echo (isset($settings['booking_approval']) && $settings['booking_approval'] == 'manual') ? 'selected' : ''; ?>>Manual - Require admin/owner approval</option>
                        </select>
                        <div class="hint">Determines how new booking requests are processed</div>
                    </div>
                </div>
                
                <button type="submit" name="update_booking" class="btn btn-primary">Save Booking Settings</button>
            </form>
        </div>
        
        <!-- Payment Settings -->
        <div id="payment" class="tab-content">
            <form action="" method="post">
                <div class="card">
                    <h3 class="card-title">Payment Configuration</h3>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="enable_payments" <?php echo (isset($settings['enable_payments']) && $settings['enable_payments'] == '1') ? 'checked' : ''; ?>>
                            Enable Online Payments
                        </label>
                        <div class="hint">Allow users to pay online for bookings</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_gateway">Payment Gateway</label>
                        <select id="payment_gateway" name="payment_gateway" class="form-control">
                            <option value="razorpay" <?php echo (isset($settings['payment_gateway']) && $settings['payment_gateway'] == 'razorpay') ? 'selected' : ''; ?>>Razorpay</option>
                            <option value="stripe" <?php echo (isset($settings['payment_gateway']) && $settings['payment_gateway'] == 'stripe') ? 'selected' : ''; ?>>Stripe</option>
                            <option value="paypal" <?php echo (isset($settings['payment_gateway']) && $settings['payment_gateway'] == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="razorpay_key_id">Razorpay Key ID</label>
                        <input type="text" id="razorpay_key_id" name="razorpay_key_id" class="form-control" 
                               value="<?php echo isset($settings['razorpay_key_id']) ? htmlspecialchars($settings['razorpay_key_id']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="razorpay_key_secret">Razorpay Key Secret</label>
                        <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" class="form-control" 
                               value="<?php echo isset($settings['razorpay_key_secret']) ? htmlspecialchars($settings['razorpay_key_secret']) : ''; ?>">
                        <div class="hint">Get your API keys from your Razorpay dashboard</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_fee">Service Fee (%)</label>
                        <input type="number" id="service_fee" name="service_fee" class="form-control" min="0" max="100" step="0.01" 
                               value="<?php echo isset($settings['service_fee']) ? htmlspecialchars($settings['service_fee']) : '0'; ?>">
                        <div class="hint">Percentage fee charged on each booking</div>
                    </div>
                </div>
                
                <button type="submit" name="update_payment" class="btn btn-primary">Save Payment Settings</button>
            </form>
        </div>
        
        <!-- Notification Settings -->
        <div id="notifications" class="tab-content">
            <form action="" method="post">
                <div class="card">
                    <h3 class="card-title">Notification Configuration</h3>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="enable_notifications" <?php echo (isset($settings['enable_notifications']) && $settings['enable_notifications'] == '1') ? 'checked' : ''; ?>>
                            Enable System Notifications
                        </label>
                        <div class="hint">Send notifications for important events</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="admin_email_notifications" <?php echo (isset($settings['admin_email_notifications']) && $settings['admin_email_notifications'] == '1') ? 'checked' : ''; ?>>
                            Send Email Notifications to Admin
                        </label>
                        <div class="hint">Notify admin of new bookings, registrations, etc.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="user_email_notifications" <?php echo (isset($settings['user_email_notifications']) && $settings['user_email_notifications'] == '1') ? 'checked' : ''; ?>>
                            Send Email Notifications to Users
                        </label>
                        <div class="hint">Notify users of booking confirmations, updates, etc.</div>
                    </div>
                </div>
                
                <button type="submit" name="update_notifications" class="btn btn-primary">Save Notification Settings</button>
            </form>
        </div>
        
        <!-- System Settings -->
        <div id="system" class="tab-content">
            <form action="" method="post">
                <div class="card">
                    <h3 class="card-title">System Configuration</h3>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="maintenance_mode" <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                            Enable Maintenance Mode
                        </label>
                        <div class="hint">When enabled, only administrators can access the site</div>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Database Actions</h3>
                    
                    <div class="form-group">
                        <p>These actions affect system data. Use with caution.</p>
                        
                        <a href="backup_database.php" class="btn btn-primary" style="background-color: #3b82f6; margin-right: 10px;">
                            Backup Database
                        </a>
                        
                        <a href="optimize_tables.php" class="btn btn-primary" style="background-color: #10b981;">
                            Optimize Database Tables
                        </a>
                    </div>
                </div>
                
                <button type="submit" name="update_system" class="btn btn-primary">Save System Settings</button>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Show payment fields based on gateway selection
        const paymentGateway = document.getElementById('payment_gateway');
        if (paymentGateway) {
            paymentGateway.addEventListener('change', updatePaymentFields);
            updatePaymentFields(); // Run once on page load
        }
        
        function updatePaymentFields() {
            const selectedGateway = paymentGateway.value;
            
            // Hide all gateway-specific fields
            document.querySelectorAll('[id^=razorpay_]').forEach(el => {
                el.closest('.form-group').style.display = 'none';
            });
            document.querySelectorAll('[id^=stripe_]').forEach(el => {
                el.closest('.form-group').style.display = 'none';
            });
            document.querySelectorAll('[id^=paypal_]').forEach(el => {
                el.closest('.form-group').style.display = 'none';
            });
            
            // Show fields for selected gateway
            document.querySelectorAll(`[id^=${selectedGateway}_]`).forEach(el => {
                el.closest('.form-group').style.display = 'block';
            });
        }
    });
    </script>
</body>
</html>