<?php
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    include '../../../../googlekeyslogin.php';

    // Datos para la solicitud POST
    $post_data = [
        "code" => $code,
        "client_id" => $client_id,
        "client_secret" => $client_secret,
        "redirect_uri" => $redirect_uri,
        "grant_type" => "authorization_code",
    ];

    // Hacer la solicitud cURL para obtener el token
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
        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=$access_token";
        $user_info = file_get_contents($user_info_url);
        $user_data = json_decode($user_info, true);

        // Asignar los valores obtenidos
        $user_data_id = $user_data['id'];
        $user_data_password = password_hash($user_data_id, PASSWORD_BCRYPT);
        $user_data_mail = $user_data['email'];
        $user_data_verified_email = $user_data['verified_email'];
        $user_data_picture = $user_data['picture'];
        $user_data_provider = "Google";

        // Incluir la conexión a la base de datos
        include '../../../db.php';
        $db = getConnection();

        // Buscar al usuario por email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$user_data_mail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        $userId = $user['id']; // Obtener el ID del usuario

        // Verificar si el usuario está verificado
        if ($user['is_verified'] != 1) {
            jsonResponse(['error' => 'Por favor, verifica tu correo electrónico antes de iniciar sesión.'], 403);
        }

        // Verificar la contraseña correctamente
        if (!password_verify($user_data_id, $user['password'])) { // La condición corregida
            jsonResponse(['error' => 'Contraseña incorrecta'], 401);
        }

        // Generar token de autenticación
        $token = base64_encode($userId . ':' . bin2hex(random_bytes(16)));
        $expiration = date('Y-m-d H:i:s', strtotime('+8 hour'));

        // Actualizar el token en la base de datos
        $updateStmt = $db->prepare("UPDATE users SET access_token = ?, token_expiration = ?, otp_code = NULL, otp_expiration = NULL WHERE id = ?");
        $updateStmt->execute([$token, $expiration, $userId]);

        // Responder con éxito
        jsonResponse(['message' => 'Autenticación exitosa', 'token' => $token, 'expires_at' => $expiration], 200);

    } else {
        jsonResponse(['error' => 'Error de acceso.'], 400);
    }
} else {
    jsonResponse(['error' => 'No se recibió ningún código de autorización.'], 400);
}

// Función para responder en JSON
function jsonResponse($data, $status = 200) {
    header("Content-Type: application/json");
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>
 