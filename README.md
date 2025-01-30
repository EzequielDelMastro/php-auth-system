# php-auth-system - PampaCode - 2025
sistema de autenticación, PHP, seguridad, registro de usuarios, recuperación de contraseñas, verificación por correo, OTP, autenticación segura, API, integración rápida


# Guía de Uso de la API


Este documento detalla el uso de la API que permite gestionar usuarios y realizar operaciones de autenticación. Sigue las instrucciones para integrarla y utilizarla correctamente.

## Configuración Inicial

### Esquema Tabla BD

CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    password VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    access_token VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    token_expiration DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    reset_token_expiration DATETIME DEFAULT NULL,
    verification_token VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    is_verified TINYINT(4) DEFAULT NULL,
    otp_code VARCHAR(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    otp_expiration DATETIME DEFAULT NULL,
    provider VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    provider_id VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    picture_profile VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
    INDEX (email)
);




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

# Registro con Google OAuth en PHP

Este sistema permite a los usuarios registrarse utilizando su cuenta de Google mediante OAuth 2.0.

## Configuración

1. **Obtener credenciales de Google**  
   - Ve a [Google Cloud Console](https://console.cloud.google.com/).
   - Crea un nuevo proyecto o selecciona uno existente.
   - Habilita la API de Google Identity Platform.
   - Crea credenciales OAuth 2.0 y obtén tu `Client ID` y `Client Secret`.
   - Configura el `redirect URI` en **Autorización OAuth 2.0** (ejemplo: `https://tudominio.com/google/register/index.php`).

2. **Configurar las credenciales en el código**  
   En el archivo `googlekeysregister.php`, agrega:

   ```php
   <?php
   $client_id = "TU_CLIENT_ID";
   $client_secret = "TU_CLIENT_SECRET";
   $redirect_uri = "TU_REDIRECT_URI";
   $token_url = "https://oauth2.googleapis.com/token";
   ?> 

 Iniciar el proceso de autenticación
        El usuario es redirigido a Google para iniciar sesión.
        Al autorizar la aplicación, se obtiene un código de autenticación.

 Intercambio del código por un token de acceso
        Se realiza una solicitud a Google para obtener un access_token.
        Con este token, se obtiene la información del usuario (email, nombre, foto, etc.).

 Registro en la base de datos
        Se almacena el email, el ID de Google como contraseña encriptada, el estado de verificación y la foto de perfil.

Modifica las credenciales en googlekeysregister.php.

# Inicio de Sesión con Google OAuth en PHP

Este sistema permite a los usuarios iniciar sesión con su cuenta de Google mediante OAuth 2.0.

## Configuración

### 1. Obtener credenciales de Google  
   - Accede a [Google Cloud Console](https://console.cloud.google.com/).
   - Crea un nuevo proyecto o selecciona uno existente.
   - Habilita la API de Google Identity Platform.
   - Crea credenciales OAuth 2.0 y obtén tu `Client ID` y `Client Secret`.
   - Configura el `redirect URI` en **Autorización OAuth 2.0** (ejemplo: `https://tudominio.com/google/login/auth/index.php`).

### 2. Configurar las credenciales en el código  
   En el archivo `googlekeyslogin.php`, agrega:

  
   
   ```php
   <?php
   $client_id = "TU_CLIENT_ID";
   $client_secret = "TU_CLIENT_SECRET";
   $redirect_uri = "TU_REDIRECT_URI";
   $token_url = "https://oauth2.googleapis.com/token";
   ?> 
```



3. Iniciar el proceso de autenticación

    El usuario es redirigido a Google para iniciar sesión.
    Si el usuario autoriza la aplicación, Google devuelve un código de autorización.

4. Intercambio del código por un token de acceso

    Se realiza una solicitud a Google para obtener un access_token.
    Con este token, se obtiene la información del usuario (email, ID, foto de perfil, etc.).

5. Validación del usuario en la base de datos

    Se busca el email del usuario en la base de datos.
    Si el usuario no está registrado, se muestra un error.
    Si el usuario está registrado pero no ha verificado su correo, se muestra un mensaje de verificación pendiente.
    Si todo está correcto, se genera un token de sesión válido por 8 horas.


## Notas Adicionales
- Usa librerías como `Postman` o `cURL` para probar la API.
- Implementa HTTPS en producción para garantizar la seguridad.
- Para el envío de correos, utiliza una librería robusta como PHPMailer o similar.

