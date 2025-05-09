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

        return $usuarios;

    } catch (PDOException $e) {
        
        return false;
    }
}


/**
 * Actualiza la imagen de un curso en la base de datos.
 *
 * Este método permite cambiar la URL de la imagen de un curso en la base de datos 
 * usando su ID. Si la actualización es exitosa, devuelve `true`; si ocurre algún error, 
 * devuelve `false`.
 *
 * @param int $idCurso El ID del curso cuya imagen se actualizará.
 * @param string $imgUrl La nueva URL de la imagen del curso.
 *
 * @return bool `true` si la actualización fue exitosa, `false` en caso contrario.
 */
public function uploadCourse($idCurso, $nombreCurso, $usuarios) {
    try {
        // Si los usuarios llegan como string separados por comas, convertir a array
        if (is_string($usuarios) && $usuarios !== 'noUsers') {
            $usuarios = explode(',', $usuarios);
        }

        // Iniciar transacción
        $this->pdo->beginTransaction();

        // 1. Actualizar el nombre del curso
        $queryNombre = "UPDATE cursos SET nombre = :nombre WHERE id = :id";
        $stmtNombre = $this->pdo->prepare($queryNombre);
        $stmtNombre->execute([
            ':nombre' => $nombreCurso,
            ':id' => $idCurso
        ]);

        // 2. Eliminar todas las relaciones anteriores
        $queryDelete = "DELETE FROM usuarios_cursos WHERE curso_id = :curso_id";
        $stmtDelete = $this->pdo->prepare($queryDelete);
        $stmtDelete->execute([':curso_id' => $idCurso]);

        // 3. Insertar nuevas relaciones (si hay usuarios válidos)
        if (is_array($usuarios)) {
            $queryInsert = "INSERT INTO usuarios_cursos (usuario_id, curso_id) VALUES (:usuario_id, :curso_id)";
            $stmtInsert = $this->pdo->prepare($queryInsert);

            foreach ($usuarios as $usuarioId) {
                if (is_numeric($usuarioId)) {
                    $stmtInsert->execute([
                        ':usuario_id' => $usuarioId,
                        ':curso_id' => $idCurso
                    ]);
                }
            }
        }

        // Confirmar cambios
        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        $this->pdo->rollBack();
        return false;
    }
}



/**
 * Crea un nuevo curso en la base de datos.
 *
 * Inserta un nuevo curso con su nombre, aula asociada y usuarios relacionados.
 *
 * @param string $nombreCurso Nombre del curso a crear.
 * @param array|string $usuarios Lista de IDs de usuarios (array o string separado por comas).
 * @param int $aulaId ID del aula asociada al curso.
 *
 * @return bool `true` si se creó correctamente, `false` si hubo un error.
 */
public function createCourse($nombreCurso, $usuarios, $aulaId) {
    try {
        if (is_string($usuarios) && $usuarios !== 'noUsers') {
            $usuarios = explode(',', $usuarios);
        }

        $this->pdo->beginTransaction();

        // Insertar curso
        $queryCurso = "INSERT INTO cursos (nombre, aula_id) VALUES (:nombre, :aulaId)";
        $stmtCurso = $this->pdo->prepare($queryCurso);
        $stmtCurso->execute([
            ':nombre' => $nombreCurso,
            ':aulaId' => $aulaId
        ]);

        $cursoId = $this->pdo->lastInsertId();

        // Insertar usuarios
        if (is_array($usuarios)) {
            $queryUsuarios = "INSERT INTO usuarios_cursos (usuario_id, curso_id) VALUES (:usuario_id, :curso_id)";
            $stmtUsuarios = $this->pdo->prepare($queryUsuarios);
            foreach ($usuarios as $usuarioId) {
                if (is_numeric($usuarioId)) {
                    $stmtUsuarios->execute([
                        ':usuario_id' => $usuarioId,
                        ':curso_id' => $cursoId
                    ]);
                }
            }
        }

        $this->pdo->commit();
        return $cursoId;

    } catch (PDOException $e) {
        $this->pdo->rollBack();
        return false;
    }
}





/**
 * Actualiza la imagen del usuario en la base de datos.
 *
 * Este método permite cambiar la URL de la imagen del usuario en la base de datos 
 * usando su ID. Si la actualización es exitosa, devuelve `true`; si ocurre algún error, 
 * devuelve `false`.
 *
 * @param int $idUsuario El ID del usuario cuya imagen se actualizará.
 * @param string $imgUrl La nueva URL de la imagen del usuario.
 *
 * @return bool `true` si la actualización fue exitosa, `false` en caso contrario.
 */
