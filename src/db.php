<?php

// Configuración de la base de datos
$config = [
    'host' => '', // Cambiar al host de tu servidor
    'database' => '', // Cambiar por el nombre de tu base de datos
    'user' => '', // Cambiar por el usuario de tu base de datos
    'password' => '' // Cambiar por la contraseña del usuario
];



// Crear la conexión a la base de datos usando PDO
function getConnection() {
    global $config; // Usar la variable global $config

    try {
        // Crear una instancia de PDO
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8",
            $config['user'],
            $config['password']
        );

        // Configurar el manejo de errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo; // Retornar la conexión
    } catch (PDOException $e) {
        // Mostrar error si no se puede conectar
        die("Error al conectar a la base de datos: " . $e->getMessage());
    }
}
