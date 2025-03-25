<?php

class dbCodigosVerificacion
{
    private $pdo; // Variable para la conexión PDO

    // Constructor: inicializa la conexión a la base de datos
    public function __construct()
    {
        // Incluye el archivo de configuración
        $config = include 'dbConf.php';

        try {
            $this->pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']}",
                $config['db_user'],
                $config['db_pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Método para insertar un código de verificación
    public function insertarCodigo($id_usuario, $codigo_verificacion, $expiracion)
    {
        try {
            // Prepara la consulta SQL para insertar el código de verificación en la tabla
            $stmt = $this->pdo->prepare("INSERT INTO codigos_verificacion (id_usuario, codigo_verificacion, expiracion) 
                                        VALUES (:id_usuario, :codigo_verificacion, :expiracion)
                                        ON DUPLICATE KEY UPDATE codigo_verificacion = :codigo_verificacion, expiracion = :expiracion");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':codigo_verificacion', $codigo_verificacion);
            $stmt->bindParam(':expiracion', $expiracion);

            // Ejecuta la consulta
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Si ocurre un error, muestra el mensaje de error
            return false;
        }
    }

    // Método para obtener el código de verificación de un usuario
    public function obtenerCodigoPorUsuario($id_usuario)
    {
        try {
            // Prepara la consulta SQL para obtener el código de verificación
            $stmt = $this->pdo->prepare("SELECT * FROM codigos_verificacion WHERE id_usuario = :id_usuario");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();

            // Devuelve los resultados
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Si ocurre un error, devuelve false
            return false;
        }
    }

    // Método para verificar si el código aún es válido
    public function verificarCodigo($id_usuario, $codigo_verificacion)
    {
        try {
            // Prepara la consulta SQL para verificar si el código es correcto y aún válido
            $stmt = $this->pdo->prepare("SELECT * FROM codigos_verificacion WHERE id_usuario = :id_usuario AND codigo_verificacion = :codigo_verificacion AND expiracion > NOW()");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':codigo_verificacion', $codigo_verificacion);
            $stmt->execute();

            // Devuelve el resultado si el código es válido
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            // Si ocurre un error, devuelve false
            return false;
        }
    }

    // Método para eliminar un código (por ejemplo, si ya se usó o expiró)
    public function eliminarCodigo($id_usuario)
    {
        try {
            // Prepara la consulta SQL para eliminar el código de verificación
            $stmt = $this->pdo->prepare("DELETE FROM codigos_verificacion WHERE id_usuario = :id_usuario");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Si ocurre un error, devuelve false
            return false;
        }
    }
}
?>
