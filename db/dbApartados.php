<?php

class dbApartados
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
public function getApartadosByCourseId($id)
{
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM apartados WHERE curso_id = :id");
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
public function updateApartado($apartadoId, $nuevoNombre)
{
    try {
        $stmt = $this->pdo->prepare("UPDATE apartados SET nombre = :nombre WHERE id = :id");
        return $stmt->execute([
            ':nombre' => $nuevoNombre,
            ':id' => $apartadoId
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Inserta un nuevo apartado en la base de datos.
 *
 * @param string $nombre El nombre del nuevo apartado.
 * @param int $cursoId El ID del curso al que pertenece el apartado.
 * @return bool True si se insertó correctamente, false en caso contrario.
 */
public function insertApartado($nombre, $cursoId)
{
    try {
        $stmt = $this->pdo->prepare("INSERT INTO apartados (nombre, curso_id) VALUES (:nombre, :curso_id)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':curso_id' => $cursoId
        ]);
    } catch (PDOException $e) {
        return false;
    }
}




/**
 * Elimina un apartado y su carpeta asociada.
 *
 * @param int $idApartado El ID del apartado a eliminar.
 * @return bool True si se eliminó correctamente, false en caso contrario.
 */
public function deleteApartado($idApartado)
{
    try {
        // Obtener el ID del curso al que pertenece el apartado
        $query = "SELECT curso_id FROM apartados WHERE id = :idApartado";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':idApartado' => $idApartado]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($curso) {
            $courseId = $curso['curso_id'];
            $apartadoFolderPath = $_SERVER['DOCUMENT_ROOT'] . "/api/uploads/courses/$courseId/apartados/$idApartado";

            if (is_dir($apartadoFolderPath)) {
                $this->deleteDirectoryContents($apartadoFolderPath);
                rmdir($apartadoFolderPath);
            }
        }

        // Eliminar el apartado (sus entregas se eliminan por ON DELETE CASCADE)
        $query = "DELETE FROM apartados WHERE id = :idApartado";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':idApartado' => $idApartado]);

        return true;

    } catch (PDOException $e) {
        echo "Error al eliminar el apartado: " . $e->getMessage();
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