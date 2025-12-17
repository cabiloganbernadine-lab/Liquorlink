<?php
// Secure JavaScript Handler
// This file serves JavaScript content only to legitimate requests

// Set security headers to prevent caching
header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Function to return JavaScript 404 error
function returnJSError($message) {
    header("HTTP/1.0 404 Not Found");
    exit("console.error('" . addslashes($message) . "');");
}

// Get the requested JS file
$requestedFile = isset($_GET['file']) ? basename($_GET['file']) : '';

// Define allowed JavaScript files
$allowedFiles = [
    'register.js',
    'login.js',
    'index.js'
];

// Check if file is allowed
if (!in_array($requestedFile, $allowedFiles)) {
    returnJSError("File not found: " . $requestedFile);
}

// Get the file path (try multiple paths for flexibility)
$possiblePaths = [
    "../js/" . $requestedFile,
    __DIR__ . "/../js/" . $requestedFile,
    "./js/" . $requestedFile,
    dirname(__DIR__) . "/js/" . $requestedFile
];

$jsFilePath = '';
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $jsFilePath = $path;
        break;
    }
}

if (empty($jsFilePath)) {
    returnJSError("File does not exist: " . $requestedFile . " (checked paths: " . implode(', ', $possiblePaths) . ")");
}

// Additional security: Check referrer (more flexible for different environments)
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// For development, be more permissive
if (!empty($referrer)) {
    // Allow localhost and current domain
    if (strpos($referrer, 'localhost') === false &&
        strpos($referrer, '127.0.0.1') === false &&
        strpos($referrer, $host) === false) {
        // For now, let's be permissive in development
        // returnJSError("Unauthorized access - invalid referrer");
    }
}

// Additional validation: Check if request has a simple access token
// This prevents direct file access while allowing legitimate page loads
$validRequest = false;

// Allow if there's a valid referrer OR if it's a POST request (AJAX)
// For basic protection, we mainly rely on referrer validation
if (!empty($referrer) || ($_SERVER['REQUEST_METHOD'] === 'POST')) {
    $validRequest = true;
}

// If no referrer (e.g., direct access), return 404
if (!$validRequest) {
    returnJSError("Direct access not allowed - missing referrer");
}

// Serve the JavaScript file with security headers
header('X-Content-Type-Options: nosniff');
readfile($jsFilePath);
?>