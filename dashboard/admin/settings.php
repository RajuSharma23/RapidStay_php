<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/admin-settings.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Initialize variables
$message = '';
$error = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Get all current settings
function getSettings($conn) {
    $settings = [];
    $query = "SELECT setting_key, setting_value, setting_description FROM system_settings";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['setting_description']
            ];
        }
    }
    
    return $settings;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which settings form was submitted
    if (isset($_POST['save_general'])) {
        // General Settings
        $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
        $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);
        $maintenance_mode = mysqli_real_escape_string($conn, $_POST['maintenance_mode']);
        
        $queries = [
            "UPDATE system_settings SET setting_value = '$site_name' WHERE setting_key = 'site_name'",
            "UPDATE system_settings SET setting_value = '$contact_email' WHERE setting_key = 'contact_email'",
            "UPDATE system_settings SET setting_value = '$maintenance_mode' WHERE setting_key = 'maintenance_mode'"
        ];
        
        $success = true;
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error .= "Error updating setting: " . mysqli_error($conn) . "<br>";
                $success = false;
            }
        }
        
        if ($success) {
            $message = "General settings updated successfully.";
        }
        $activeTab = 'general';
    }
    elseif (isset($_POST['save_listing'])) {
        // Listing Settings
        $pg_approval_mode = mysqli_real_escape_string($conn, $_POST['pg_approval_mode']);
        $auto_approved_owners = mysqli_real_escape_string($conn, $_POST['auto_approved_owners']);
        $max_images = intval($_POST['max_images_per_listing']);
        $default_listing_visibility = mysqli_real_escape_string($conn, $_POST['default_listing_visibility']);
        
        $queries = [
            "UPDATE system_settings SET setting_value = '$pg_approval_mode' WHERE setting_key = 'pg_approval_mode'",
            "UPDATE system_settings SET setting_value = '$auto_approved_owners' WHERE setting_key = 'auto_approved_owners'",
            "UPDATE system_settings SET setting_value = '$max_images' WHERE setting_key = 'max_images_per_listing'",
            "UPDATE system_settings SET setting_value = '$default_listing_visibility' WHERE setting_key = 'default_listing_visibility'"
        ];
        
        $success = true;
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error .= "Error updating listing setting: " . mysqli_error($conn) . "<br>";
                $success = false;
            }
        }
        
        if ($success) {
            $message = "Listing settings updated successfully.";
        }
        $activeTab = 'listings';
    }
    elseif (isset($_POST['save_notification'])) {
        // Notification Settings
        $enable_email = isset($_POST['enable_email_notifications']) ? '1' : '0';
        $enable_system = isset($_POST['enable_system_notifications']) ? '1' : '0';
        $admin_email_recipients = mysqli_real_escape_string($conn, $_POST['admin_email_recipients']);
        
        $queries = [
            "UPDATE system_settings SET setting_value = '$enable_email' WHERE setting_key = 'enable_email_notifications'",
            "UPDATE system_settings SET setting_value = '$enable_system' WHERE setting_key = 'enable_system_notifications'",
            "UPDATE system_settings SET setting_value = '$admin_email_recipients' WHERE setting_key = 'admin_email_recipients'"
        ];
        
        $success = true;
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error .= "Error updating notification setting: " . mysqli_error($conn) . "<br>";
                $success = false;
            }
        }
        
        if ($success) {
            $message = "Notification settings updated successfully.";
        }
        $activeTab = 'notifications';
    }
    elseif (isset($_POST['save_payment'])) {
        // Payment Settings
        $currency = mysqli_real_escape_string($conn, $_POST['currency']);
        $payment_gateway = mysqli_real_escape_string($conn, $_POST['payment_gateway']);
        $service_fee_percentage = floatval($_POST['service_fee_percentage']);
        
        $queries = [
            "UPDATE system_settings SET setting_value = '$currency' WHERE setting_key = 'currency'",
            "UPDATE system_settings SET setting_value = '$payment_gateway' WHERE setting_key = 'payment_gateway'",
            "UPDATE system_settings SET setting_value = '$service_fee_percentage' WHERE setting_key = 'service_fee_percentage'"
        ];
        
        $success = true;
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error .= "Error updating payment setting: " . mysqli_error($conn) . "<br>";
                $success = false;
            }
        }
        
        if ($success) {
            $message = "Payment settings updated successfully.";
        }
        $activeTab = 'payments';
    }
    elseif (isset($_POST['save_security'])) {
        // Security Settings
        $login_attempts = intval($_POST['max_login_attempts']);
        $password_expiry = intval($_POST['password_expiry_days']);
        $enable_2fa = isset($_POST['enable_2fa']) ? '1' : '0';
        
        $queries = [
            "UPDATE system_settings SET setting_value = '$login_attempts' WHERE setting_key = 'max_login_attempts'",
            "UPDATE system_settings SET setting_value = '$password_expiry' WHERE setting_key = 'password_expiry_days'",
            "UPDATE system_settings SET setting_value = '$enable_2fa' WHERE setting_key = 'enable_2fa'"
        ];
        
        $success = true;
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error .= "Error updating security setting: " . mysqli_error($conn) . "<br>";
                $success = false;
            }
        }
        
        if ($success) {
            $message = "Security settings updated successfully.";
        }
        $activeTab = 'security';
    }
}

