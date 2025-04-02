<?php

class dbPlanes
{
    private $pdo; // Variable per a la connexió PDO

    // Constructor: inicializa la conexió a la bbdd
    public function __construct()
    {
        // Inclou l'arxiu de configuració
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

    public function getAllPlanes()
{
    try {
        $stmt = $this->pdo->prepare('SELECT * FROM planes_servicio');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

public function getPlanById($id)
{
    try {
        $stmt = $this->pdo->prepare('SELECT * FROM planes_servicio WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC); // Usamos fetch() en lugar de fetchAll() ya que esperamos un solo resultado
    } catch (PDOException $e) {
        return false;
    }
}






}