<?php
// Limitar el acceso solo a localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    exit('Acceso denegado');
}

require '../db/dbUsuarios.php'; // Archivo de conexión a la base de datos

$db = new dbUsuarios(); // Instancia de la clase de acceso a datos

$response = $db->updateIgmgURLWithRealIP(); // Llamada al método para actualizar las URLs

if ($response) {
    http_response_code(200); // Código de éxito
    echo json_encode(['success' => true, 'message' => 'URLs actualizadas correctamente.']);
} else {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'error' => 'Error al actualizar las URLs.']);
}

?>
