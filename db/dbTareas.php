<?php

class dbTareas
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
 * Obtiene todas las entregas no entregadas desde la base de datos.
 *
 * Este método ejecuta una consulta para recuperar todos los registros de la tabla
 * `entregas` cuya columna `estado` sea igual a 'noentregada' y los devuelve como un array asociativo.
 *
 * @return array|false Un array asociativo con todas las entregas no entregadas si la consulta es exitosa
 *                     (puede ser un array vacío si no hay ninguna),
 *                     o `false` en caso de error.
 * @throws PDOException Si ocurre un error durante la ejecución de la consulta.
 */
public function getUnDoneTareas()
{
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM entregas WHERE estado = 'noentregada'");
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