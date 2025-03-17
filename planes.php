<?php
// Habilitar CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Allow: GET, POST, PUT, DELETE');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db/dbPlanes.php';

$db = new dbPlanes();

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
            $planes = $db->getAllPlanes(); 
            echo json_encode($planes); 
        break;

    case 'POST':
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
      
        // Insertar un nou estudiant
        // A data  tindrem un array associatiu amb els paràmetres que rebem per POST
        // Haurem de rebre les dades diferents si ens ve en format JSON, o per formulari POST
        // Ens haurem d'assegurar d'indicar bé el content type si cridem desde JS

         // TO-DEBUG -- PER A COMPROVAR QUE PASSA SI NO HO AGAFEM COM TOCA
        //echo json_encode($contentType);
        //$data = json_decode(file_get_contents('php://input'), true);
        //parse_str(file_get_contents('php://input'), $data);
        //echo json_encode($data);
        

        // Amb este if ens assegurem que rebem $data com toca depenent del contentType
        // Sempre que el contentType sigue correcte i els paràmetres s'envien codificats com toca

        
        if (strpos($contentType, 'application/json') !== false) {
            // Si el Content-Type es JSON, usamos json_decode
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Si el Content-Type es form-urlencoded, usamos parse_str
            parse_str(file_get_contents('php://input'), $data);
        } else {
            // Si el Content-Type no coincide con ninguno, enviamos un error
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Unsupported Content-Type']);
            exit;
        }
            
       
        // Insertem a través de la capa d'accés i tornem en JSON el resultat
        try
        {
            $response = $db->insertCategoria($data['name']);
            if ($response) {
                // Respuesta en caso de éxito
                echo json_encode([
                    "success" => true,
                    "message" => "Categoría creada con éxito",
                    "id" => $response // ID de la nueva categoría
                ]);
            } else {
                // Respuesta en caso de error
                http_response_code(500); // Código de error
                echo json_encode([
                    "success" => false,
                    "error" => "Error al crear la categoría"
                ]);
            }
            
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            print json_encode(['error' => 'Error insertant producte']);
       }
        break;
    case 'PUT':


        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
      
        // Insertar un nou estudiant
        // A data  tindrem un array associatiu amb els paràmetres que rebem per POST
        // Haurem de rebre les dades diferents si ens ve en format JSON, o per formulari POST
        // Ens haurem d'assegurar d'indicar bé el content type si cridem desde JS

         // TO-DEBUG -- PER A COMPROVAR QUE PASSA SI NO HO AGAFEM COM TOCA
        //echo json_encode($contentType);
        //$data = json_decode(file_get_contents('php://input'), true);
        //parse_str(file_get_contents('php://input'), $data);
        //echo json_encode($data);
        

        // Amb este if ens assegurem que rebem $data com toca depenent del contentType
        // Sempre que el contentType sigue correcte i els paràmetres s'envien codificats com toca

        
        if (strpos($contentType, 'application/json') !== false) {
            // Si el Content-Type es JSON, usamos json_decode
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Si el Content-Type es form-urlencoded, usamos parse_str
            parse_str(file_get_contents('php://input'), $data);
        } else {
            // Si el Content-Type no coincide con ninguno, enviamos un error
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Unsupported Content-Type']);
            exit;
        }
            
       
        // Insertem a través de la capa d'accés i tornem en JSON el resultat
        try
        {
            $response = $db->updateCategoria($data['id'], $data['name']);
            if ($response) {
                // Respuesta en caso de éxito
                echo json_encode([
                    "success" => true,
                    "message" => "Categoría creada con éxito",
                    "id" => $response // ID de la nueva categoría
                ]);
            } else {
                // Respuesta en caso de error
                http_response_code(500); // Código de error
                echo json_encode([
                    "success" => false,
                    "error" => "Error al crear la categoría"
                ]);
            }
            
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            print json_encode(['error' => 'Error insertant producte']);
       }





        break;

    case 'DELETE':
         

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
      
        // Insertar un nou estudiant
        // A data  tindrem un array associatiu amb els paràmetres que rebem per POST
        // Haurem de rebre les dades diferents si ens ve en format JSON, o per formulari POST
        // Ens haurem d'assegurar d'indicar bé el content type si cridem desde JS

         // TO-DEBUG -- PER A COMPROVAR QUE PASSA SI NO HO AGAFEM COM TOCA
        //echo json_encode($contentType);
        //$data = json_decode(file_get_contents('php://input'), true);
        //parse_str(file_get_contents('php://input'), $data);
        //echo json_encode($data);
        

        // Amb este if ens assegurem que rebem $data com toca depenent del contentType
        // Sempre que el contentType sigue correcte i els paràmetres s'envien codificats com toca

        
        if (strpos($contentType, 'application/json') !== false) {
            // Si el Content-Type es JSON, usamos json_decode
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Si el Content-Type es form-urlencoded, usamos parse_str
            parse_str(file_get_contents('php://input'), $data);
        } else {
            // Si el Content-Type no coincide con ninguno, enviamos un error
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Unsupported Content-Type']);
            exit;
        }
            
       
        // Insertem a través de la capa d'accés i tornem en JSON el resultat
        try
        {
            $response = $db->deleteCategoria($data['id']);
            if ($response) {
                // Respuesta en caso de éxito
                echo json_encode([
                    "success" => true,
                    "message" => "Categoría eliminada con éxito",
                    "id" => $response // ID de la nueva categoría
                ]);
            } else {
                // Respuesta en caso de error
                http_response_code(500); // Código de error
                echo json_encode([
                    "success" => false,
                    "error" => "Error al eliminar la categoría"
                ]);
            }
            
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            print json_encode(['error' => 'Error insertant producte']);
       }


        break;

    default:
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: GET, POST, PUT, DELETE');
        break;
}
?>