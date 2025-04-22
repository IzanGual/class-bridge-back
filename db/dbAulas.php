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

    // Método para insertar una nueva aula
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


    // Método para eliminar aulas por profesor_id
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

    // Método para obtener un aula por su ID
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
