<?php
// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require 'auth/jwtHelper.php'; // Archivo con funciones de JWT
require 'db/dbUsuarios.php'; // Archivo de conexión a la base de datos

// Instancia de la clase de acceso a datos
$db = new dbUsuarios();

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Manejo de la solicitud según el método HTTP
switch ($method) {
    case 'GET':  
        if (!validateToken()) {
            response(401, [
                'success' => false,
                'error' => 'invalidToken'
            ]);
        } else {
            handleGet($db);
        }
        break;
    case 'POST':
        handlePost($db);
        break;
    case 'PUT':
        if (!validateToken()) {
            response(401, [
                'success' => false,
                'error' => 'invalidToken'
            ]);
        } else {
            handlePut($db);
        }
        break;
    case 'DELETE':
        if (!validateToken()) {
            response(401, [
                'success' => false,
                'error' => 'invalidToken'
            ]);
        } else {
            handleDelete($db);
        }
        
        break;
    default:
        response(405, ['error' => 'Método no permitido']);
}

/**
 * Manejo de solicitudes GET
 */
function handleGet($db) {
    try {
        if (isset($_GET['id'])) {
            $user = $db->getUserById($_GET['id'] ?? null); 
            if ($user) {
                response(200, [
                    'success' => true,
                    'user' => $user
                ]);
            } else {
                response(404, [
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ]);
            }
        } else {
            response(400, [
                'success' => false,
                'error' => 'Debes proporcionar un ID'
            ]);
        }
    } catch (Exception $e) {
        response(500, [
            'success' => false,
            'error' => 'Error al obtener el usuario: ' . $e->getMessage()
        ]);
    }
}

/**
 * Manejo de solicitudes PUT
 */
function handlePut($db) {
    $data = getRequestData();
    $userId = getUserIdFromToken();

       
        if (isset($data['name'])) {
            $response = $db->updateUserName($userId ,$data['name']);

            if($response){
                response(200, [
                    'success' => true,
                    'message' => 'nameSuccessfulyUpdated'
                ]);
            }else{
                response(200, [
                    'success' => false,
                    'error' => 'nameNotUpdated'
                ]);
            }
        }

        if (isset($data['mail'])) {
            $response = $db->updateUserMail($userId ,$data['mail']);

            if($response){
                response(200, [
                    'success' => true,
                    'message' => 'mailSuccessfulyUpdated'
                ]);
            }else{
                response(200, [
                    'success' => false,
                    'error' => 'mailNotUpdated'
                ]);
            }
        } 

}

/**
 * Manejo de solicitudes POST (Registro de usuario o subida de imagen)
 */
function handlePost($db) {
    if (isset($_FILES['imagen'])) {
        handleImageUpload($db);
    } else {
        $data = getRequestData();
        
        // Validar datos requeridos
        if (!isset($data['nombre'], $data['email'], $data['contraseña'])) {
            response(400, ['error' => 'Faltan parámetros']);
        } else {
            try {
                $response = $db->registerUser($data['nombre'], $data['email'], $data['contraseña']);
                
                if ($response === true) {
                    // Respuesta en caso de éxito utilizando la función `response`
                    response(200, [
                        "success" => true,
                        "message" => "Usuario creado con éxito"
                    ]);
                } else {
                    // Respuesta en caso de error utilizando la función `response`
                    response(400, [
                        "success" => false,
                        "error" => $response['error']  // Extraemos el error devuelto desde la función registerUser
                    ]);
                }
            
            } catch (Exception $e) {
                // Respuesta en caso de una excepción inesperada
                response(500, [
                    "success" => false,
                    "error" => 'Error al procesar la solicitud: ' . $e->getMessage()
                ]);
            }
        }
    }
}

/**
 * Manejo de la subida de imágenes
 */
