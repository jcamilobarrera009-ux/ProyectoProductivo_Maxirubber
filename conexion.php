<?php
// ================================================================
//  conexion.php
//  Archivo de conexión a la base de datos con PDO
//  XAMPP local: usuario root, contraseña vacía
// ================================================================

$host     = 'localhost';
$dbname   = 'maxirubber';
$usuario  = 'root';
$password = '';
$charset  = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $usuario,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}