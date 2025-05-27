<?php
session_start(); // Inicia la sesión

header('Access-Control-Allow-Origin: *'); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, X-Requested-With");
header("Access-Control-Allow-Credentials: true"); 

// Manejo de preflight OPTIONS request para el cors de navegadores como google
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Usamos la librería PHPMailer para enviar correos electrónicos
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../../auth/jwtHelper.php'; // Archivo propio con funciones utiles de JWT
require '../../db/dbCodigos-Verificacion.php'; // Archivo con la funcion de verificar la validez del token
require '../../db/dbUsuarios.php'; 
require '../../db/dbAulas.php'; 


// Si el token no es válido respondemos con un error 401
if (!validateToken()) {
    response(401, [
        'success' => false,
        'error' => 'invalidToken'
    ]);
} else {
    // Verificar el parámetro 'action' en el POST
    $data = getRequestData();
    if (!isset($data['action'])) {
        response(400, ['success' => false, 'error' => 'Falta el parámetro action']);
        exit;
    }
    
    // Según el action llamamos a la función correspondiente, si no corresponde con ninguna respondemos error 400
    switch ($data['action']) {
        case 'sendCode':
            sendCode();
            break;
        
        case 'verifyCode':
            verifyCode();
            break;

        case 'sendInfoMail':
            sendInfoMail($data['aula_name']);
            break;
        
        default:
            response(400, ['success' => false, 'error' => 'Acción no válida']);
    }
    
}

/**
 * Envía un correo electrónico al usuario notificándole
 * que su aula ya está lista para ser administrada.
 *
 * Utiliza PHPMailer para la configuración SMTP y envía, utilizamos brevo como SMTP.
 * un email HTML personalizado con los datos del usuario y el aula.
 */
function sendInfoMail($aulaName) {
    $dbAulas = new dbAulas();
    $db = new dbUsuarios();
    $userMail = "";
    
    $id_usuario = getUserIdFromToken(); // Obtener el ID del usuario de la solicitud utilizando jwtHelper
    $userInfo = $db->getUserById($id_usuario);
    $ipServer = gethostbyname(gethostname()); // Obtener la IP del servidor
    $AulaUrl = "https://classbridge.es/bridgeto/" . $aulaName; // URL del aula

    if (!$userInfo) {
        response(200, ['error' => 'Error al obtener los datos del usuario.']);
        return;
    } else {
        $userMail = $userInfo['email']; // Extraemos el email del usuario
    }

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';  
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com'; // Host SMTP de Brevo
        $mail->SMTPAuth = true;
        $mail->Username = '851f2e001@smtp-brevo.com'; // El correo asociado a la cuenta de Brevo
        $mail->Password = $_ENV['SMTP_KEY'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O SSL si usas el puerto 465
        $mail->Port = 587; // Puerto de Brevo

        // Configuración del correo
        $mail->setFrom('iesvda.izamar@gmail.com', 'class-bridge'); 
        $mail->addAddress($userMail, 'user'); 
        $mail->Subject = '¡Tu Aula ya está lista para ser administrada!';
        $mail->addEmbeddedImage('imgs/text-logo.png', 'logoImg', 'text-logo.png');
        $mail->Body = '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        color: #333333;
                        background-color: #f9f9f9;
                        margin: 0;
                        padding: 0;
                    }
                    .email-container {
                        width: 100%;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #ffffff;
                        border-radius: 8px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    }
                    .email-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .email-header img {
                        max-width: 180px;
                        border-radius: 10px;
                    }
                    .email-body {
                        padding: 20px;
                        text-align: center;
                    }
                    .verification-code {
                        font-size: 24px;
                        font-weight: bold;
                        color: #feb47b;
                        background-color: #f1f1f1;
                        padding: 10px;
                        border-radius: 5px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        font-size: 12px;
                        color: #777777;
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="email-header">
                        <img src="cid:logoImg" alt="Logo Class-Bridge">
                    </div>
                    <div class="email-body">
                        <h2>¡Hola, '.$userInfo['nombre'].'!</h2>
                        <p>¡Muchas gracias por confiar en <strong>Class-Bridge</strong>!</p>
                        <p>Tu aula "<strong>'.$aulaName.'</strong>" ya está lista para ser administrada.</p>
                        <p>Puedes acceder a tu aula desde el siguiente enlace:</p>
                        <p><a href="'.$AulaUrl.'" target="_blank">'.$AulaUrl.'</a></p>
                        <p>¡Estamos muy emocionados de que empieces a crear tus primeros alumnos y cursos!</p>
                    </div>
                    <div class="footer">
                        <p>Si no solicitaste este correo, por favor ignóralo.</p>
                        <p>&copy; '.date('Y').' Class-Bridge. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>';

        $mail->isHTML(true);

        // Enviar el correo
        $mail->send();
        response(200, ['success' => true, 'message' => 'Correo enviado con éxito.']);

    } catch (Exception $e) {
        response(200, ['success' => false, 'error' => 'El correo no se ha podido enviar.']);
    }
}



