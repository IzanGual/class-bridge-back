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

    public function authenticateUser($email, $password) {
        // Consulta para obtener los datos del usuario por email
        $query = "SELECT id, nombre, email, pass, tipo, estado_suscripcion FROM usuarios WHERE email = :email";
        
        // Usamos la conexión a la base de datos de la clase actual (asegurándonos de que sea la misma que en el método de registro)
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':email' => $email]);
        
        // Verificamos si encontramos un usuario
        $user = $stmt->fetch();
        
        if ($user) {
            // Si encontramos un usuario, verificamos la contraseña
            if (password_verify($password, $user['pass'])) {
                // Eliminamos la contraseña antes de devolver los datos
                unset($user['pass']);
                return $user; // Usuario autenticado correctamente
            }
        }
        
        // Si no encontramos al usuario o la contraseña es incorrecta, devolvemos false
        return false;
    }
    
    
    



}