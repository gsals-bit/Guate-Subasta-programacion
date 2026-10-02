<?php
class Database {
    public static function connect(): PDO {
        $host = 'localhost';
        $db   = 'guate_subasta';
        $user = 'root';
        $pass = '';
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
