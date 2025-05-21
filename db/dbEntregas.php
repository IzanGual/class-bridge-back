<?php

class dbEntregas
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
 * Obtiene entregas según filtros opcionales: alumno_id y/o tarea_id.
 *
 * @param int|null $alumno_id ID del alumno (opcional)
 * @param int|null $tarea_id ID de la tarea (opcional)
 * @return array|false Lista de entregas o false si hay error
 */
public function getEntregas($alumno_id = null, $tarea_id = null)
{
    try {
        $sql = "
            SELECT 
                e.*, 
                u.nombre AS nombre_alumno, 
                t.nombre AS nombre_tarea,
                t.fecha_limite AS fecha_limite_tarea
            FROM entregas e
            JOIN usuarios u ON e.alumno_id = u.id
            JOIN tareas t ON e.tarea_id = t.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($alumno_id)) {
            $sql .= " AND e.alumno_id = :alumno_id";
            $params[':alumno_id'] = $alumno_id;
        }

        if (!empty($tarea_id)) {
            $sql .= " AND e.tarea_id = :tarea_id";
            $params[':tarea_id'] = $tarea_id;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    return false;
}
}

/**
 * Obtiene una única entrega para un alumno y una tarea específicos.
 *
 * @param int $alumno_id ID del alumno
 * @param int $tarea_id ID de la tarea
 * @return array|false Datos de la entrega o false si no existe o hay error
 */
public function getEntrega($alumno_id, $tarea_id)
{
    try {
        $sql = "
            SELECT 
                e.*, 
                u.nombre AS nombre_alumno, 
                t.nombre AS nombre_tarea,
                t.fecha_limite AS fecha_limite_tarea
            FROM entregas e
            JOIN usuarios u ON e.alumno_id = u.id
            JOIN tareas t ON e.tarea_id = t.id
            WHERE e.alumno_id = :alumno_id AND e.tarea_id = :tarea_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':alumno_id' => $alumno_id,
            ':tarea_id' => $tarea_id
        ]);

        $entrega = $stmt->fetch(PDO::FETCH_ASSOC);

        return $entrega ?: false;

    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Obtiene una entrega específica por su ID.
 *
 * @param int $id ID de la entrega
 * @return array|false Datos de la entrega o false si hay error
 */
public function getEntregaById($id)
{
    try {
        $sql = "
            SELECT 
                e.*, 
                u.nombre AS nombre_alumno, 
                t.nombre AS nombre_tarea,
                t.fecha_limite AS fecha_limite_tarea
            FROM entregas e
            JOIN usuarios u ON e.alumno_id = u.id
            JOIN tareas t ON e.tarea_id = t.id
            WHERE e.id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC); // fetch() en lugar de fetchAll() porque es solo una
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Corrige una entrega actualizando la nota, el comentario y el estado de corrección.
 *
 * @param int $id ID de la entrega
 * @param string $nota Nota asignada
 * @param string $comentario Comentario del profesor
 * @return bool true si la actualización fue exitosa, false en caso contrario
 */
public function correctEntrega($id, $nota, $comentario)
{
    try {
        $sql = "
            UPDATE entregas
            SET 
                nota = :nota,
                comentario = :comentario,
                estado_correccion = 'corregida'
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nota' => $nota,
            ':comentario' => $comentario,
            ':id' => $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

public function getEntregaInfo($entregaID)
{
    try {
        $sql = "
            SELECT 
                t.curso_id AS course_id,
                c.apartado_id AS apartado_id,
                t.categoria_id AS categoria_id
            FROM entregas e
            JOIN tareas t ON e.tarea_id = t.id
            JOIN categorias c ON t.categoria_id = c.id
            WHERE e.id = :entregaID
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':entregaID' => $entregaID]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

    } catch (PDOException $e) {
        return false;
    }
}


public function registrarEntrega($entregaId, $urlDocumento, $fecha)
{
    try {
        $sql = "
            UPDATE entregas
            SET 
                archivo_url = :urlDocumento,
                fecha_entrega = :fecha,
                estado = 'entregada'
            WHERE id = :entregaId
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':urlDocumento' => $urlDocumento,
            ':fecha' => $fecha,
            ':entregaId' => $entregaId
        ]);
    } catch (PDOException $e) {
        return false;
    }
}


public function deleteEntrega($entregaId)
{
    try {
        // 1. Obtener info de la entrega
        $sql = "SELECT e.id AS entrega_id, e.archivo_url, 
                       t.curso_id, c.apartado_id, t.categoria_id
                FROM entregas e
                JOIN tareas t ON e.tarea_id = t.id
                JOIN categorias c ON t.categoria_id = c.id
                WHERE e.id = :entregaId";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':entregaId' => $entregaId]);
        $entrega = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entrega) {
            return false; // Entrega no encontrada
        }

        // 2. Borrar archivos y carpeta
        $entregaPath = $_SERVER['DOCUMENT_ROOT'] . "/classBridgeAPI/uploads/courses/{$entrega['curso_id']}/apartados/{$entrega['apartado_id']}/categorias/{$entrega['categoria_id']}/entregas/{$entrega['entrega_id']}";
        if (is_dir($entregaPath)) {
            $this->deleteDirectoryContents($entregaPath);
            rmdir($entregaPath); // eliminar carpeta vacía
        }

        // 3. Vaciar campos de entrega
        $sql = "UPDATE entregas
                SET comentario = NULL,
                    nota = NULL,
                    fecha_entrega = NULL,
                    archivo_url = NULL,
                    estado_correccion = 'no_corregida',
                    estado = 'noentregada'
                WHERE id = :entregaId";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':entregaId' => $entregaId]);

        return true;

    } catch (PDOException $e) {
        echo "Error al eliminar entrega: " . $e->getMessage();
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