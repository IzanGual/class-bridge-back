<?php

class dbAulas
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
 * Obtiene todos los registros de aulas de la base de datos.
 *
 * @return array|false Retorna un array asociativo con los datos de las aulas si tiene éxito,
 *                     o `false` si ocurre un error en la consulta.
 *
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function getAllAulas()
{
    try {
        $stmt = $this->pdo->prepare('SELECT * FROM aulas');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Inserta un nuevo aula en la base de datos si el profesor no tiene ya un aula asignada
 * y el nombre del aula no está duplicado.
 *
 * @param string $nombre Nombre del aula que se quiere registrar.
 * @param int $profesor_id ID del profesor que va a ser asignado al aula.
 *
 * @return true|array Retorna `true` si la inserción fue exitosa, o un array con un mensaje de error en caso contrario.
 *
 * @throws PDOException Si ocurre un error durante la consulta a la base de datos.
 */
public function insertAula($nombre, $profesor_id)
{
    try {
        // Comprobar si el profesor ya tiene un aula
        $stmt = $this->pdo->prepare("SELECT id FROM aulas WHERE profesor_id = :profesor_id");
        $stmt->bindParam(':profesor_id', $profesor_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return ['error' => 'teacherHasAClass']; // El profesor ya tiene un aula asignada
        }

        // Comprobar si el nombre del aula ya existe
        $stmt = $this->pdo->prepare("SELECT id FROM aulas WHERE nombre = :nombre");
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            return ['error' => 'classNameDup']; // El nombre ya está en uso
        }

        // Insertar la nueva aula
        $stmt = $this->pdo->prepare("INSERT INTO aulas (nombre, profesor_id) VALUES (:nombre, :profesor_id)");
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':profesor_id', $profesor_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return ['error' => 'insertError']; // No se pudo insertar
        }
    } catch (PDOException $e) {
        return ['error' => 'errorBD', 'message' => $e->getMessage()]; // Error en la base de datos
    }
}


/**
 * Elimina el aula asociada a un profesor específico en la base de datos.
 *
 * @param int $profesor_id ID del profesor cuyo aula se desea eliminar.
 *
 * @return bool Retorna `true` si la eliminación fue exitosa, o `false` si ocurrió un error.
 *
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function deleteAulaByProfesor($profesor_id)
{
        try {
            $stmt = $this->pdo->prepare("DELETE FROM aulas WHERE profesor_id = :profesor_id");
            $stmt->bindParam(':profesor_id', $profesor_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
}


/**
 * Obtiene los datos de un aula a partir de su ID.
 *
 * @param int $id ID del aula que se desea buscar.
 *
 * @return array|false Retorna un array asociativo con los datos del aula si existe,
 *                     o `false` si no se encuentra o si ocurre un error.
 *
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function getAulaById($id)
{
    try {
        // Preparamos la consulta SQL para obtener el aula por su ID
        $stmt = $this->pdo->prepare("SELECT * FROM aulas WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        // Ejecutamos la consulta
        $stmt->execute();
        
        // Verificamos si encontramos el aula
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si encontramos el aula, la retornamos
        if ($aula) {
            return $aula;
        }
        
        // Si no encontramos el aula, retornamos false
        return false;
    } catch (PDOException $e) {
        // Si hay un error en la consulta o conexión, retornamos false
        return false;
    }
}

/**
 * Verifica si un profesor es el propietario de un aula específica.
 *
 * @param int $aulaId ID del aula que se desea verificar.
 * @param int $teacherId ID del profesor que se desea comprobar.
 *
 * @return bool Retorna `true` si el profesor es el propietario del aula, `false` en caso contrario
 *              o si ocurre un error en la consulta.
 *
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function isTheTeacher($aulaId, $teacherId) 
{
    try {
        // Preparamos la consulta SQL para obtener el aula por su ID y comprobar el profesor
        $stmt = $this->pdo->prepare("SELECT profesor_id FROM aulas WHERE id = :id");
        $stmt->bindParam(':id', $aulaId, PDO::PARAM_INT); // Usamos el parámetro aulaId

        // Ejecutamos la consulta
        $stmt->execute();

        // Verificamos si encontramos el aula
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si encontramos el aula, comprobamos si el profesor coincide
        if ($aula) {
            // Comparar el profesor_id del aula con el teacherId proporcionado
            if ($aula['profesor_id'] == $teacherId) {
                return true; // El usuario es el profesor del aula
            }
        }

        // Si no encontramos el aula o los ids no coinciden, retornamos false
        return false;
    } catch (PDOException $e) {
        // Si hay un error en la consulta o conexión, retornamos false
        return false;
    }
}


}
