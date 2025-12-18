<?php
require 'vendor/autoload.php';

// Load .env
$_ENV = [];
if (file_exists('.env')) {
    $dotenv = new \Symfony\Component\Dotenv\Dotenv();
    $dotenv->loadEnv('.env');
}

// Boot kernel
$kernel = new App\Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? true);
$kernel->boot();

// Get router and generate paths
$router = $kernel->getContainer()->get('router');

echo "app_category_index path: " . $router->generate('app_category_index') . "\n";
echo "app_product_index path: " . $router->generate('app_product_index') . "\n";
echo "app_admin_order_index path: " . $router->generate('app_admin_order_index') . "\n";
?>
