<?php

class Database
{
    private $hostname;
    private $database;
    private $username;
    private $password;
    private $charset = "utf8";

    public function __construct()
    {
        // Detectar entorno
        $isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
                   strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

        if ($isLocal) {
            // XAMPP local
            $this->hostname = 'localhost';
            $this->database = 'u148394603_teamtalks';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Hostinger u otro hosting
            $this->hostname = 'localhost';
            $this->database = 'u148394603_teamtalks';
            $this->username = 'u148394603_teamtalks';
            $this->password = 'TeamTalks2901879';
        }
    }

    public function connect()
    {
        try {
            $conexion = "mysql:host={$this->hostname};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $pdo = new PDO($conexion, $this->username, $this->password, $options);

            $pdo->exec("SET time_zone = '-05:00'");

            return $pdo;
        } catch (PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }
}
