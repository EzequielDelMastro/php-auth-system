<?php

include 'db.php';
include 'response.php';

// Función para manejar el registro
function register($email, $password) {
    $db = getConnection();

    // Verificar si el email ya está registrado
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        jsonResponse(['error' => 'El correo electrónico ya está registrado'], 400);
    }

    // Encriptar la contraseña
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Generar un token único de verificación de correo
    $verificationToken = bin2hex(random_bytes(32));

    // Insertar el usuario en la base de datos con el token de verificación
    $stmt = $db->prepare("INSERT INTO users (email, password, verification_token, is_verified) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $hashedPassword, $verificationToken, 0]); // 0 indica que no está verificado

    // Obtener el ID del nuevo usuario insertado
    $userId = $db->lastInsertId();

    // Enviar el enlace de verificación al correo del usuario
    $verificationLink = "http://tu-dominio.com/verify-email?token=$verificationToken&user_id=$userId";
    sendVerificationEmail($email, $verificationLink);

    // Responder con un mensaje indicando que el correo ha sido enviado
    jsonResponse(['message' => 'Se ha enviado un correo para verificar tu dirección de correo electrónico.'], 200);
}

function sendVerificationEmail($email, $verificationLink) {
    // Aquí va el código para enviar el correo, por ejemplo usando PHPMailer
    $subject = "Verifica tu correo electrónico";
    $message = "Por favor, haz clic en el siguiente enlace para verificar tu correo electrónico: $verificationLink";
    
    mail($email, $subject, $message); // Solo un ejemplo, usa una librería como PHPMailer en producción
}


