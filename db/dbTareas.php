<?php

class dbTareas
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
 * Obtiene todas las entregas no entregadas desde la base de datos.
 *
 * Este método ejecuta una consulta para recuperar todos los registros de la tabla
 * `entregas` cuya columna `estado` sea igual a 'noentregada' y los devuelve como un array asociativo.
 *
 * @return array|false Un array asociativo con todas las entregas no entregadas si la consulta es exitosa
 *                     (puede ser un array vacío si no hay ninguna),
 *                     o `false` en caso de error.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function getUnDoneTareas()
{
    try {
        $stmt = $this->pdo->prepare("
            SELECT e.*, u.nombre AS nombre_usuario, t.nombre AS nombre_tarea
            FROM entregas e
            JOIN usuarios u ON e.alumno_id = u.id
            JOIN tareas t ON e.tarea_id = t.id
            WHERE e.estado = 'noentregada' AND e.estado_correccion = 'no_corregida'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Obtiene todas las tareas desde la base de datos.
 *
 * @return array|false Un array asociativo con todas las tareas (puede ser vacío),
 *                     o `false` en caso de error.
 */
public function getTasks()
{
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}




/**
 * Inserta una nueva tarea en la base de datos.
 *
 * Este método se encarga de insertar un nuevo registro en la tabla `tareas`. Recibe como parámetros
 * el nombre de la tarea, la fecha límite, el ID del curso y el ID de la categoría. Utiliza una consulta
 * SQL preparada para evitar inyecciones SQL, y utiliza `bindParam` para vincular los valores de los 
 * parámetros a la consulta antes de ejecutarla.
 *
 * @param string $nombreTarea El nombre de la tarea a insertar.
 * @param string $fechaLimite La fecha límite para la tarea.
 * @param int $cursoId El ID del curso al que pertenece la tarea.
 * @param int $categoriaId El ID de la categoría de la tarea.
 * @return bool `true` si la tarea se insertó correctamente, `false` si ocurrió un error.
 */
public function insertTarea($nombreTarea, $fechaLimite, $cursoId, $categoriaId)
{
    try {
        // Preparar la consulta SQL para insertar los datos en la tabla tareas
        $sql = "INSERT INTO tareas (nombre, fecha_limite, curso_id, categoria_id) 
                VALUES (:nombreTarea, :fechaLimite, :cursoId, :categoriaId)";
        
        // Preparar la sentencia SQL
        $stmt = $this->pdo->prepare($sql);
        
        // Asignar valores a los parámetros de la consulta SQL para evitar inyecciones
        $stmt->bindParam(':nombreTarea', $nombreTarea, PDO::PARAM_STR);
        $stmt->bindParam(':fechaLimite', $fechaLimite, PDO::PARAM_STR);
        $stmt->bindParam(':cursoId', $cursoId, PDO::PARAM_INT);
        $stmt->bindParam(':categoriaId', $categoriaId, PDO::PARAM_INT);
        
        // Ejecutar la consulta
        $stmt->execute();
        
        // Si la inserción fue exitosa, devolver true
        return true;
        
    } catch (PDOException $e) {
        // Si ocurre un error en la ejecución de la consulta, devolver false
        return false;
    }
}
/**
 * Actualiza el nombre y la fecha límite de una tarea existente en la base de datos.
 *
 * Este método actualiza los campos `nombre` y `fecha_limite` de una tarea en la tabla `tareas`.
 * Solo requiere el ID de la tarea a modificar, el nuevo nombre y la nueva fecha límite.
 * Utiliza una consulta preparada para evitar inyecciones SQL, y `bindParam` para enlazar valores.
 *
 * @param int $id El ID de la tarea a actualizar.
 * @param string $nombreTarea El nuevo nombre de la tarea.
 * @param string $fechaLimite La nueva fecha límite de la tarea.
 * @return bool `true` si la actualización fue exitosa, `false` si ocurrió un error.
 */
public function updateTarea($id, $nombreTarea, $fechaLimite)
{
    try {
        // Preparar la consulta SQL para actualizar nombre y fecha_limite
        $sql = "UPDATE tareas 
                SET nombre = :nombreTarea, 
                    fecha_limite = :fechaLimite 
                WHERE id = :id";
        
        // Preparar la sentencia
        $stmt = $this->pdo->prepare($sql);

        // Asignar valores a los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombreTarea', $nombreTarea, PDO::PARAM_STR);
        $stmt->bindParam(':fechaLimite', $fechaLimite, PDO::PARAM_STR);

        // Ejecutar la sentencia
        $stmt->execute();

        return true;

    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Obtiene todas las tareas asociadas a una categoría específica.
 *
 * @param int $categoriaId El ID de la categoría.
 * @return array|false Array de tareas si la consulta es exitosa, o false en caso de error.
 */
public function getTasksByCategoriaId($categoriaId)
{
    try {
        $sql = "SELECT * FROM tareas WHERE categoria_id = :categoriaId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':categoriaId', $categoriaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Elimina una tarea y borra los archivos asociados a sus entregas.
 *
 * Este método elimina una tarea de la base de datos. Gracias a `ON DELETE CASCADE`,
 * todas las entregas asociadas también serán eliminadas de la base de datos. Además,
 * se eliminan las carpetas del sistema de archivos relacionadas con esas entregas.
 *
 * @param int $idTarea El ID de la tarea a eliminar.
 * @return bool `true` si la tarea y sus archivos fueron eliminados correctamente, `false` si ocurrió un error.
 */
public function deleteTask($idTarea)
{
    try {
        // Obtener curso_id, apartado_id, categoria_id y entrega_ids relacionados con la tarea
        $sql = "SELECT t.curso_id, c.apartado_id, t.categoria_id, e.id AS entrega_id 
                FROM tareas t 
                LEFT JOIN entregas e ON t.id = e.tarea_id
                JOIN categorias c ON t.categoria_id = c.id
                WHERE t.id = :idTarea";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idTarea' => $idTarea]);
        $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$entregas) {
            return false; // No se encontró la tarea
        }

        // Eliminar carpetas de entregas si existen
        foreach ($entregas as $entrega) {
            $courseId = $entrega['curso_id'];
            $apartadoId = $entrega['apartado_id'];
            $categoriaId = $entrega['categoria_id'];
            $entregaId = $entrega['entrega_id'];

            if ($entregaId) {
                $entregaPath = $_SERVER['DOCUMENT_ROOT'] . "/classBridgeAPI/uploads/courses/$courseId/apartados/$apartadoId/categorias/$categoriaId/entregas/$entregaId";
                if (is_dir($entregaPath)) {
                    $this->deleteDirectoryContents($entregaPath);
                    rmdir($entregaPath);
                }
            }
        }

        // Eliminar la tarea (esto también elimina las entregas por ON DELETE CASCADE)
        $stmt = $this->pdo->prepare("DELETE FROM tareas WHERE id = :idTarea");
        $stmt->execute([':idTarea' => $idTarea]);

        return true;

    } catch (PDOException $e) {
        echo "Error al eliminar la tarea: " . $e->getMessage();
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