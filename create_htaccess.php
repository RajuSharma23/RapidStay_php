<?php
// Create .htaccess file in uploads directory
$uploads_base = 'uploads';
$htaccess_file = $uploads_base . '/.htaccess';

// Create uploads directory if it doesn't exist
if (!is_dir($uploads_base)) {
    mkdir($uploads_base, 0755, true);
}

// Create .htaccess with proper permissions
$htaccess_content = <<<EOT
<IfModule mod_authz_core.c>
    Require all granted
</IfModule>
<IfModule !mod_authz_core.c>
    Order Allow,Deny
    Allow from all
</IfModule>

Options -Indexes
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>
EOT;

file_put_contents($htaccess_file, $htaccess_content);
echo "Created .htaccess file in uploads directory";
?>