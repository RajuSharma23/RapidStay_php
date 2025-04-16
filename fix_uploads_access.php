<?php
// Script to create permissive .htaccess files in all upload directories

// Define upload directories to fix
$upload_dirs = [
    'uploads',
    'uploads/profile_pictures',
    'uploads/listings',
    'uploads/staff',
    'uploads/profiles'
];

// Create each directory if it doesn't exist and add .htaccess
foreach ($upload_dirs as $dir) {
    // Create directory if it doesn't exist
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir<br>";
    }
    
    // Create .htaccess with permissive settings
    $htaccess_content = <<<EOT
# Allow access from all domains
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>

# Allow direct access to image files
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order Allow,Deny
        Allow from all
    </IfModule>
</FilesMatch>

# Disable PHP execution in this directory
<FilesMatch "\.php$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevent directory listings
Options -Indexes
EOT;

    // Write the .htaccess file
    $htaccess_path = $dir . '/.htaccess';
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        echo "Created/updated .htaccess in $dir<br>";
    } else {
        echo "Failed to create .htaccess in $dir<br>";
    }
}

echo "<p>Done. Image access restrictions should be removed.</p>";
?>