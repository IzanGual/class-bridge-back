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
require 'db/dbAulas.php'; // Archivo de conexión a la base de datos
require 'db/dbPagos.php'; // Archivo de conexión a la base de datos

$db = new dbUsuarios();

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
        // Si se proporciona un ID de usuario, buscar por ID
        if (isset($_GET['id'])) {
            $user = $db->getUserById($_GET['id']);
            if ($user) {
                response(200, [
                    'success' => true,
                    'user' => $user
                ]);
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ]);
            }
            return; // Salir después de manejar este caso
        }

        // Si se proporciona un ID de aula, buscar usuarios por aula
        if (isset($_GET['aula_id'])) {
            $users = $db->getUsersByAulaId($_GET['aula_id']);
            if ($users) {
                response(200, [
                    'success' => true,
                    'users' => $users
                ]);
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'No se encontraron usuarios en el aula'
                ]);
            }
            return; // Salir después de manejar este caso
        }

        // Si no se proporciona ni `id` ni `aula_id`, devolver un error
        response(400, [
            'success' => false,
            'error' => 'Debes proporcionar un parámetro válido (id o aula_id)'
        ]);
    } catch (Exception $e) {
        response(500, [
            'success' => false,
            'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
}

/**
 * Manejo de solicitudes PUT
 */
function handlePut($db) {
    $data = getRequestData();
    $userId = getUserIdFromToken();

        if (empty($data)) {
            response(400, [
                'success' => false,
                'error' => 'noDataProvided'
            ]);
        return; 
        }

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
            $response = $db->updateUserMail($userId, $data['mail']);
        
            if (is_array($response) && isset($response['error'])) {
                response(200, [
                    "success" => false,
                    "error" => $response['error']
                ]);
            } else {
                response(200, [
                    'success' => true,
                    'message' => 'mailSuccessfullyUpdated'
                ]);
            }
        }

        if (isset($data['cursos'])) {
            $response = $db->updateStudent($data['id'] ,$data['nombre'], $data['correo'], $data['pass'], $data['cursos']);

            if (is_array($response) && isset($response['error'])) {
                response(200, [
                    "success" => false,
                    "error" => $response['error']
                ]);
            } else {
                response(200, [
                    'success' => true,
                    'message' => 'passSuccessfullyUpdated'
                ]);
            }
        }

        if (isset($data['pass'])) {
            $response = $db->updateUserPass($userId ,$data['pass']);

            if (is_array($response) && isset($response['error'])) {
                response(200, [
                    "success" => false,
                    "error" => $response['error']
                ]);
            } else {
                response(200, [
                    'success' => true,
                    'message' => 'passSuccessfullyUpdated'
                ]);
            }
        }

        if (isset($data['precio']) && isset($data['classroomName'])) {
            $dbAulas = new dbAulas();
            $dbPagos = new dbPagos();

          
        
            $response = $dbAulas->insertAula($data['classroomName'], $userId);
        
            if (is_array($response) && isset($response['error'])) {
                response(200, [
                    'success' => false,
                    'error' => $response['error']
                ]);
            } else {

                $aulaId = $response;
                $response = $db->updateUserRoleToTeacher($userId, $aulaId);
        
                if (is_array($response) && isset($response['error'])) {
                    response(200, [
                        'success' => false,
                        'error' => $response['error']
                    ]);
                } else {
                    $response = $dbPagos->insertPago($userId, $data['precio']);
        
                    if (is_array($response) && isset($response['error'])) {
                        response(200, [
                            'success' => false,
                            'error' => $response['error']
                        ]);
                    } else {
                        response(200, [
                            'success' => true,
                            'message' => 'userCorrectlyUpdated'
                        ]);
                    }
                }
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

        if (!isset($data['nombre'], $data['email'], $data['contraseña'])) {
            response(400, ['error' => 'Faltan parámetros']);
        } 
        // Si también llegan cursos y aula_id, hacer otra cosa
        else if (isset($data['cursos']) && isset($data['aula_id'])) {
            
             try {
                $response = $db->registerStudent($data['nombre'], $data['email'], $data['contraseña'], $data['cursos'], $data['aula_id']);
                
                if ($response === true) {
                    response(200, [
                        "success" => true,
                        "message" => "Usuario creado con éxito"
                    ]);
                } else {
                    response(200, [
                        "success" => false,
                        "error" => $response['error']
                    ]);
                }

            } catch (Exception $e) {
                response(500, [
                    "success" => false,
                    "error" => 'Error al procesar la solicitud: ' . $e->getMessage()
                ]);
            }




        } 
        // Si solo llegaron los datos del usuario, hacer el registro
        else {
            try {
                $response = $db->registerUser($data['nombre'], $data['email'], $data['contraseña']);
                
                if ($response === true) {
                    response(200, [
                        "success" => true,
                        "message" => "Usuario creado con éxito"
                    ]);
                } else {
                    response(200, [
                        "success" => false,
                        "error" => $response['error']
                    ]);
                }

            } catch (Exception $e) {
                response(500, [
                    "success" => false,
                    "error" => 'Error al procesar la solicitud: ' . $e->getMessage()
                ]);
            }
        }
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

    $userId = getUserIdFromToken();

    if($_GET["action"] == "deleteImage"){

        $deleted = handleImageDeletion($db, $userId);
    
        if ($deleted) {
            $newImageUrl = $db->getUserImage($userId);
            response(200, ['success' => true, 'message' => 'Imagen eliminada correctamente.', 'imageUrl' => $newImageUrl]);
        } else {
            response(200, ['success' => false, 'error' => 'Error al eliminar la imagen de la BD.']);
        }
    }else if($_GET["action"] == "deleteUserProfile"){

        $deleted =  $db->deleteUserProfile($userId);
        if ($deleted) {
            response(200, ['success' => true, 'message' => 'Prifile succesfully deleted']);
        } else {
            response(200, ['success' => false, 'error' => $response['error']]);
        }

    }else if($_GET["action"] == "deleteStudentProfile"){

        $deleted =  $db->deleteUserProfile($_GET["student_id"]);
        if ($deleted) {
            response(200, ['success' => true, 'message' => 'Prifile succesfully deleted']);
        } else {
            response(200, ['success' => false, 'error' => $response['error']]);
        }

    }else if($_GET["action"] == "cancelSuscription"){
        $dbAulas = new dbAulas();
    
        $response = $dbAulas->deleteAulaByProfesor($userId);

        if($response){
            $response = $db->degradeUserRoleToCanceled($userId);
            if($response){
                response(200, ['success' => true, 'message' => 'Prifile succesfully degraded  to normal']);
            }
            else{
                response(200, ['success' => false, 'error' => 'Error in the DB']);
            }
        }else{
            response(200, ['success' => false, 'error' => 'Error in the DB']);

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
        $ip_servidor = gethostbyname(gethostname());
        $imageUrl = "https://classbridge.es/api/" . $filePath;

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
    $ip_servidor = gethostbyname(gethostname());
    $defaultImageUrl = "https://classbridge.es/api/uploads/profiles/000/profile.png";

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
    $filePath = str_replace("https://classbridge.es/api/", "", $imageUrl);


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