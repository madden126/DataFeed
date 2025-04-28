<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

$connectionParams = [
    'dbname'   => getenv('DB_DATABASE') ?: 'myapp',      // Default: myapp
    'user'     => getenv('DB_USERNAME') ?: 'user',       // Default: user
    'password' => getenv('DB_PASSWORD') ?: 'secret',     // Default: secret
    'host'     => getenv('DB_HOST') ?: 'db',             // Use 'db' for Docker container name
    'driver'   => 'pdo_mysql',                           // Or pdo_pgsql, etc.
    'port'     => getenv('DB_PORT') ?: 3306,             // Default MySQL port
    'charset'  => 'utf8mb4',
];

try {
    // Create and return the DBAL Connection
    return DriverManager::getConnection($connectionParams);
} catch (\Exception $e) {
    // Handle connection error appropriately in your application
    error_log("DBAL Connection Error: " . $e->getMessage());
    exit(1); // Exit or throw exception
}