function verifyEmail($token, $user_id) {
    $db = getConnection();

    // Verificar si el token existe y es válido en la base de datos
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND verification_token = ?");
    $stmt->execute([$user_id, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Token de verificación inválido o usuario no encontrado'], 400);
    }

    // Activar el usuario (por ejemplo, cambiar el estado 'activo' en la base de datos)
    $updateStmt = $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $updateStmt->execute([$user_id]);

    // Limpiar el token de verificación (por seguridad)
    $clearTokenStmt = $db->prepare("UPDATE users SET verification_token = NULL WHERE id = ?");
    $clearTokenStmt->execute([$user_id]);

    // Responder al cliente
    jsonResponse(['message' => 'Correo electrónico verificado exitosamente. Ahora puedes iniciar sesión.'], 200);
}



// Función para manejar el login
function login($email, $password) {
    $db = getConnection();

    // Buscar al usuario por email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    // Verificar si el usuario está verificado
    if ($user['is_verified'] != 1) {
        jsonResponse(['error' => 'Por favor, verifica tu correo electrónico antes de iniciar sesión.'], 403);
    }

    // Verificar la contraseña
    if (!password_verify($password, $user['password'])) { // Usar password_verify
        jsonResponse(['error' => 'Contraseña incorrecta'], 401);
    }

    // Generar un OTP (One-Time Password) y guardarlo en la base de datos
    $otp = rand(100000, 999999); // Código de 6 dígitos
    $otpExpiration = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Válido por 5 minutos

    $updateStmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expiration = ? WHERE id = ?");
    $updateStmt->execute([$otp, $otpExpiration, $user['id']]);

    // Enviar el OTP por correo
    mail(
        $user['email'],
        "Tu código OTP de inicio de sesión",
        "Tu código es: $otp\nEste código expira en 5 minutos."
    );

    // Devolver una respuesta indicando que el OTP ha sido enviado
    jsonResponse([
        'message' => 'Login exitoso. Por favor, verifica tu código OTP enviado a tu correo.',
        'user_id' => $user['id']
    ], 200);
}


function generateOTP($userId) {
    $db = getConnection();

    // Generar un código aleatorio de 6 dígitos
    $otp = rand(100000, 999999);

    // Establecer la expiración del OTP (5 minutos desde ahora)
    $expiration = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Guardar el OTP y su expiración en la base de datos
    $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expiration = ? WHERE id = ?");
    $stmt->execute([$otp, $expiration, $userId]);

    // Enviar el OTP al usuario por correo (puedes usar PHPMailer o mail())
    $user = getUserById($userId); // Supongamos que tienes esta función
    mail($user['email'], "Tu código de verificación", "Tu código es: $otp. Expira en 5 minutos.");

    return ['message' => 'OTP enviado con éxito'];
}

function verifyOtp($otp, $user_id) {
    $db = getConnection();

    // Buscar al usuario por ID
    $stmt = $db->prepare("SELECT otp_code, otp_expiration FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    // Verificar si el OTP es válido y no ha expirado
    if ($user['otp_code'] !== $otp) {
        jsonResponse(['error' => 'OTP inválido'], 401);
    }

    if (strtotime($user['otp_expiration']) < time()) {
        jsonResponse(['error' => 'El OTP ha expirado'], 401);
    }

    // OTP válido, autenticar al usuario
    $token = base64_encode($userId . ':' . bin2hex(random_bytes(16)));
    $expiration = date('Y-m-d H:i:s', strtotime('+8 hour'));

    // Actualizar el token de acceso en la base de datos
    $updateStmt = $db->prepare("UPDATE users SET access_token = ?, token_expiration = ?, otp_code = NULL, otp_expiration = NULL WHERE id = ?");
    $updateStmt->execute([$token, $expiration, $user_id]);

    jsonResponse(['message' => 'Autenticación exitosa', 'token' => $token, 'expires_at' => $expiration], 200);
}




function updatePassword($email, $oldPassword, $newPassword, $token) {
    // Obtener la conexión a la base de datos
    $db = getConnection();

    // Buscar al usuario por email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    // Validar el token y su expiración
    if ($user['access_token'] !== "$token") {
    jsonResponse(['error' => 'Token inválido'], 401);
    }


    if (strtotime($user['token_expiration']) < time()) {
        jsonResponse(['error' => 'El token ha expirado'], 401);
    }

    // Verificar la contraseña actual
    if (!password_verify($oldPassword, $user['password'])) {
        jsonResponse(['error' => 'Contraseña actual incorrecta'], 401);
    }

    // Actualizar la contraseña con la nueva
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->execute([$hashedPassword, $email]);

    if ($updateStmt->rowCount() > 0) {
        jsonResponse(['message' => 'Contraseña actualizada correctamente'], 200);
    } else {
        jsonResponse(['error' => 'No se pudo actualizar la contraseña'], 500);
    }
}




function requestPasswordReset($email, $token) {
    // Obtener la conexión a la base de datos
    $db = getConnection();

    // Buscar al usuario por email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Credenciales inválidas'], 404);
    }

    // Validar el token y su expiración
    if (!hash_equals($user['access_token'], $token)) {
        jsonResponse(['error' => 'Credenciales inválidas'], 401);
    }

    if (strtotime($user['token_expiration']) < time()) {
        jsonResponse(['error' => 'El token ha expirado'], 401);
    }

    // Generar un token único para restablecimiento de contraseña
    $resetToken = bin2hex(random_bytes(32));
    $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar el token en la base de datos
    $updateStmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiration = ? WHERE email = ?");
    $updateStmt->execute([$resetToken, $expiration, $email]);

    if ($updateStmt->rowCount() > 0) {
        // Generar enlace para el restablecimiento
        $resetLink = "http://tu-dominio.com/reset-password?token=$resetToken";
        jsonResponse(['message' => 'Se ha enviado un correo para restablecer la contraseña.', 'resetLink' => $resetLink], 200);
    } else {
        jsonResponse(['error' => 'Error al generar el enlace de recuperación'], 500);
    }
}






function resetPassword($token, $newPassword) {
    

    // Conexión a la base de datos
    $db = getConnection();

    // Buscar al usuario por el token
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiration > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'Token inválido o expirado'], 400);
    }

    // Actualizar la contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiration = NULL WHERE reset_token = ?");
    $updateStmt->execute([$hashedPassword, $token]);

    if ($updateStmt->rowCount() > 0) {
        jsonResponse(['message' => 'Contraseña restablecida correctamente'], 200);
    } else {
        jsonResponse(['error' => 'No se pudo restablecer la contraseña'], 500);
    }
}


function validateToken($token) {
    $db = getConnection();

    // Buscar el token en la base de datos
    $stmt = $db->prepare("SELECT token_expiration FROM users WHERE access_token = ?");
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false; // Token no encontrado
    }

    // Verificar si el token ha expirado
    $currentTime = new DateTime();
    $expirationTime = new DateTime($result['token_expiration']);

    return $expirationTime > $currentTime;
}


function callback($code) {
    $db = getConnection();

if (isset($code)) {
    $code = $code;

    // Configuración
    $client_id = "398422448371-tecdirisu3arsecb181vnc10lsg5081d.apps.googleusercontent.com";
    $client_secret = "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";
    $redirect_uri = "http://suchinmeli.com.ar/WS/public/?route=callback";
    $token_url = "https://oauth2.googleapis.com/token";

    // Datos para el POST
    $post_data = [
        "code" => $code,
        "client_id" => $client_id,
        "client_secret" => $client_secret,
        "redirect_uri" => $redirect_uri,
        "grant_type" => "authorization_code",
    ];

    // Hacer la solicitud cURL
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    $response = curl_exec($ch);
    curl_close($ch);

    // Procesar la respuesta
    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Obtener información del usuario
        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=$access_token";
        $user_info = file_get_contents($user_info_url);
        $user_data = json_decode($user_info, true);

        // Mostrar la información del usuario
        echo "<pre>";
        print_r($user_data);
        echo "</pre>";
    } else {
        echo "Error obteniendo el token de acceso.";
    }
}


}