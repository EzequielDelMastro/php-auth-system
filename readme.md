# PampaCode - 2025
# Guía de Uso de la API

Este documento detalla el uso de la API que permite gestionar usuarios y realizar operaciones de autenticación. Sigue las instrucciones a continuación para integrarla y utilizarla correctamente.

## Configuración Inicial

### Esquema Tabla BD

CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `password` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `access_token` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    `token_expiration` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    `reset_token` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    `reset_token_expiration` DATETIME DEFAULT NULL,
    `verification_token` VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    `is_verified` TINYINT(4) DEFAULT NULL,
    `otp_code` VARCHAR(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    `otp_expiration` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


### Entorno de Desarrollo
- La API incluye un entorno de desarrollo y producción.
- Configura el entorno cambiando la constante `ENV` en el archivo `index.php`:
  ```php
  define('ENV', 'development'); // Cambiar a 'production' para producción
  ```

### Requisitos
- PHP >= 7.4
- Extensión PDO habilitada
- Servidor configurado para manejar solicitudes HTTP (Apache o NGINX)

### Instalación
1. Clona este repositorio o descárgalo como un archivo ZIP.
2. Configura tu base de datos e importa el esquema requerido.
3. Configura la conexión a la base de datos en el archivo `db.php`.

## Autenticación y Rutas

La API incluye varias rutas para manejar operaciones como registro, inicio de sesión y gestión de contraseñas. Todas las respuestas son en formato JSON.

### Cabeceras Comunes
Asegúrate de incluir las siguientes cabeceras en tus solicitudes:
- `Content-Type: application/json`
- `Authorization: Bearer <token>` (cuando se requiera autenticación)

### Endpoints Disponibles

#### Registro de Usuario
- **Ruta:** `/register`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "email": "correo@ejemplo.com",
    "password": "contraseña123"
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "message": "Se ha enviado un correo para verificar tu dirección de correo electrónico."
  }
  ```

#### Verificación de Correo Electrónico
- **Ruta:** `/verify-email?token=<token>&user_id=<id>`
- **Método:** GET
- **Respuesta exitosa:**
  ```json
  {
    "message": "Correo electrónico verificado exitosamente. Ahora puedes iniciar sesión."
  }
  ```

#### Inicio de Sesión
- **Ruta:** `/login`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "email": "correo@ejemplo.com",
    "password": "contraseña123"
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "token": "JWT_Autenticación",
    "message": "Inicio de sesión exitoso."
  }
  ```

#### Actualización de Contraseña
- **Ruta:** `/update-password`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "email": "correo@ejemplo.com",
    "oldPassword": "contraseñaAntigua",
    "newPassword": "nuevaContraseña123",
    "token": "JWT_Autenticación"
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "message": "Contraseña actualizada exitosamente."
  }
  ```

#### Solicitar Restablecimiento de Contraseña
- **Ruta:** `/request-password-reset`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "email": "correo@ejemplo.com"
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "message": "Se ha enviado un enlace para restablecer tu contraseña."
  }
  ```

#### Restablecer Contraseña
- **Ruta:** `/reset-password`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "token": "Token_De_Restablecimiento",
    "newPassword": "nuevaContraseña123"
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "message": "Contraseña restablecida exitosamente."
  }
  ```

#### Verificar OTP
- **Ruta:** `/verify-otp`
- **Método:** POST
- **Parámetros del cuerpo:**
  ```json
  {
    "otp": "123456",
    "user_id": 1
  }
  ```
- **Respuesta exitosa:**
  ```json
  {
    "message": "OTP verificado correctamente."
  }
  ```

## Manejo de Errores
Las respuestas de error tienen el siguiente formato:
```json
{
  "error": "Descripción del error"
}
```

## Notas Adicionales
- Usa librerías como `Postman` o `cURL` para probar la API.
- Implementa HTTPS en producción para garantizar la seguridad.
- Para el envío de correos, utiliza una librería robusta como PHPMailer o similar.

