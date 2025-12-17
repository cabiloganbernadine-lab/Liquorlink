<?php
// Enhanced JavaScript Protection Loader
header('Content-Type: application/javascript; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Get the requested file
$file = $_GET['file'] ?? '';

// Only allow specific files
$allowed_files = ['register.js', 'login.js'];
if (!in_array($file, $allowed_files)) {
    http_response_code(404);
    exit('// 404 File not found');
}

// Enhanced security: Multiple checks
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// Block if no referrer (direct access)
if (empty($referrer)) {
    http_response_code(404);
    exit('// 404 Direct access not allowed');
}

// Block if referrer doesn't contain localhost or our domain
if (strpos($referrer, 'localhost') === false &&
    strpos($referrer, '127.0.0.1') === false &&
    strpos($referrer, $_SERVER['HTTP_HOST']) === false) {
    http_response_code(404);
    exit('// 404 Unauthorized access');
}

// Additional check: Make sure request comes from script tag, not direct navigation
if (strpos($referrer, '/Php/') === false) {
    http_response_code(404);
    exit('// 404 Invalid referrer');
}

// Serve the JavaScript file
$js_file = '../js/' . $file;
if (file_exists($js_file)) {
    // Add security headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the JavaScript content
    readfile($js_file);
} else {
    http_response_code(404);
    exit('// 404 File not found');
}
?>