function handleImageUpload($db) {
    // Verifica si se recibió el archivo y el ID del usuario
    if (!isset($_FILES['imagen']) || !isset($_POST['id'])) {
        response(400, ['success' => false, 'error' => 'Falta la imagen o el ID del usuario.']);
    }

    $userId = $_POST['id'];
    $uploadDir = "uploads/profiles/$userId/";

    // Crear la carpeta si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Obtener la extensión del archivo
    $imageFileType = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));

    // Validar si el archivo es una imagen
    $check = getimagesize($_FILES["imagen"]["tmp_name"]);
    if ($check === false) {
        response(400, ['success' => false, 'error' => 'El archivo no es una imagen válida.']);
    }

    // Validar el tamaño del archivo (máximo 5 MB)
    if ($_FILES["imagen"]["size"] > 5000000) {
        response(400, ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB.']);
    }

    // Validar tipos de archivos permitidos
    $allowedExtensions = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowedExtensions)) {
        response(400, ['success' => false, 'error' => 'Solo se permiten imágenes JPG, JPEG, PNG y GIF.']);
    }

    // Definir el nombre de archivo (por ejemplo, "profile.jpg")
    $filePath = $uploadDir . "profile." . $imageFileType;

    // Mover la imagen a la carpeta del usuario
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $filePath)) {
        // Generar la URL pública de la imagen
        $imageUrl = "http://localhost/classbridgeapi/" . $filePath;

        // Guardar la URL en la base de datos
        $response = $db->updateUserImage($userId, $imageUrl);

        if($response){
            response(200, ['success' => true, 'message' => 'Imagen subida correctamente.', 'imageUrl' => $imageUrl]);
        }
        else{
            response(500, ['success' => false, 'error' => 'Error al insertar la url a la bd']);
        }

    } else {
        response(500, ['success' => false, 'error' => 'Error al mover el archivo.']);
    }
}

function handleImageDeletion($db, $userId) {
    // Definir la URL de la imagen predeterminada
    $defaultImageUrl = "http://localhost/classbridgeapi/uploads/profiles/000/profile.png";

    // Obtener la URL de la imagen desde la BD
    $imageUrl = $db->getUserImage($userId);

    // Si la imagen no existe en la BD, devolvemos error
    if (!$imageUrl) {
        response(404, ['success' => false, 'error' => 'No se encontró la imagen del usuario.']);
        return false;
    }

    // Si la imagen es la predeterminada, no la eliminamos
    if ($imageUrl === $defaultImageUrl) {
        response(400, ['success' => false, 'error' => 'No puedes eliminar la imagen predeterminada.']);
        return false;
    }

    // Convertir la URL a una ruta de archivo eliminando la parte del dominio
    $filePath = str_replace("http://localhost/classbridgeapi/", "", $imageUrl);


    // Eliminar la imagen del servidor si existe
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Actualizar la base de datos para que el usuario tenga la imagen predeterminada
    $resetImage = $db->updateUserImage($userId, $defaultImageUrl);

    if ($resetImage) {
        return true;
    } else {
        return false;
    }
}


/**
 * Manejo de solicitudes DELETE (Eliminar usuario)
 */
function handleDelete($db) {
    if (!isset($_GET["action"])) {
        response(200, ['success' => false, 'error' => 'Falta el action']);
        return;
    }

    if($_GET["action"] == "deleteImage"){
        $userId = getUserIdFromToken();

        // Llamamos a la función que elimina la imagen
        $deleted = handleImageDeletion($db, $userId);
    
        if ($deleted) {
            $newImageUrl = $db->getUserImage($userId);
            response(200, ['success' => true, 'message' => 'Imagen eliminada correctamente.', 'imageUrl' => $newImageUrl]);
        } else {
            response(200, ['success' => false, 'error' => 'Error al eliminar la imagen de la BD.']);
        }
    }else{
        response(200, ['success' => false, 'error' => 'NO VALID ACTION!!']);

    }


    
}

/**
 * Obtiene los datos de la solicitud en JSON, `x-www-form-urlencoded` o `multipart/form-data`
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
    
    response(400, ['success' => false, 'error' => 'Error al recibir los datos en el seridor: Formato de datos no soportado']);
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