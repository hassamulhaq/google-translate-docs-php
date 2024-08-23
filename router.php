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