// Get all settings
$settings = getSettings($conn);

// Get owner list for auto-approved owners
$owners_query = "SELECT id, name, email FROM users WHERE user_type = 'owner' ORDER BY name";
$owners_result = mysqli_query($conn, $owners_query);
$owners = [];
while ($owner = mysqli_fetch_assoc($owners_result)) {
    $owners[] = $owner;
}

// Include header
include '../includes/admin_header.php';
?>

<div class="flex-1 p-8 overflow-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">System Settings</h1>
        <p class="text-gray-600">Configure all aspects of the RapidStay platform</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
            <span><?php echo $message; ?></span>
            <button type="button" class="text-green-700" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
            <span><?php echo $error; ?></span>
            <button type="button" class="text-red-700" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Tabs Navigation -->
        <div class="flex border-b overflow-x-auto">
            <a href="?tab=general" class="px-6 py-4 font-medium <?php echo $activeTab === 'general' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="fas fa-cog mr-2"></i> General
            </a>
            <a href="?tab=listings" class="px-6 py-4 font-medium <?php echo $activeTab === 'listings' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="fas fa-home mr-2"></i> Listings
            </a>
            <a href="?tab=notifications" class="px-6 py-4 font-medium <?php echo $activeTab === 'notifications' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
            <a href="?tab=payments" class="px-6 py-4 font-medium <?php echo $activeTab === 'payments' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="fas fa-rupee-sign mr-2"></i> Payments
            </a>
            <a href="?tab=security" class="px-6 py-4 font-medium <?php echo $activeTab === 'security' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                <i class="fas fa-shield-alt mr-2"></i> Security
            </a>
        </div>
        
        <!-- General Settings -->
        <div id="general-settings" class="p-6 <?php echo $activeTab === 'general' ? '' : 'hidden'; ?>">
            <form method="POST" action="settings.php?tab=general">
                <h2 class="text-xl font-semibold mb-6">General Settings</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                        <input 
                            type="text" 
                            id="site_name" 
                            name="site_name" 
                            value="<?php echo htmlspecialchars($settings['site_name']['value'] ?? 'RapidStay'); ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                        <input 
                            type="email" 
                            id="contact_email" 
                            name="contact_email" 
                            value="<?php echo htmlspecialchars($settings['contact_email']['value'] ?? 'contact@rapidstay.com'); ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="maintenance_mode" class="block text-sm font-medium text-gray-700 mb-1">Maintenance Mode</label>
                    <select 
                        id="maintenance_mode" 
                        name="maintenance_mode" 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="off" <?php echo ($settings['maintenance_mode']['value'] ?? 'off') === 'off' ? 'selected' : ''; ?>>Off</option>
                        <option value="on" <?php echo ($settings['maintenance_mode']['value'] ?? 'off') === 'on' ? 'selected' : ''; ?>>On</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">When enabled, the site will display a maintenance message to regular users. Admins can still log in.</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="save_general" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Listing Settings -->
        <div id="listing-settings" class="p-6 <?php echo $activeTab === 'listings' ? '' : 'hidden'; ?>">
            <form method="POST" action="settings.php?tab=listings">
                <h2 class="text-xl font-semibold mb-6">Listing Settings</h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <h3 class="text-lg font-medium mb-4">PG Approval Settings</h3>
                    
                    <div class="mb-4">
                        <label for="pg_approval_mode" class="block text-sm font-medium text-gray-700 mb-1">Approval Mode</label>
                        <select 
                            id="pg_approval_mode" 
                            name="pg_approval_mode" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="manual" <?php echo ($settings['pg_approval_mode']['value'] ?? 'manual') === 'manual' ? 'selected' : ''; ?>>
                                Manual Approval (Admin review required)
                            </option>
                            <option value="auto" <?php echo ($settings['pg_approval_mode']['value'] ?? 'manual') === 'auto' ? 'selected' : ''; ?>>
                                Auto Approval (No review required)
                            </option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Determines how new PG listings are processed when submitted by owners
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="auto_approved_owners" class="block text-sm font-medium text-gray-700 mb-1">Auto-Approved Owners</label>
                        <select 
                            id="auto_approved_owners_select" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2"
                        >
                            <option value="">-- Select Owner --</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>" data-name="<?php echo htmlspecialchars($owner['name']); ?>">
                                    <?php echo htmlspecialchars($owner['name'] . ' (' . $owner['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="addSelectedOwner()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-md text-sm">
                            Add Selected Owner
                        </button>
                        
                        <div id="selected-owners-container" class="flex flex-wrap gap-2 mt-3">
                            <!-- Selected owners will be displayed here -->
                        </div>
                        
                        <input 
                            type="hidden" 
                            id="auto_approved_owners" 
                            name="auto_approved_owners" 
                            value="<?php echo htmlspecialchars($settings['auto_approved_owners']['value'] ?? ''); ?>"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Comma-separated list of owner IDs whose listings are automatically approved (only applies if Manual Approval is selected)
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="default_listing_visibility" class="block text-sm font-medium text-gray-700 mb-1">Default Listing Visibility</label>
                        <select 
                            id="default_listing_visibility" 
                            name="default_listing_visibility" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="visible" <?php echo ($settings['default_listing_visibility']['value'] ?? 'visible') === 'visible' ? 'selected' : ''; ?>>Visible</option>
                            <option value="hidden" <?php echo ($settings['default_listing_visibility']['value'] ?? 'visible') === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Default visibility for new listings after approval
                        </p>
                    </div>
                    
                    <div>
                        <label for="max_images_per_listing" class="block text-sm font-medium text-gray-700 mb-1">Maximum Images Per Listing</label>
                        <input 
                            type="number" 
                            id="max_images_per_listing" 
                            name="max_images_per_listing" 
                            value="<?php echo intval($settings['max_images_per_listing']['value'] ?? 10); ?>" 
                            min="1" 
                            max="50" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Maximum number of images that can be uploaded per listing
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="save_listing" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save Listing Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Notification Settings -->
        <div id="notification-settings" class="p-6 <?php echo $activeTab === 'notifications' ? '' : 'hidden'; ?>">
            <form method="POST" action="settings.php?tab=notifications">
                <h2 class="text-xl font-semibold mb-6">Notification Settings</h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <h3 class="text-lg font-medium mb-4">Email Notifications</h3>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="enable_email_notifications" 
                                class="rounded"
                                <?php echo ($settings['enable_email_notifications']['value'] ?? '1') === '1' ? 'checked' : ''; ?>
                            >
                            <span class="ml-2">Enable Email Notifications</span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1 ml-6">
                            Send email notifications for important system events
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="admin_email_recipients" class="block text-sm font-medium text-gray-700 mb-1">Admin Email Recipients</label>
                        <input 
                            type="text" 
                            id="admin_email_recipients" 
                            name="admin_email_recipients" 
                            value="<?php echo htmlspecialchars($settings['admin_email_recipients']['value'] ?? ''); ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="admin@example.com, manager@example.com"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Comma-separated list of email addresses that will receive admin notifications
                        </p>
                    </div>
                </div>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <h3 class="text-lg font-medium mb-4">System Notifications</h3>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="enable_system_notifications" 
                                class="rounded"
                                <?php echo ($settings['enable_system_notifications']['value'] ?? '1') === '1' ? 'checked' : ''; ?>
                            >
                            <span class="ml-2">Enable In-App Notifications</span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1 ml-6">
                            Display in-app notifications for users
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="save_notification" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save Notification Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Payment Settings -->
        <div id="payment-settings" class="p-6 <?php echo $activeTab === 'payments' ? '' : 'hidden'; ?>">
            <form method="POST" action="settings.php?tab=payments">
                <h2 class="text-xl font-semibold mb-6">Payment Settings</h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                            <select 
                                id="currency" 
                                name="currency" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="INR" <?php echo ($settings['currency']['value'] ?? 'INR') === 'INR' ? 'selected' : ''; ?>>Indian Rupee (₹)</option>
                                <option value="USD" <?php echo ($settings['currency']['value'] ?? 'INR') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                <option value="EUR" <?php echo ($settings['currency']['value'] ?? 'INR') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                <option value="GBP" <?php echo ($settings['currency']['value'] ?? 'INR') === 'GBP' ? 'selected' : ''; ?>>British Pound (£)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="payment_gateway" class="block text-sm font-medium text-gray-700 mb-1">Payment Gateway</label>
                            <select 
                                id="payment_gateway" 
                                name="payment_gateway" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="razorpay" <?php echo ($settings['payment_gateway']['value'] ?? 'razorpay') === 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                                <option value="stripe" <?php echo ($settings['payment_gateway']['value'] ?? 'razorpay') === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                <option value="paypal" <?php echo ($settings['payment_gateway']['value'] ?? 'razorpay') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <h3 class="text-lg font-medium mb-4">Service Fees</h3>
                    
                    <div class="mb-4">
                        <label for="service_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Service Fee Percentage (%)</label>
                        <input 
                            type="number" 
                            id="service_fee_percentage" 
                            name="service_fee_percentage" 
                            value="<?php echo floatval($settings['service_fee_percentage']['value'] ?? 5); ?>" 
                            min="0" 
                            max="100" 
                            step="0.01" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Percentage fee charged on each transaction
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="save_payment" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save Payment Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Security Settings -->
        <div id="security-settings" class="p-6 <?php echo $activeTab === 'security' ? '' : 'hidden'; ?>">
            <form method="POST" action="settings.php?tab=security">
                <h2 class="text-xl font-semibold mb-6">Security Settings</h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                            <input 
                                type="number" 
                                id="max_login_attempts" 
                                name="max_login_attempts" 
                                value="<?php echo intval($settings['max_login_attempts']['value'] ?? 5); ?>" 
                                min="1" 
                                max="10" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <p class="text-sm text-gray-500 mt-1">
                                Number of login attempts before account is temporarily locked
                            </p>
                        </div>
                        
                        <div>
                            <label for="password_expiry_days" class="block text-sm font-medium text-gray-700 mb-1">Password Expiry (Days)</label>
                            <input 
                                type="number" 
                                id="password_expiry_days" 
                                name="password_expiry_days" 
                                value="<?php echo intval($settings['password_expiry_days']['value'] ?? 90); ?>" 
                                min="0" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <p class="text-sm text-gray-500 mt-1">
                                Number of days before passwords expire (0 = never)
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="enable_2fa" 
                                class="rounded"
                                <?php echo ($settings['enable_2fa']['value'] ?? '0') === '1' ? 'checked' : ''; ?>
                            >
                            <span class="ml-2">Enable Two-Factor Authentication</span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1 ml-6">
                            Require two-factor authentication for admin users
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="save_security" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save Security Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Setup auto-approved owners selection
    document.addEventListener('DOMContentLoaded', function() {
        const ownersInput = document.getElementById('auto_approved_owners');
        const ownersContainer = document.getElementById('selected-owners-container');
        
        // Initial population of selected owners
        if (ownersInput.value) {
            const ownerIds = ownersInput.value.split(',');
            populateSelectedOwners(ownerIds);
        }
    });
    
    function populateSelectedOwners(ownerIds) {
        const ownersContainer = document.getElementById('selected-owners-container');
        ownersContainer.innerHTML = '';
        
        // Get all options from the select element
        const selectElement = document.getElementById('auto_approved_owners_select');
        const options = selectElement.options;
        
        // Find each owner by ID and add to container
        ownerIds.forEach(id => {
            // Skip empty IDs
            if (!id.trim()) return;
            
            let ownerName = '';
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === id) {
                    ownerName = options[i].dataset.name;
                    break;
                }
            }
            
            // If no name found, use ID as fallback
            if (!ownerName) ownerName = `Owner ${id}`;
            
            addOwnerTag(id.trim(), ownerName);
        });
    }
    
    function addSelectedOwner() {
        const select = document.getElementById('auto_approved_owners_select');
        const selectedOption = select.options[select.selectedIndex];
        
        if (select.value) {
            const ownerId = select.value;
            const ownerName = selectedOption.dataset.name;
            
            // Check if already added
            const existingOwners = document.getElementById('auto_approved_owners').value.split(',');
            if (existingOwners.includes(ownerId)) return;
            
            // Add to UI
            addOwnerTag(ownerId, ownerName);
            
            // Update hidden input
            updateOwnersInput();
        }
    }
    
    function addOwnerTag(ownerId, ownerName) {
        const container = document.getElementById('selected-owners-container');
        
        const tag = document.createElement('div');
        tag.className = 'bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center';
        tag.dataset.id = ownerId;
        
        tag.innerHTML = `
            ${ownerName}
            <button type="button" onclick="removeOwner('${ownerId}')" class="ml-2 text-blue-600 hover:text-blue-800">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(tag);
    }
    
    function removeOwner(ownerId) {
        // Remove from UI
        const tag = document.querySelector(`.selected-owners-container [data-id="${ownerId}"]`);
        if (tag) tag.remove();
        
        // Update hidden input
        updateOwnersInput();
    }
    
    function updateOwnersInput() {
        const container = document.getElementById('selected-owners-container');
        const tags = container.querySelectorAll('[data-id]');
        const ownerIds = Array.from(tags).map(tag => tag.dataset.id);
        
        document.getElementById('auto_approved_owners').value = ownerIds.join(',');
    }
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>