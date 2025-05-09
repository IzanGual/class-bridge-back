<?php
// Limitar el acceso solo a localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    exit('Acceso denegado');
}

require '../db/dbUsuarios.php';
require '../db/dbCursos.php';

$db = new dbUsuarios(); 

$response = $db->updateIgmgURLWithRealIP(); 

if ($response) {
    $db = new dbCursos(); 

    $response = $db->updateIgmgURLWithRealIP();
    if ($response) {

        $db = new dbDocumentos();

        $response = $db->updateDocumentUrlsWithServerIP();
        if ($response) {
            http_response_code(200); // Código de éxito
        echo json_encode(['success' => true, 'message' => 'URLs actualizadas correctamente.']);
        }else{
            http_response_code(500); // Error interno del servidor
        echo json_encode(['success' => false, 'error' => 'Error al actualizar las URLs.']);
        }
        
    } else {
        http_response_code(500); // Error interno del servidor
        echo json_encode(['success' => false, 'error' => 'Error al actualizar las URLs.']);
    }

} else {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'error' => 'Error al actualizar las URLs.']);
}

?>
