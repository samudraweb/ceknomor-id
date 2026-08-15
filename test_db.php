<?php
require_once __DIR__ . '/config/database.php';
$start = microtime(true);
$db = new PDO("mysql:host=127.0.0.1;port=3307;dbname=ceknomor", "root", "", [PDO::ATTR_PERSISTENT => true]);
echo "Connected in " . (microtime(true) - $start) . " seconds";
