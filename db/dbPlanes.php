<?php

class dbPlanes
{
    private $pdo;

    public function __construct()
    {
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


/**
 * Obtiene todos los planes de servicio de la base de datos.
 *
 * Este método ejecuta una consulta para recuperar todos los registros de la tabla
 * `planes_servicio` y los devuelve como un array asociativo.
 *
 * @return array|false Un array asociativo con todos los planes de servicio si la consulta es exitosa,
 *                     `false` en caso de un error.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
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


/**
 * Obtiene un plan de servicio por su ID.
 *
 * Este método ejecuta una consulta para recuperar el registro de la tabla
 * `planes_servicio` que coincide con el `id` proporcionado. Si el plan existe,
 * lo devuelve como un array asociativo.
 *
 * @param int $id El ID del plan de servicio a recuperar.
 *
 * @return array|false Un array asociativo con los datos del plan si se encuentra, 
 *                     `false` si ocurre un error o no se encuentra el plan.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
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