public function updateCourseBanner($id, $imgUrl) 
{
        // Consulta SQL para actualizar la imagen del usuario
        $query = "UPDATE cursos SET img_url = :img_url WHERE id = :id";
        
        try {
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':img_url' => $imgUrl,
                ':id' => $id
            ]);
    
            // Si la ejecución fue exitosa, devolvemos true, independientemente de si rowCount() es 0
            return $success;
    
        } catch (PDOException $e) {
            // Si hay un error real en la ejecución, lo registramos y devolvemos false
            //error_log("Error al actualizar la imagen del usuario: " . $e->getMessage());
            return false;
        }
}

/**
 * Obtiene la URL de la imagen (banner) de un curso por su ID.
 *
 * @param int $id El ID del curso.
 * @return string|null La URL de la imagen si se encuentra, o null si no existe o hay error.
 */
public function getCourseBanner($id) 
{
    $query = "SELECT img_url FROM cursos WHERE id = :id";

    try {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['img_url'])) {
            return $result['img_url'];
        } else {
            return false;
        }
    } catch (PDOException $e) {
        //error_log("Error al obtener el banner del curso: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un curso y todo su contenido asociado (archivos en la carpeta de uploads).
 *
 * @param int $courseId El ID del curso a eliminar.
 * @return bool True si el curso se eliminó correctamente, false en caso contrario.
 */
public function deleteCourse($courseId) 
{
    $courseFolderPath = $_SERVER['DOCUMENT_ROOT'] . "/classBridgeAPI/uploads/courses/" . $courseId;

    $this->pdo->beginTransaction();

    try {
        // Eliminar tareas relacionadas con las categorías de este curso
        $query = "DELETE FROM tareas 
                  WHERE categoria_id IN (
                      SELECT id FROM categorias WHERE curso_id = :courseId
                  )";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':courseId' => $courseId]);

        // Eliminar categorías relacionadas con este curso
        $query = "DELETE FROM categorias WHERE curso_id = :courseId";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':courseId' => $courseId]);

        // Eliminar archivos y carpeta del curso
        if (is_dir($courseFolderPath)) {
            $this->deleteDirectoryContents($courseFolderPath);
            rmdir($courseFolderPath);
        }

        // Eliminar el curso de la base de datos
        $query = "DELETE FROM cursos WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $courseId]);

        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        $this->pdo->rollBack();
        echo "Error al eliminar el curso: " . $e->getMessage();
        return false;
    }
}


/**
 * Elimina todos los archivos dentro de un directorio y luego el directorio.
 *
 * @param string $dir Ruta del directorio a eliminar.
 * @return void
 */
private function deleteDirectoryContents($dir)
{
    if (!is_dir($dir)) return;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $this->deleteDirectoryContents($path); // Recursivo
            rmdir($path); // Elimina subcarpeta vacía
        } else {
            unlink($path); // Elimina archivo
        }
    }
}

/**
 * Obtiene toda la información de un curso: apartados, categorías, documentos y entregas.
 *
 * @param int $id ID del curso.
 * @return array|false Información del curso completa o false en caso de error o si no se encuentra.
 */
public function getFullCourseInfo($id)
{
    try {
        // Obtener datos del curso
        $queryCurso = "SELECT * FROM cursos WHERE id = :id";
        $stmtCurso = $this->pdo->prepare($queryCurso);
        $stmtCurso->execute([':id' => $id]);
        $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC);

        if (!$curso) {
            return false;
        }

        // Obtener apartados del curso
        $queryApartados = "SELECT * FROM apartados WHERE curso_id = :curso_id";
        $stmtApartados = $this->pdo->prepare($queryApartados);
        $stmtApartados->execute([':curso_id' => $id]);
        $apartados = $stmtApartados->fetchAll(PDO::FETCH_ASSOC);

        foreach ($apartados as &$apartado) {
            // Obtener categorías del apartado
            $queryCategorias = "SELECT * FROM categorias WHERE apartado_id = :apartado_id";
            $stmtCategorias = $this->pdo->prepare($queryCategorias);
            $stmtCategorias->execute([':apartado_id' => $apartado['id']]);
            $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

            foreach ($categorias as &$categoria) {
                // Documentos de la categoría
                $queryDocs = "SELECT * FROM documentos WHERE categoria_id = :categoria_id";
                $stmtDocs = $this->pdo->prepare($queryDocs);
                $stmtDocs->execute([':categoria_id' => $categoria['id']]);
                $categoria['documentos'] = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

                // Tareas de la categoría
                $queryTareas = "SELECT * FROM tareas WHERE categoria_id = :categoria_id";
                $stmtTareas = $this->pdo->prepare($queryTareas);
                $stmtTareas ->execute([':categoria_id' => $categoria['id']]);
                $categoria['tareas'] = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);
            }

            $apartado['categorias'] = $categorias;
        }

        $curso['apartados'] = $apartados;
        return $curso;

    } catch (PDOException $e) {
        // error_log("Error al obtener la información completa del curso: " . $e->getMessage());
        return false;
    }
}









}
