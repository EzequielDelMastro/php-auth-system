<?php
define('ENV', 'development'); // Cambiar a 'production' cuando sea necesario

if (ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}


header("Access-Control-Allow-Origin: *"); // Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Headers: Authorization, Content-Type"); // Permitir el header Authorization

// Incluir las funciones de autenticación desde el archivo auth.php
include '../src/auth.php';

// Obtener la ruta solicitada desde la URL (por ejemplo, ?route=login)
// Obtener la ruta solicitada desde la URL (por ejemplo, ?route=login)
$route = trim($_GET['route'] ?? ''); // Eliminar espacios y saltos de línea
// Obtener el método HTTP de la solicitud (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// Obtener el token desde la cabecera
$headers = apache_request_headers();
$token = $headers['Authorization'] ?? null; // Obtener el token desde la cabecera

/*
$headers = getallheaders();
$token = $headers['Authorization'] ?? null; // Obtener el token desde la cabecera
*/

/*
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
    }
}
$token = $headers['Authorization'] ?? null; // Obtener el token desde la cabecera
*/


// Verificar rutas y métodos disponibles
if ($route === 'register' && $method === 'POST') {
    // Registro de usuario, no requiere token
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;

    if (!$email || !$password) {
        jsonResponse(['error' => 'Email y contraseña son requeridos'], 400);
    }

    register($email, $password);
} elseif ($route === 'login' && $method === 'POST') {
    // Login de usuario, no requiere token
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;

    if (!$email || !$password) {
        jsonResponse(['error' => 'Email y contraseña son requeridos'], 400);
    }

    login($email, $password);
} 

elseif ($route === 'update-password' && $method === 'POST') {
    // Actualización de contraseña, requiere token
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    $oldPassword = $input['oldPassword'] ?? null;
    $newPassword = $input['newPassword'] ?? null;
    $token = $input['token'] ?? null;

    if (!$email || !$oldPassword || !$newPassword) {
        jsonResponse(['error' => 'Email, contraseña actual y nueva contraseña son requeridos'], 400);
    }

    updatePassword($email, $oldPassword, $newPassword, $token);

}
 elseif ($route === 'request-password-reset' && $method === 'POST') {
    // Solicitar restablecimiento de contraseña, requiere token
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    $token = $input['token'] ?? null;

    if (!$email) {
        jsonResponse(['error' => 'El email es requerido'], 400);
    }

    requestPasswordReset($email, $token);
} elseif ($route === 'reset-password' && $method === 'POST') {
    // Restablecer la contraseña, requiere token
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? null;
    $newPassword = $input['newPassword'] ?? null;

    if (!$token || !$newPassword) {
        jsonResponse(['error' => 'Token y nueva contraseña son requeridos'], 400);
    }

    resetPassword($token, $newPassword);
}

elseif ($route === 'verify-email' && $method === 'GET') {
    // Verificar el correo electrónico con el token de verificación
    $token = $_GET['token'] ?? null; // Obtener el token de la URL
    $user_id = $_GET['user_id'] ?? null; // Obtener el ID del usuario de la URL

    if (!$token || !$user_id) {
        jsonResponse(['error' => 'Token y ID de usuario son requeridos'], 400);
    }

    verifyEmail($token, $user_id); // Llamar a la función para verificar el correo
}

elseif ($route === 'verify-otp' && $method === 'POST') {
    // Verificar el OTP después del login
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = $input['otp'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$otp || !$user_id) {
        jsonResponse(['error' => 'OTP y ID de usuario son requeridos'], 400);
    }

    verifyOtp($otp, $user_id); // Llamar a la función para verificar el OTP
}


 else {
    jsonResponse(['error' => 'Ruta no encontrada'], 404);
}
