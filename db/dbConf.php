<?php
require __DIR__ . '/../auth/vendor/autoload.php'; // Cargar Composer desde auth/

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

return [
    'db_host' => $_ENV['DB_HOST'],
    'db_name' => $_ENV['DB_NAME'],
    'db_user' => $_ENV['DB_USER'],
    'db_pass' => $_ENV['DB_PASS']
];
