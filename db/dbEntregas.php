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

}