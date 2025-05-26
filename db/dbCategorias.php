<?php

class dbCategorias
{
    private $pdo;

    public function __construct()
    {
        $config = include 'dbConf.php';

        try {
            $this->pdo = new PDO( "mysql:host={$config['db_host']};dbname={$config['db_name']}",
                                   $config['db_user'],
                                   $config['db_pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

/**
 * Obtiene todos los apartados asociados a un curso específico.
 *
 * Este método ejecuta una consulta para recuperar todos los registros de la tabla
 * `apartados` cuyo campo `curso_id` sea igual al ID proporcionado, y los devuelve como un array asociativo.
 *
 * @param int $id El ID del curso del que se desean obtener los apartados.
 * @return array|false Un array asociativo con los apartados encontrados (puede ser un array vacío si no hay ninguno),
 *                     o `false` en caso de error.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function getCategoriasByApartadoId($id)
{
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE apartado_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Actualiza el nombre de un apartado en la base de datos.
 *
 * @param int $apartadoId El ID del apartado a actualizar.
 * @param string $nuevoNombre El nuevo nombre para el apartado.
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */

public function updateCategoria($categoriaId, $nuevoNombre)
{
    try {
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = :nombre WHERE id = :id");
        return $stmt->execute([
            ':nombre' => $nuevoNombre,
            ':id' => $categoriaId
        ]);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Inserta una nueva categoría en la base de datos.
 *
 * @param string $nombre El nombre de la nueva categoría.
 * @param int $cursoId El ID del curso al que pertenece la categoría.
 * @param int $apartadoId El ID del apartado al que pertenece la categoría.
 * @return bool True si se insertó correctamente, false en caso contrario.
 */
public function insertCategoria($nombre, $cursoId, $apartadoId)
{
    try {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (nombre, curso_id, apartado_id) VALUES (:nombre, :curso_id, :apartado_id)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':curso_id' => $cursoId,
            ':apartado_id' => $apartadoId
        ]);
    } catch (PDOException $e) {
        return false;
    }
}



/**
 * Elimina una categoría de la base de datos y su carpeta asociada.
 * Las entregas y documentos se eliminan automáticamente por las claves foráneas ON DELETE CASCADE.
 *
 * @param int $idCategoria El ID de la categoría a eliminar.
 * @return bool True si la categoría se eliminó correctamente, false en caso contrario.
 */
public function deleteCategoria($idCategoria)
{
    try {
        // Obtener curso_id y apartado_id para construir la ruta
        $query = "SELECT curso_id, apartado_id FROM categorias WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $idCategoria]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            $courseId = $categoria['curso_id'];
            $apartadoId = $categoria['apartado_id'];

            // Ruta actualizada que incluye el apartado
            $categoriaFolderPath = $_SERVER['DOCUMENT_ROOT'] . "/api/uploads/courses/$courseId/apartados/$apartadoId/categorias/$idCategoria";

            if (is_dir($categoriaFolderPath)) {
                $this->deleteDirectoryContents($categoriaFolderPath);
                rmdir($categoriaFolderPath);
            }

            // Eliminar la categoría
            $query = "DELETE FROM categorias WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id' => $idCategoria]);

            return true;
        }

        return false;

    } catch (PDOException $e) {
        echo "Error al eliminar la categoría: " . $e->getMessage();
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