<?php

// Función para enviar una respuesta JSON
function jsonResponse($data, $status = 200) {
    // Establecer el código de respuesta HTTP
    http_response_code($status);

    // Configurar el encabezado para JSON
    header('Content-Type: application/json');

    // Convertir el array o objeto a JSON y enviarlo
    echo json_encode($data);

    exit();
}