/**
 * Envía un correo electrónico al usuario con un codigo de verificacion que se crea en el servidor
 */
function sendCode() {

    $data = getRequestData();

    $db = new dbCodigosVerificacion();

    $verificationCode = rand(100000, 999999);

    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $id_usuario = getUserIdFromToken(); // Obtenemos el id del usuario

    $insertResult = $db->insertarCodigo($id_usuario, $verificationCode, $expiresAt);

    if (!$insertResult) {
        response(200, ['error' => 'Error al guardar el código de verificación en la base de datos.']);
    }
    if (!isset($data['email'])) {
        response(200, ['error' => 'Falta el parametro email']);
    } else {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com'; // Host SMTP de Brevo
            $mail->SMTPAuth = true;
            $mail->Username = '851f2e001@smtp-brevo.com'; 
            $mail->Password = $_ENV['SMTP_KEY'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O SSL si usas el puerto 465
            $mail->Port = 587; // Puerto de Brevo

            // Configuración del correo
            $mail->setFrom('iesvda.izamar@gmail.com', 'class-bridge'); 
            $mail->addAddress($data['email'], 'user'); 
            $mail->Subject = 'Codigo de verificacion de classBridge';
            $mail->addEmbeddedImage('imgs/text-logo.png', 'logoImg', 'text-logo.png');
            $mail->Body = '
                <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            color: #333333;
                            background-color: #f9f9f9;
                            margin: 0;
                            padding: 0;
                        }
                        .email-container {
                            width: 100%;
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                        }
                        .email-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        .email-header img {
                            max-width: 180px;
                            border-radius: 10px;
                        }
                        .email-body {
                            padding: 20px;
                            text-align: center;
                        }
                        .verification-code {
                            font-size: 24px;
                            font-weight: bold;
                            color: #feb47b;
                            background-color: #f1f1f1;
                            padding: 10px;
                            border-radius: 5px;
                        }
                        .footer {
                            text-align: center;
                            margin-top: 20px;
                            font-size: 12px;
                            color: #777777;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="email-header">
                            <img src="cid:logoImg" alt="Logo Class-Bridge">
                        </div>
                        <div class="email-body">
                            <h2>¡Hola!</h2>
                            <p>Tu c&oacute;digo de verificaci&oacute;n es:</p>
                            <div class="verification-code">
                                '.$verificationCode.'  <!-- Insertar el código generado -->
                            </div>
                            <p>Usa este c&oacute;digo para completar la verificaci&oacute;n en <strong>Class-Bridge</strong>.</p>
                        </div>
                        <div class="footer">
                            <p>Si no solicitaste este correo, por favor ign&oacute;ralo.</p>
                            <p>&copy; '.date('Y').' Class-Bridge. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>';

            $mail->isHTML(true);

            // Enviar el correo
            $mail->send();
            response(200, ['success' => true, 'message' => 'Correo enviado con éxito.']);

        } catch (Exception $e) {
            response(200, ['success' => false, 'error' => 'El correo no se ha podido enviar.']);
        }
    }
}

// Función para verificar el código de verificación, 
function verifyCode() {
    $data = getRequestData();

    if (!isset($data['verificationCode'])) {
        response(200, ['error' => 'Falta el parámetro verificationCode']);
    }

    // Conectar a la base de datos
    $db = new dbCodigosVerificacion();

    // Buscar el código de verificación en la base de datos
    $result = $db->obtenerCodigoPorUsuario(getUserIdFromToken());

    if (!$result) {
        response(200, ['error' => 'No se encontró un código de verificación para este usuario']);
    }

    // Verificar si el código ha expirado
    $expirationTime = strtotime($result['expiracion']);
    $currentTime = time();

    if ($currentTime > $expirationTime) {
        // Si ha expirado, eliminamos el código
        $db->eliminarCodigo(getUserIdFromToken());
        response(200, ['error' => 'El código de verificación ha expirado']);
    }

    // Verificar si el código ingresado por el usuario es el correcto
    if ((int)$result['codigo_verificacion'] === (int)$data['verificationCode']) {
        // Si el email es correcto eliminamos el codigo de la bd
        $db->eliminarCodigo(getUserIdFromToken());
        response(200, ['success' => true, 'message' => 'Código de verificación válido.']);
    } else {
        response(200, [
            'success' => false, 
            'error' => 'Código de verificación incorrecto. Código ingresado: ' . $data['verificationCode']
        ]);
    }
}


/**
 * Envía una respuesta JSON con el código de estado correspondiente
 */
function response($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


/**
 * Devuelve los datos de la solicitud en función del tipo de contenido
 */
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        parse_str(file_get_contents('php://input'), $data);
        return $data;
    } elseif (strpos($contentType, 'multipart/form-data') !== false) {
        return $_POST;
    }
    
    response(400, ['success' => false, 'error' => 'Error al recibir los datos en el servidor: Formato de datos no soportado']);
}
?>
