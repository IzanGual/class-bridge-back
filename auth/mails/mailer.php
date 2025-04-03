<?php
session_start(); // Inicia la sesión

header('Access-Control-Allow-Origin: *'); // Cambia '*' por la URL exacta
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, X-Requested-With");
header("Access-Control-Allow-Credentials: true"); // Agregar para sesiones y cookies

// Manejo de preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Si usaste Composer
require '../../auth/jwtHelper.php'; // Archivo con funciones de JWT
require '../../db/dbCodigos-Verificacion.php'; // Archivo con funciones de JWT
require '../../db/dbUsuarios.php'; 
require '../../db/dbAulas.php'; 



// Comprobamos si el token de sesión es válido (si corresponde)
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
    
    switch ($data['action']) {
        case 'sendCode':
            sendCode();
            break;
        
        case 'verifyCode':
            verifyCode();
            break;

        case 'sendInfoMail':
            sendInfoMail();
            break;
        
        default:
            response(400, ['success' => false, 'error' => 'Acción no válida']);
    }
    
}


function sendInfoMail() {
    $dbAulas = new dbAulas();
    $db = new dbUsuarios();
    $userMail = "";
    
    $id_usuario = getUserIdFromToken(); // Obtener el ID del usuario de la solicitud
    $userInfo = $db->getUserById($id_usuario);
    $aula = $dbAulas->getAulaById($id_usuario);
    $aulaName = $aula['nombre'];

    $AulaUrl = "http://localhost:3000/bridgeto/" . $aulaName; // URL del aula

    if (!$userInfo) {
        response(200, ['error' => 'Error al obtener los datos del usuario.']);
        return;
    } else {
        $userMail = $userInfo['email']; // Extraemos el email del usuario
    }

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';  // Asegúrate de colocar esto primero
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com'; // Host SMTP de Brevo
        $mail->SMTPAuth = true;
        $mail->Username = '851f2e001@smtp-brevo.com'; // El correo asociado a tu cuenta Brevo
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



// Función para enviar el código de verificación
function sendCode() {

    $data = getRequestData();

    $db = new dbCodigosVerificacion();

    $verificationCode = rand(100000, 999999);

    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $id_usuario = getUserIdFromToken(); // Aquí debes obtener el ID del usuario de la solicitud

    $insertResult = $db->insertarCodigo($id_usuario, $verificationCode, $expiresAt);

    if (!$insertResult) {
        response(200, ['error' => 'Error al guardar el código de verificación en la base de datos.']);
    }
    if (!isset($data['email'])) {
        response(200, ['error' => 'Falta el parametro email']);
    } else {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';  // Asegúrate de colocar esto primero
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com'; // Host SMTP de Brevo
            $mail->SMTPAuth = true;
            $mail->Username = '851f2e001@smtp-brevo.com'; // El correo asociado a tu cuenta Brevo
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
                            <p>Usa este c&oacute;digo para completar tu registro en <strong>Class-Bridge</strong>.</p>
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

// Función para verificar el código de verificación
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
        // El código es correcto, eliminamos el código de la base de datos (opcional)
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
