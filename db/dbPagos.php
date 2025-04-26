<?php

class dbPagos
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
 * Inserta un nuevo pago en la base de datos.
 *
 * Este método inserta un nuevo registro de pago con la información proporcionada,
 * utilizando valores predeterminados para el método de pago y el estado.
 *
 * @param int $usuarioId ID del usuario que realizó el pago.
 * @param float $monto Monto del pago realizado.
 *
 * @return bool `true` si el pago fue insertado correctamente, `false` en caso contrario.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function insertPago($usuarioId, $monto)
{
        try {
            // Valores predeterminados
            $metodoPago = "Tarjeta de credito";
            $estado = "completado";

            // Consulta SQL para insertar el pago
            $query = "INSERT INTO pagos (usuario_id, monto, metodo_pago, estado) 
                      VALUES (:usuario_id, :monto, :metodo_pago, :estado)";
            
            // Preparar la consulta
            $stmt = $this->pdo->prepare($query);

            // Ejecutar la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':monto' => $monto,
                ':metodo_pago' => $metodoPago,
                ':estado' => $estado
            ]);

            return $success; // Devuelve true si la inserción fue exitosa

        } catch (PDOException $e) {
            return ['error' => 'Error al insertar el pago: ' . $e->getMessage()];
        }
}

}
