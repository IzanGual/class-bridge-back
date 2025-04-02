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
}
