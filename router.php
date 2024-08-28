<?php

// Define the routes
$routes = [
    '/' => 'index.php',
    '/translate' => [
        'GET' => '405.php',
        'POST' => __DIR__ . '/app/translate.php'
    ],
    '/translate-file' => [
        'GET' => '405.php',
        'POST' => __DIR__ . '/app/translate-file.php'
    ],
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Define the file extensions that should bypass routing
$staticFileExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'json', 'xml', 'txt', 'pdf', 'doc', 'docs', 'docx', 'pdf', 'csv', 'jpg', 'png', 'jpeg', 'gif', 'css', 'js', 'html', 'socs'];
$fileExtension = pathinfo($path, PATHINFO_EXTENSION);
// Check if the requested URL has a static file extension
if (in_array($fileExtension, $staticFileExtensions)) {
    // Serve the file directly
    $staticFilePath = __DIR__ . $path;
    if (file_exists($staticFilePath) && is_file($staticFilePath)) {
        header('Content-Type: ' . mime_content_type($staticFilePath));
        readfile($staticFilePath);
    } else {
        http_response_code(404);
        echo "404 - File Not Found";
    }
    exit;
}

// Check if the route exists and if the method is allowed
if (array_key_exists($path, $routes)) {
    if (is_array($routes[$path])) {
        if (array_key_exists($method, $routes[$path])) {
            require $routes[$path][$method];
        } else {
            http_response_code(405);
            echo "405 - Method Not Allowed";
        }
    } else {
        require $routes[$path];
    }
} else {
    // Handle 404 - Not Found
    http_response_code(404);
    echo "404 - Not Found";
}
