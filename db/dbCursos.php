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


public function uploadCourse($idCurso, $nombreCurso, $usuarios)
{
    try {
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

        // 2. Obtener lista anterior de usuarios del curso (antes de eliminar)
        $stmtOldUsers = $this->pdo->prepare("SELECT usuario_id FROM usuarios_cursos WHERE curso_id = :curso_id");
        $stmtOldUsers->execute([':curso_id' => $idCurso]);
        $oldUsers = $stmtOldUsers->fetchAll(PDO::FETCH_COLUMN);

        // 3. Eliminar relaciones actuales
        $stmtDelete = $this->pdo->prepare("DELETE FROM usuarios_cursos WHERE curso_id = :curso_id");
        $stmtDelete->execute([':curso_id' => $idCurso]);

        // 4. Insertar nuevas relaciones
        $newUsers = [];
        if (is_array($usuarios)) {
            $stmtInsert = $this->pdo->prepare("INSERT INTO usuarios_cursos (usuario_id, curso_id) VALUES (:usuario_id, :curso_id)");
            foreach ($usuarios as $usuarioId) {
                if (is_numeric($usuarioId)) {
                    $stmtInsert->execute([
                        ':usuario_id' => $usuarioId,
                        ':curso_id' => $idCurso
                    ]);
                    $newUsers[] = (int)$usuarioId;
                }
            }
        }

        // 5. Eliminar entregas de los antiguos alumnos que ya NO están
        $usuariosQuitados = array_diff($oldUsers, $newUsers);
        if (!empty($usuariosQuitados)) {
            $in = implode(',', array_fill(0, count($usuariosQuitados), '?'));
            $sqlDeleteEntregas = "
                DELETE FROM entregas 
                WHERE alumno_id IN ($in) 
                AND tarea_id IN (SELECT id FROM tareas WHERE curso_id = ?)
            ";
            $stmtDelEntregas = $this->pdo->prepare($sqlDeleteEntregas);
            $stmtDelEntregas->execute(array_merge($usuariosQuitados, [$idCurso]));
        }

        // 6. Obtener todas las tareas de este curso
        $stmtTareas = $this->pdo->prepare("SELECT id FROM tareas WHERE curso_id = :curso_id");
        $stmtTareas->execute([':curso_id' => $idCurso]);
        $tareas = $stmtTareas->fetchAll(PDO::FETCH_COLUMN);

        // 7. Insertar entregas nuevas para los nuevos usuarios si no existen
        if (!empty($tareas) && !empty($newUsers)) {
            $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM entregas WHERE tarea_id = ? AND alumno_id = ?");
            $stmtInsertEntrega = $this->pdo->prepare("
                INSERT INTO entregas (tarea_id, alumno_id, estado, estado_correccion)
                VALUES (:tarea_id, :alumno_id, 'noentregada', 'no_corregida')
            ");

            foreach ($newUsers as $alumnoId) {
                foreach ($tareas as $tareaId) {
                    // Solo insertar si no existe ya
                    $stmtCheck->execute([$tareaId, $alumnoId]);
                    if ($stmtCheck->fetchColumn() == 0) {
                        $stmtInsertEntrega->execute([
                            ':tarea_id' => $tareaId,
                            ':alumno_id' => $alumnoId
                        ]);
                    }
                }
            }
        }

        // Confirmar todo
        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        $this->pdo->rollBack();
        return false;
    }
}



public function getStudentCourses($aulaId, $userId)
{
    try {
        $sql = "
            SELECT 
                c.*, 
                (
                    SELECT nombre 
                    FROM apartados 
                    WHERE apartados.curso_id = c.id 
                    ORDER BY id ASC 
                    LIMIT 1
                ) AS nombre_apartado
            FROM cursos c
            INNER JOIN usuarios_cursos uc ON c.id = uc.curso_id
            WHERE c.aula_id = :aula_id AND uc.usuario_id = :user_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':aula_id' => $aulaId,
            ':user_id' => $userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return ['error' => 'dbError'];
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

        // Crear carpeta para el curso
        $courseFolderPath = $_SERVER['DOCUMENT_ROOT'] . "/classBridgeAPI/uploads/courses/" . $cursoId;
        if (!is_dir($courseFolderPath)) {
            if (!mkdir($courseFolderPath, 0755, true)) {
                throw new Exception("No se pudo crear la carpeta del curso en: $courseFolderPath");
            }
        }

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

    } catch (Exception $e) {
        $this->pdo->rollBack();
        echo "Error al crear el curso: " . $e->getMessage();
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



/**
 * Obtiene los IDs de los cursos asociados a un usuario.
 *
 * @param int $userId ID del usuario.
 * @return array Array con los IDs de cursos o array vacío si no hay cursos asociados.
 */
public function getCoursesByUserId($userId)
{
    try {
        $query = "SELECT curso_id FROM usuarios_cursos WHERE usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':usuario_id' => $userId]);

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Extrae solo los valores de la columna curso_id
        return $result ?: []; // Devuelve un array vacío si no hay resultados
    } catch (PDOException $e) {
        // Puedes loguear el error si lo necesitas
        // error_log("Error al obtener cursos del usuario: " . $e->getMessage());
        return false;
    }
}




}
