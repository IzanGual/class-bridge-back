<?php

class dbUsuarios
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


    // 
    public function registerUser($nombre, $email, $pass) {
        // Comprobamos si el email ya existe en la base de datos
        $checkStmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $checkStmt->execute([':email' => $email]);
        $existingUser = $checkStmt->fetch();
    
        if ($existingUser) {
            // Si el usuario ya existe, devolvemos un error
            return ['error' => 'emailDup'];
        }
    
        // Encriptamos la pass antes de insertarla
        $hashedPassword = password_hash($pass, PASSWORD_BCRYPT);
    
        try {
            // Preparamos la inserción de un nuevo usuario
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, pass) VALUES (:nombre, :email, :pass)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':pass' => $hashedPassword
            ]);
            
            // Si la inserción es exitosa, retornamos true
            return true;
        } catch (PDOException $e) {
            // En caso de error, devolvemos el mensaje de error
            return ['error' => 'Error al registrar usuario: ' . $nombre . $email . $pass .$e->getMessage()];
        }
    }
    



}