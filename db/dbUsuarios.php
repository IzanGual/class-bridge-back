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

        $tipo = "normal";
    
        if ($existingUser) {
            // Si el usuario ya existe, devolvemos un error
            return ['error' => 'emailDup'];
        }
    
        // Encriptamos la pass antes de insertarla
        $hashedPassword = password_hash($pass, PASSWORD_BCRYPT);
    
        try {
            // Preparamos la inserción de un nuevo usuario
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, pass, tipo) VALUES (:nombre, :email, :pass, :tipo)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':pass' => $hashedPassword,
                ':tipo' => $tipo
            ]);
    
            // Si la inserción es exitosa, retornamos true
            return true;
        } catch (PDOException $e) {
            // En caso de error, devolvemos el mensaje de error
            return ['error' => 'Error al registrar usuario: ' . $e->getMessage()];
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
    
    
    public function getUserById($id) {
        // Consulta para obtener los detalles del usuario por ID
        $query = "SELECT id, nombre, email, tipo, estado_suscripcion, img_url FROM usuarios WHERE id = :id";
        
        // Preparamos y ejecutamos la consulta con el ID proporcionado
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        
        // Obtenemos el usuario de la base de datos
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificamos si encontramos el usuario
        if ($user) {
            return $user; // Retornamos los datos del usuario
        }
        
        // Si no encontramos el usuario, retornamos false
        return false;
    }

    public function updateUserImage($idUsuario, $imgUrl) {
        // Consulta SQL para actualizar la imagen del usuario
        $query = "UPDATE usuarios SET img_url = :img_url WHERE id = :id";
        
        try {
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':img_url' => $imgUrl,
                ':id' => $idUsuario
            ]);
    
            // Si la ejecución fue exitosa, devolvemos true, independientemente de si rowCount() es 0
            return $success;
    
        } catch (PDOException $e) {
            // Si hay un error real en la ejecución, lo registramos y devolvemos false
            error_log("Error al actualizar la imagen del usuario: " . $e->getMessage());
            return false;
        }
    }

    public function getUserImage($idUsuario) {
        // Consulta SQL para obtener la URL de la imagen del usuario
        $query = "SELECT img_url FROM usuarios WHERE id = :id LIMIT 1";
    
        try {
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con el ID del usuario
            $stmt->execute([':id' => $idUsuario]);
    
            // Obtenemos el resultado
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Si existe una imagen, la devolvemos; si no, devolvemos null
            return $result ? $result['img_url'] : null;
    
        } catch (PDOException $e) {
            // Si hay un error, lo registramos y devolvemos null
            error_log("Error al obtener la imagen del usuario: " . $e->getMessage());
            return null;
        }
    }

    public function updateUserName($idUsuario, $nombre) {
        // Consulta SQL para actualizar la imagen del usuario
        $query = "UPDATE usuarios SET nombre = :nombre WHERE id = :id";
        
        try {
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $idUsuario
            ]);
    
            // Si la ejecución fue exitosa, devolvemos true, independientemente de si rowCount() es 0
            return $success;
    
        } catch (PDOException $e) {
            // Si hay un error real en la ejecución, lo registramos y devolvemos false
            error_log("Error al actualizar la imagen del usuario: " . $e->getMessage());
            return false;
        }
    }

    
    

    

    

   

}