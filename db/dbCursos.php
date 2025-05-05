<?php

class dbCursos
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
 * Actualiza la URL de la imagen de todos los usuarios con la IP real del servidor.
 *
 * Este método obtiene la dirección IP del servidor mediante `gethostbyname()`, construye una nueva URL
 * para la imagen del perfil y actualiza el campo `img_url` de todos los usuarios con esta nueva URL.
 * Si la operación es exitosa, devuelve `true`; en caso contrario, devuelve un mensaje de error.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */
public function updateIgmgURLWithRealIP() 
{
        try {
            // Obtener la IP del servidor usando gethostbyname() para asegurarnos de que sea IPv4
            $ipServidor = gethostbyname(gethostname());
    
            // Nueva URL con la IP del servidor
            $nuevaImgUrl = "http://$ipServidor/classbridgeapi/uploads/courses/000/banner.png";
    
            // Consulta para actualizar todos los usuarios
            $query = "UPDATE cursos SET img_url = :nueva_url";
    
            // Preparar y ejecutar la consulta
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([
                ':nueva_url' => $nuevaImgUrl
            ]);
    
            return $success;
    
        } catch (PDOException $e) {
            return ['error' => 'Error al actualizar img_url: ' . $e->getMessage()];
        }
}

/**
 * Método que obtiene los cursos asociados a un aula específica desde la tabla 'cursos'.
 * Devuelve true si la consulta se realiza correctamente y hay datos, false en caso de error o si no hay resultados.
 */
/**
 * Método que obtiene los cursos asociados a un aula específica desde la tabla 'cursos',
 * e incluye el primer apartado asociado como 'nombre_apartado'.
 * Devuelve un array con los cursos o false si hay error o no hay resultados.
 */
public function getOwnCourses($aula_id) 
{
    try {
        $query = "
            SELECT 
                cursos.*, 
                (
                    SELECT nombre 
                    FROM apartados 
                    WHERE apartados.curso_id = cursos.id 
                    ORDER BY id ASC 
                    LIMIT 1
                ) AS nombre_apartado
            FROM cursos
            WHERE aula_id = :aula_id
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':aula_id' => $aula_id]);

        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $cursos;

    } catch (PDOException $e) {
        
        return false;
    }
}

/**
 * Método que obtiene los usuarios asociados a un curso específico desde la tabla 'usuarios_cursos',
 * y devuelve su id y nombre mediante un JOIN con la tabla 'usuarios'.
 * Devuelve un array con los usuarios si hay resultados, o false en caso de error o si no hay ninguno.
 */
public function getUsersByCourse_id($course_id)
{
    try {
        $query = "
            SELECT u.id, u.nombre
            FROM usuarios_cursos uc
            JOIN usuarios u ON u.id = uc.usuario_id
            WHERE uc.curso_id = :course_id
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':course_id' => $course_id]);

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $usuarios ?: false;

    } catch (PDOException $e) {
        
        return false;
    }
}




}
