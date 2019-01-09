<?php

class Connection
{
    public static function get() : PDO
    {
        $pdo = new PDO("sqlite::memory:", null, null, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);
        $pdo->exec("CREATE TABLE `watchdog_logs` 
            (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `action` VARCHAR(200) NOT NULL,
              `session_id` VARCHAR(512),
              `ip_address` VARCHAR(40),
              `type` VARCHAR(100) NOT NULL,
              `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
        return $pdo;
    }
}