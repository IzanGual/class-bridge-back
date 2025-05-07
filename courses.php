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

require 'db/dbCursos.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbCursos();

$method = $_SERVER['REQUEST_METHOD'];
if (!validateToken()) {
    response(200, [
        'success' => false,
        'error' => 'invalidToken'
    ]);
} else {
    // Manejo de la solicitud según el método HTTP
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            response(405, ['error' => 'Método no permitido']);
    }
}

/**
 * Manejo de solicitudes GET
 */
function handleGet($db) {
    try {
       
        if (isset($_GET['getUsersByCourse_id'])) {
            $users = $db->getUsersByCourse_id($_GET['getUsersByCourse_id']);
            if ($users) {
                response(200, [
                    'success' => true,
                    'users' => $users
                ]);
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'No hay usuarios en este curos'
                ]);
            }
            return; 
        }

       
        if (isset($_GET['aula_id'])) {
            $courses = $db->getOwnCourses($_GET['aula_id']);
            if ($courses) {
                response(200, [
                    'success' => true,
                    'courses' => $courses
                ]);
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'No se encontraron cursos para el aula'
                ]);
            }
            return; 
        }

        
        response(400, [
            'success' => false,
            'error' => 'Debes proporcionar un parámetro válido (getUsersByCourse_id o aula_id)'
        ]);
    } catch (Exception $e) {
        response(500, [
            'success' => false,
            'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
}



/**
 * Manejo de solicitudes POST (Registro de usuario o subida de imagen)
 */
function handlePost($db) {

    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion === 'uploadCourse') {
            if (isset($_FILES['imagen'])) {
                $imageUrl = handleImageUpload($db);
                if (!$imageUrl) {
                    response(500, ['success' => false, 'error' => 'Error al subir la imagen.']);
                    return;
                }
            }

            

            // Verificar que se envíen los demás datos necesarios
            if (!isset($_POST['id'], $_POST['courseName'], $_POST['courseUsers'])) {
                response(400, ['success' => false, 'error' => 'Faltan datos del curso']);
                return;
            }


            // Llamar al método que actualiza el curso
            $success = $db->uploadCourse($_POST['id'], $_POST['courseName'], $_POST['courseUsers']);

            if ($success) {
                response(200, [
                    "success" => true,
                    "message" => "Curso actualizado correctamente"
                ]);
            } else {
                response(500, ["success" => false, "error" => "Error al actualizar el curso en la base de datos."]);
            }
            return;
        }
    }
    else{
        response(500, ["success" => false, "error" => "Debes proporcionar accion"]);
    }
}

/**
 * Manejo de solicitudes PUT (Actualizar usuario)
 */
function handlePut($db) {
    response(500, ['error' => 'Put not developed']);
}

/**
 * Manejo de solicitudes DELETE (Eliminar usuario)
 */
function handleDelete($db) {
    
    if (!isset($_GET['accion'])) {
        response(200, ['error' => 'Falta accion']);
    }

    if($_GET["accion"] == "deleteCourseBanner"){
        $courseId = intval($_GET['id']);

        $deleted = handleBannerDeletion($db, $courseId);
    
        if ($deleted) {
            $newImageUrl = $db->getCourseBanner($courseId);
            response(200, ['success' => true, 'message' => 'Imagen eliminada correctamente.', 'imageUrl' => $newImageUrl]);
        } else {
            response(200, ['success' => false, 'error' => 'Error al eliminar la imagen de la BD.']);
        }
    }else if($_GET["accion"] == "deleteCourse"){
        $courseId = intval($_GET['id']);

            $CourseDeleted = $db->deleteCourse($courseId);
            if ($CourseDeleted) {
                response(200, ['success' => true, 'message' => 'Curso eliminada correctamente.']);
            } else {
                response(200, ['success' => false, 'error' => 'Error al eliminar el curso de la BD.']);
            }
            
        

       

    
        
    }

}


/**
 * Obtiene los datos de la solicitud en JSON o `x-www-form-urlencoded`
 */
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        parse_str(file_get_contents('php://input'), $data);
        return $data;
    }
    
    response(400, ['error' => 'Formato de datos no soportado']);
}

/**
 * Manejo de la subida de imágenes
 */
function handleImageUpload($db) {
    // Verifica si se recibió el archivo y el ID del curso
    if (!isset($_FILES['imagen']) || !isset($_POST['id'])) {
        response(200, ['success' => false, 'error' => 'Falta la imagen o el ID del curso.']);
    }

    $courseId = $_POST['id'];
    $uploadDir = "uploads/courses/$courseId/";

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

    // Definir el nombre de archivo
    $filePath = $uploadDir . "banner." . $imageFileType;

    // Mover la imagen a la carpeta
    if (!move_uploaded_file($_FILES["imagen"]["tmp_name"], $filePath)) {
        response(500, ['success' => false, 'error' => 'Error al mover el archivo.']);
    }

    // Generar la URL pública de la imagen
    $ip_servidor = gethostbyname(gethostname());
    $imageUrl = "http://$ip_servidor/classbridgeapi/" . $filePath;

    // Actualizar en la base de datos
    $response = $db->updateCourseBanner($courseId, $imageUrl);
    if (!$response) {
        response(500, ['success' => false, 'error' => 'Error al insertar la URL en la base de datos.']);
    }

    // Éxito: devolvemos la URL
    return $imageUrl;
}

function handleBannerDeletion($db, $courseId) {
    // Definir la URL de la imagen predeterminada
    $ip_servidor = gethostbyname(gethostname());
    $defaultImageUrl = "http://$ip_servidor/classbridgeapi/uploads/courses/000/banner.png";

    // Obtener la URL de la imagen desde la BD
    $imageUrl = $db->getCourseBanner($courseId);

    // Si la imagen no existe en la BD, devolvemos error
    if (!$imageUrl) {
        response(200, ['success' => false, 'error' => 'No se encontró la imagen del usuario.']);
        return false;
    }

    // Si la imagen es la predeterminada, no la eliminamos
    if ($imageUrl === $defaultImageUrl) {
        response(200, ['success' => false, 'error' => 'No puedes eliminar la imagen predeterminada.']);
        return false;
    }

    // Convertir la URL a una ruta de archivo eliminando la parte del dominio
    $filePath = str_replace("http://$ip_servidor/classbridgeapi/", "", $imageUrl);


    // Eliminar la imagen del servidor si existe
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Actualizar la base de datos para que el usuario tenga la imagen predeterminada
    $resetImage = $db->updateCourseBanner($courseId, $defaultImageUrl);

    if ($resetImage) {
        return true;
    } else {
        return false;
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
