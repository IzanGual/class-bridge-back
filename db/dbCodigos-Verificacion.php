<?php

class dbCodigosVerificacion
{
    private $pdo;

    public function __construct()
    {
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


/**
 * Inserta o actualiza un código de verificación para un usuario.
 *
 * Si el usuario ya tiene un código en la base de datos, lo actualiza; si no, lo inserta.
 *
 * @param int $id_usuario ID del usuario al que se le asigna el código.
 * @param string $codigo_verificacion Código de verificación que se quiere guardar.
 * @param string $expiracion Fecha y hora de expiración del código (formato DATETIME).
 *
 * @return bool Retorna `true` si la operación fue exitosa, `false` si ocurrió un error.
 *
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
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
            // Si ocurre un error, devuelve false
            return false;
        }
}


/**
 * Obtiene el código de verificación asociado a un usuario.
 *
 * @param int $id_usuario ID del usuario del que se desea obtener el código de verificación.
 *
 * @return array|false Devuelve un array asociativo con los datos del código si existe,
 *                     o `false` si no se encuentra o ocurre un error.
 *
 * @throws PDOException Si ocurre un error durante la consulta.
 */
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


/**
 * Verifica si el código de verificación es válido para un usuario.
 *
 * Comprueba que el código proporcionado coincida con el que está asociado al usuario
 * y que aún no haya expirado.
 *
 * @param int $id_usuario ID del usuario cuyo código se verifica.
 * @param string $codigo_verificacion El código de verificación a comprobar.
 *
 * @return bool `true` si el código es válido, `false` si no es válido o si ocurre un error.
 *
 * @throws PDOException Si ocurre un error durante la consulta.
 */
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

/**
 * Elimina el código de verificación asociado a un usuario.
 *
 * Este método elimina el código de verificación, por ejemplo, si ya ha sido usado
 * o si ha expirado.
 *
 * @param int $id_usuario ID del usuario cuyo código de verificación se desea eliminar.
 *
 * @return bool `true` si el código fue eliminado correctamente, `false` si ocurre un error.
 *
 * @throws PDOException Si ocurre un error durante la consulta.
 */
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
