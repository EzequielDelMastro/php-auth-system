<?php
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Configuración
    include '../../../googlekeysregister.php';

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
        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=$access_token";
        $user_info = file_get_contents($user_info_url);
        $user_data = json_decode($user_info, true);
        
        // Asignar los valores a las variables
        $user_data_id = $user_data['id'];
        $user_data_password = password_hash($user_data_id, PASSWORD_BCRYPT);
        $user_data_mail = $user_data['email'];
        $user_data_verified_email = $user_data['verified_email'];
        $user_data_picture = $user_data['picture'];  // Para obtener la imagen de perfil
        $user_data_provider = "Google";

        include '../../db.php';

        $db = getConnection();
        
        $stmt = $db->prepare("INSERT INTO users (email, password, is_verified, provider, provider_id, picture_profile) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_data_mail, $user_data_password, $user_data_verified_email, $user_data_provider, $user_data_id, $user_data_picture]); // 0 indica que no está verificado

        // Obtener el ID del nuevo usuario insertado
        $userId = $db->lastInsertId();

        // Mostrar los datos completos para depuración
        jsonResponse(['message' => 'Registro Exitoso', 'email' => $user_data_mail, 'password' => $user_data_password, 'is_verified' => $user_data_verified_email, 'provider' => $user_data_provider, 'provider_id' => $user_data_id, 'picture_profile' => $user_data_picture], 200);

        // redirigir a otra pantalla
        // proximamente
    } else {
        echo "Error obteniendo el token de acceso.";
    }
} else {
    echo "No se recibió ningún código de autorización.";
}

// Función para responder en JSON
function jsonResponse($data, $status = 200) {
    header("Content-Type: application/json");
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>




