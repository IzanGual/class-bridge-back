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
 * Obtiene todas las tareas no entregadas y no corregidas de un aula específica.
 *
 * @param int $aula_id ID del aula para filtrar las tareas.
 * @return array|false Un array asociativo con las tareas si la consulta tiene éxito, o false si falla.
 */
public function getUnDoneTareas($aula_id)
{
    try {
        $stmt = $this->pdo->prepare("
            SELECT e.*, u.nombre AS nombre_usuario, t.nombre AS nombre_tarea
            FROM entregas e
            JOIN usuarios u ON e.alumno_id = u.id
            JOIN tareas t ON e.tarea_id = t.id
            JOIN cursos c ON t.curso_id = c.id
            WHERE e.estado = 'noentregada'
              AND e.estado_correccion = 'no_corregida'
              AND c.aula_id = :aula_id
        ");
        $stmt->bindParam(':aula_id', $aula_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Obtiene las tareas no entregadas por un usuario en un aula específica.
 *
 * @param int $aula_id ID del aula.
 * @param int $user_id ID del usuario (alumno).
 * @return array|false Lista de tareas no entregadas, o false en caso de error.
 */
public function getUnDeliveredTasks($aula_id, $user_id)
{
    try {
        $stmt = $this->pdo->prepare("
            SELECT e.*, t.nombre AS nombre_tarea, c.nombre AS nombre_curso
            FROM entregas e
            JOIN tareas t ON e.tarea_id = t.id
            JOIN cursos c ON t.curso_id = c.id
            WHERE e.estado = 'noentregada'
              AND e.alumno_id = :user_id
              AND c.aula_id = :aula_id
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':aula_id', $aula_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Obtiene todas las tareas asociadas a un aula específica.
 *
 * @param int $aula_id ID del aula.
 * @return array|false Un array asociativo con todas las tareas del aula (puede ser vacío),
 *                     o `false` en caso de error.
 */
public function getTasks($aula_id)
{
    try {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.nombre AS nombre_curso
            FROM tareas t
            JOIN cursos c ON t.curso_id = c.id
            WHERE c.aula_id = :aula_id
        ");
        $stmt->bindParam(':aula_id', $aula_id, PDO::PARAM_INT);
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
/**
 * Inserta una nueva tarea en la base de datos y crea entregas no entregadas
 * y no corregidas para todos los alumnos del curso correspondiente.
 *
 * @param string $nombreTarea El nombre de la tarea a insertar.
 * @param string $fechaLimite La fecha límite para la tarea.
 * @param int $cursoId El ID del curso al que pertenece la tarea.
 * @param int $categoriaId El ID de la categoría de la tarea.
 * @return bool `true` si todo se insertó correctamente, `false` si hubo error.
 */
public function insertTarea($nombreTarea, $fechaLimite, $cursoId, $categoriaId)
{
    try {
        // Iniciar transacción
        $this->pdo->beginTransaction();

        // 1. Insertar la tarea
        $sql = "INSERT INTO tareas (nombre, fecha_limite, curso_id, categoria_id) 
                VALUES (:nombreTarea, :fechaLimite, :cursoId, :categoriaId)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':nombreTarea', $nombreTarea, PDO::PARAM_STR);
        $stmt->bindParam(':fechaLimite', $fechaLimite, PDO::PARAM_STR);
        $stmt->bindParam(':cursoId', $cursoId, PDO::PARAM_INT);
        $stmt->bindParam(':categoriaId', $categoriaId, PDO::PARAM_INT);
        $stmt->execute();

        // 2. Obtener el ID de la tarea recién insertada
        $tareaId = $this->pdo->lastInsertId();

        // 3. Obtener los alumnos del curso desde la tabla usuarios_cursos
        $stmtAlumnos = $this->pdo->prepare("SELECT usuario_id FROM usuarios_cursos WHERE curso_id = :cursoId");
        $stmtAlumnos->bindParam(':cursoId', $cursoId, PDO::PARAM_INT);
        $stmtAlumnos->execute();
        $alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_COLUMN);

        // 4. Insertar entregas para cada alumno
        $stmtEntrega = $this->pdo->prepare("
            INSERT INTO entregas (tarea_id, alumno_id, estado, estado_correccion) 
            VALUES (:tareaId, :alumnoId, 'noentregada', 'no_corregida')
        ");

        foreach ($alumnos as $alumnoId) {
            $stmtEntrega->bindParam(':tareaId', $tareaId, PDO::PARAM_INT);
            $stmtEntrega->bindParam(':alumnoId', $alumnoId, PDO::PARAM_INT);
            $stmtEntrega->execute();
        }

        // Confirmar la transacción
        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        // Revertir si algo falla
        $this->pdo->rollBack();
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
                $entregaPath = $_SERVER['DOCUMENT_ROOT'] . "/api/uploads/courses/$courseId/apartados/$apartadoId/categorias/$categoriaId/entregas/$entregaId";
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