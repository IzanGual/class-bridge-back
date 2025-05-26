<?php

class dbDocumentos
{
    private $pdo;

    public function __construct()
    {
        $config = include 'dbConf.php';

        try {
            $this->pdo = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']}",
                $config['db_user'],
                $config['db_pass']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }


/**
 * Actualiza las URLs de la tabla `documentos`, reemplazando solo la IP por la IP actual del servidor.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */
public function updateDocumentUrlsWithServerIP()
{
    try {
        // Obtener la IP actual del servidor
        $ipServidor = gethostbyname(gethostname());

        // Seleccionar todas las URLs actuales de la tabla documentos
        $selectQuery = "SELECT id, url FROM documentos";
        $stmtSelect = $this->pdo->query($selectQuery);
        $documentos = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

        // Preparar la consulta de actualización
        $updateQuery = "UPDATE documentos SET url = :nueva_url WHERE id = :id";
        $stmtUpdate = $this->pdo->prepare($updateQuery);

        foreach ($documentos as $doc) {
            $urlAntigua = $doc['url'];

            // Reemplazar solo la IP en la URL
            $nuevaUrl = preg_replace(
                '/^http:\/\/[\d.]+/',
                "http://$ipServidor",
                $urlAntigua
            );

            // Solo actualizar si cambió la URL
            if ($nuevaUrl !== $urlAntigua) {
                $stmtUpdate->execute([
                    ':nueva_url' => $nuevaUrl,
                    ':id' => $doc['id']
                ]);
            }
        }

        return true;

    } catch (PDOException $e) {
        return ['error' => 'Error al actualizar las URLs de documentos: ' . $e->getMessage()];
    }
}


/**
 * Crea un nuevo documento en la base de datos.
 *
 * @param int $categoriaId ID de la categoría a la que pertenece el documento.
 * @param string $nombreDocumento Nombre del documento.
 * @param string $publicUrl URL pública del documento.
 *
 * @return bool True si el documento fue creado correctamente, false en caso de error.
 */
public function createDocumento($categoriaId, $nombreDocumento, $publicUrl)
{
    $query = "INSERT INTO documentos (categoria_id, nombre, url) 
              VALUES (:categoria_id, :nombre, :url)";
    
    try {
        $stmt = $this->pdo->prepare($query);
        $success = $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':nombre' => $nombreDocumento,
            ':url' => $publicUrl
        ]);
        return $success;
    } catch (PDOException $e) {
       
        return false;
    }
}

/**
 * Obtiene todos los documentos de una categoría específica.
 *
 * @param int $categoriaId ID de la categoría.
 *
 * @return array|false Lista de documentos o false en caso de error.
 */
public function getDocumentosByCategoriaId($categoriaId)
{
    $query = "SELECT * FROM documentos WHERE categoria_id = :categoria_id";
    
    try {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':categoria_id' => $categoriaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Obtiene un documento por su ID.
 *
 * @param int $id ID del documento.
 *
 * @return array|false Documento o false si no se encuentra o hay error.
 */
public function getDocumentoById($id)
{
    $query = "SELECT * FROM documentos WHERE id = :id";
    
    try {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Actualiza el nombre y la URL de un documento.
 *
 * @param int $id ID del documento.
 * @param string $nombre Nuevo nombre del documento.
 * @param string $url Nueva URL del documento.
 *
 * @return bool true si se actualizó correctamente, false si no.
 */
public function updateDocumento($id, $nombre, $url)
{
    $query = "UPDATE documentos SET nombre = :nombre, url = :url WHERE id = :id";
    
    try {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':url' => $url,
            ':id' => $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Elimina un documento por su ID: borra el archivo del servidor y elimina el registro en la BD.
 *
 * @param int $documentoId ID del documento a eliminar.
 * @return bool True si se eliminó correctamente, false en caso de error.
 */
public function deleteDocumento($documentoId)
{
    // Paso 1: Obtener los datos del documento
    $documento = $this->getDocumentoById($documentoId);
    if (!$documento) {
        return false; // Documento no encontrado
    }

    $url = $documento['url'];
    $urlPath = parse_url($url, PHP_URL_PATH); 
    $relativePath = str_replace("/api/", "", $urlPath); // uploads/...
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/api/" . $relativePath;

    // Paso 2: Eliminar el archivo si existe
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    // Paso 3: Eliminar de la base de datos
    $query = "DELETE FROM documentos WHERE id = :id";
    try {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([':id' => $documentoId]);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Elimina todos los archivos dentro de un directorio y luego el directorio.
 *
 * @param string $dir Ruta del directorio a eliminar.
 * @return void
 */
private function deleteDirectoryContents($dir)
{
    if (!is_dir($dir)) return;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $this->deleteDirectoryContents($path); // Recursivo
            rmdir($path); // Elimina subcarpeta vacía
        } else {
            unlink($path); // Elimina archivo
        }
    }
}








}
