<?php
header('Access-Control-Allow-Origin: *'); // Cambia '*' por la URL exacta
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, X-Requested-With");
header("Access-Control-Allow-Credentials: true"); // Agregar para sesiones y cookies

// Manejo de preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start(); // Debe ir después de los headers CORS
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Si usaste Composer
require '../../auth/jwtHelper.php'; // Archivo con funciones de JWT

if (!validateToken()) {
    response(401, [
        'success' => false,
        'error' => 'invalidToken'
    ]);
} else {
    sendCode();
}

function sendCode() {
        $data = getRequestData();
        
        if (!isset($data['email'])) {
            response(400, ['error' => 'Falta el parametro email']);
        } 
        else{
            $mail = new PHPMailer(true);
            $verificationCode = rand(100000, 999999);
            $_SESSION['verification-code'] = $verificationCode; // Guardar en la sesión

            try {
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
                $mail->Subject = 'Código de verificacion de classBridge';
                $mail->Body = "¡Hola! Tu código de verificacion es: <b>$verificationCode</b>. Úsalo para completar tu registro.";
                $mail->isHTML(true);

                // Enviar el correo
                $mail->send();
                response(200, ['success' => true, 'message' => 'Correo enviado con éxito.']);

            } catch (Exception $e) {
                response(200, ['success' => false, 'error' => 'El correo no se ha podido enviar.']);
            }
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
    
    response(400, ['success' => false, 'error' => 'Error al recibir los datos en el seridor: Formato de datos no soportado']);
}

?>
