<?php

class dbUsuarios
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
 * Registra un nuevo usuario en la base de datos.
 *
 * Este método comprueba si el correo electrónico proporcionado ya está registrado. 
 * Si el correo no existe, encripta la contraseña proporcionada y registra al nuevo usuario 
 * en la tabla `usuarios`. Si el correo ya está registrado, devuelve un error.
 *
 * @param string $nombre El nombre del nuevo usuario.
 * @param string $email El correo electrónico del nuevo usuario.
 * @param string $pass La contraseña del nuevo usuario (sin encriptar).
 *
 * @return bool|array `true` si el registro fue exitoso, o un array con el error 
 *                    `['error' => 'emailDup']` si el correo ya está registrado, 
 *                    o un mensaje de error en caso de una excepción.
 * @throws PDOException Si ocurre un error durante la consulta o inserción en la base de datos.
 */    
public function registerUser($nombre, $email, $pass) 
{
        // Comprobamos si el email ya existe en la base de datos
        $checkStmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $checkStmt->execute([':email' => $email]);
        $existingUser = $checkStmt->fetch();

        
    
        if ($existingUser) {
            // Si el usuario ya existe, devolvemos un error
            return ['error' => 'emailDup'];
        }
        $tipo = "normal";
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
    


/**
 * Autentica a un usuario comprobando su email y contraseña.
 *
 * Este método consulta la base de datos para obtener los datos de un usuario 
 * basándose en su correo electrónico. Si el usuario existe y la contraseña 
 * proporcionada coincide con la almacenada (encriptada), el método devuelve 
 * los datos del usuario sin la contraseña. Si no se encuentra al usuario 
 * o la contraseña es incorrecta, devuelve `false`.
 *
 * @param string $email El correo electrónico del usuario que intenta autenticar.
 * @param string $password La contraseña proporcionada por el usuario.
 *
 * @return array|false Un array con los datos del usuario (sin la contraseña) si la autenticación es exitosa, 
 *                     o `false` si el email o la contraseña no son correctos.
 */
public function authenticateUser($email, $password) 
{
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


/**
 * Verifica si el usuario tiene acceso a un aula específica.
 *
 * Este método consulta el aula asociada al correo electrónico del usuario y la compara 
 * con el ID de aula proporcionado. Si coinciden, devuelve `true`, indicando que el usuario 
 * tiene acceso al aula. Si no, devuelve `false`.
 *
 * @param int $aulaID El ID del aula que se quiere verificar.
 * @param string $email El correo electrónico del usuario a verificar.
 *
 * @return bool `true` si el usuario tiene acceso al aula, `false` si no.
 */

public function authenticateUserClass($aulaID, $email) 
{
        // Consulta para obtener el classId del usuario por email
        $query = "SELECT aulaId FROM usuarios WHERE email = :email";
        
        // Usamos la conexión a la base de datos de la clase actual
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':email' => $email]);
        
        // Verificamos si encontramos un usuario
        $user = $stmt->fetch();
        
        if ($user) {
            // Si el classId del usuario coincide con el aulaID proporcionado
            if ($user['aulaId'] == $aulaID) {
                return true; // Coincide
            }
        }
        
        // Si no hay coincidencia, devolvemos false
        return false;
}
    
    

/**
 * Obtiene los detalles de un usuario por su ID.
 *
 * Este método consulta la base de datos para obtener la información de un usuario 
 * utilizando su ID. Si el usuario existe, devuelve los datos correspondientes; 
 * de lo contrario, devuelve `false`.
 *
 * @param int $id El ID del usuario a obtener.
 *
 * @return array|false Los datos del usuario si se encuentra, `false` si no se encuentra.
 */
public function getUserById($id) 
{
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



/**
 * Obtiene una lista de usuarios tipo 'alumno' que pertenecen a un aula específica.
 *
 * Este método consulta la base de datos para obtener todos los usuarios 
 * cuyo campo `aulaId` coincida con el proporcionado y cuyo tipo sea 'alumno'. 
 * Si la consulta se ejecuta correctamente, devuelve el array de usuarios 
 * (puede estar vacío). En caso de error al ejecutar la consulta, devuelve `false`.
 *
 * @param int $aula_id El ID del aula cuyos alumnos se desean obtener.
 *
 * @return array|false Un array con los alumnos encontrados (puede estar vacío), 
 *                     o `false` si ocurre un error en la consulta.
 */
public function getUsersByAulaId($aula_id) 
{
    try {
        // Consulta para obtener los usuarios tipo 'alumno' con el aulaId dado
        $query = "SELECT id, nombre, email, tipo, aulaId, img_url 
                  FROM usuarios 
                  WHERE aulaId = :aula_id AND tipo = 'alumno'";
        
        // Preparamos y ejecutamos la consulta
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':aula_id' => $aula_id]);
        
        // Obtenemos todos los usuarios que coinciden
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Retornamos el array de usuarios (puede estar vacío)
        return $users;
    } catch (PDOException $e) {
        // Si ocurre un error en la consulta, retornamos false
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
public function updateUserImage($idUsuario, $imgUrl) 
{
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
            //error_log("Error al actualizar la imagen del usuario: " . $e->getMessage());
            return false;
        }
}



/**
 * Obtiene la URL de la imagen del usuario desde la base de datos.
 *
 * Este método consulta la base de datos para obtener la URL de la imagen de un usuario
 * usando su ID. Si encuentra la imagen, devuelve la URL; si no, devuelve `null`.
 *
 * @param int $idUsuario El ID del usuario cuya imagen se recuperará.
 *
 * @return string|null La URL de la imagen del usuario si existe, `null` si no se encuentra.
 */
public function getUserImage($idUsuario) 
{
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
            //error_log("Error al obtener la imagen del usuario: " . $e->getMessage());
            return null;
        }
}



/**
 * Actualiza el nombre del usuario en la base de datos.
 *
 * Este método actualiza el nombre de un usuario específico utilizando su ID. Si la actualización
 * es exitosa, devuelve `true`; de lo contrario, devuelve `false`.
 *
 * @param int $idUsuario El ID del usuario cuyo nombre se actualizará.
 * @param string $nombre El nuevo nombre del usuario.
 *
 * @return bool `true` si la actualización fue exitosa, `false` en caso de error.
 */
public function updateUserName($idUsuario, $nombre) 
{
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
            //error_log("Error al actualizar la imagen del usuario: " . $e->getMessage());
            return false;
        }
}



/**
 * Actualiza el correo electrónico de un usuario en la base de datos.
 *
 * Este método actualiza el correo electrónico de un usuario específico utilizando su ID. Antes de
 * realizar la actualización, verifica si el correo electrónico ya está registrado en otro usuario.
 * Si el correo electrónico está duplicado, devuelve un error. Si la actualización es exitosa,
 * devuelve `true`; de lo contrario, devuelve un error.
 *
 * @param int $idUsuario El ID del usuario cuyo correo electrónico se actualizará.
 * @param string $mail El nuevo correo electrónico del usuario.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo de error en caso contrario.
 */
public function updateUserMail($idUsuario, $mail) 
{
        // Comprobamos si el email ya existe en otro usuario
        $checkStmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
        $checkStmt->execute([
            ':email' => $mail,
            ':id' => $idUsuario
        ]);
        $existingUser = $checkStmt->fetch();
    
        if ($existingUser) {
            // Si el email ya está en otro usuario, devolvemos un error
            return ['error' => 'emailDup'];
        }
    
        // Consulta SQL para actualizar el email del usuario
        $query = "UPDATE usuarios SET email = :email WHERE id = :id";
    
        try {
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([
                ':email' => $mail,
                ':id' => $idUsuario
            ]);
    
            return $success;
    
        } catch (PDOException $e) {
            return ['error' => 'NotPosibleToUpdateEmail'];
        }
}



/**
 * Actualiza la contraseña de un usuario en la base de datos.
 *
 * Este método actualiza la contraseña de un usuario específico utilizando su ID. Primero verifica
 * que el usuario exista en la base de datos. Luego, encripta la nueva contraseña y la actualiza en la
 * base de datos. Si la actualización es exitosa, devuelve `true`. Si no se encuentra el usuario o
 * ocurre algún error, devuelve un mensaje de error.
 *
 * @param int $idUsuario El ID del usuario cuya contraseña se actualizará.
 * @param string $pass La nueva contraseña del usuario.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */
public function updateUserPass($idUsuario, $pass) 
{
        // Verificamos si el usuario existe
        $checkStmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
        $checkStmt->execute([':id' => $idUsuario]);
        $existingUser = $checkStmt->fetch();
    
        if (!$existingUser) {
            return ['error' => 'userNotFound'];
        }
    
        // Encriptamos la nueva contraseña
        $hashedPassword = password_hash($pass, PASSWORD_BCRYPT);
    
        try {
            // Actualizamos la contraseña en la base de datos
            $stmt = $this->pdo->prepare("UPDATE usuarios SET pass = :pass WHERE id = :id");
            $success = $stmt->execute([
                ':pass' => $hashedPassword,
                ':id' => $idUsuario
            ]);
    
            if ($success) {
                return true;
            } else {
                return ['error' => 'updateFailed'];
            }
    
        } catch (PDOException $e) {
            return ['error' => 'Error updating password: ' . $e->getMessage()];
        }
}



/**
 * Elimina el perfil de un usuario de la base de datos.
 *
 * Este método elimina un usuario específico de la base de datos mediante su ID. Primero verifica si
 * el usuario existe en la base de datos. Si el usuario existe, procede a eliminarlo. Si la eliminación
 * es exitosa, devuelve `true`. Si no se encuentra el usuario o ocurre algún error, devuelve un mensaje
 * de error.
 *
 * @param int $userId El ID del usuario cuyo perfil se eliminará.
 *
 * @return mixed `true` si la eliminación fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */

public function deleteUserProfile($userId) 
{
        try {
            // Verificamos si el usuario existe
            $checkStmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
            $checkStmt->execute([':id' => $userId]);
            $existingUser = $checkStmt->fetch();
    
            if (!$existingUser) {
                return ['error' => 'userNotFound'];
            }
    
            // Eliminamos el usuario
            $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $success = $stmt->execute([':id' => $userId]);
    
            if ($success) {
                return true;
            } else {
                return ['error' => 'deleteFailed'];
            }
    
        } catch (PDOException $e) {
            return ['error' => 'Error deleting user: ' . $e->getMessage()];
        }
}
    


/**
 * Actualiza el rol y estado de suscripción de un usuario a "profesor".
 *
 * Este método cambia el rol de un usuario a "profesor" y su estado de suscripción a "activo". Si la 
 * actualización es exitosa, devuelve `true`. Si ocurre un error durante el proceso, devuelve un mensaje
 * de error.
 *
 * @param int $idUsuario El ID del usuario cuyo rol y estado de suscripción se actualizarán.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */
public function updateUserRoleToTeacher($idUsuario) 
{
        try {
            // Definir los valores fijos
            $Rol = "profesor";
            $estadoSuscripcion = "activo";
    
            // Consulta SQL para actualizar el rol y estado de suscripción
            $query = "UPDATE usuarios SET tipo = :tipo, estado_suscripcion = :estado WHERE id = :id";
            
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':tipo' => $Rol,
                ':estado' => $estadoSuscripcion,
                ':id' => $idUsuario
            ]);
    
            return $success; // Devuelve true si la actualización fue exitosa
    
        } catch (PDOException $e) {
            return ['error' => 'Error al actualizar el rol: ' . $e->getMessage()];
        }
}



/**
 * Degrada el rol y estado de suscripción de un usuario a "normal" y "cancelado".
 *
 * Este método cambia el rol de un usuario a "normal" y su estado de suscripción a "cancelado". Si la 
 * actualización es exitosa, devuelve `true`. Si ocurre un error durante el proceso, devuelve un mensaje
 * de error.
 *
 * @param int $idUsuario El ID del usuario cuyo rol y estado de suscripción se actualizarán.
 *
 * @return mixed `true` si la actualización fue exitosa, un arreglo con un mensaje de error en caso contrario.
 */
public function degradeUserRoleToCanceled($idUsuario) 
{
        try {
            // Definir los valores fijos
            $Rol = "normal";
            $estadoSuscripcion = "cancelado";
    
            // Consulta SQL para actualizar el rol y estado de suscripción
            $query = "UPDATE usuarios SET tipo = :tipo, estado_suscripcion = :estado WHERE id = :id";
            
            // Preparamos la consulta
            $stmt = $this->pdo->prepare($query);
    
            // Ejecutamos la consulta con los valores proporcionados
            $success = $stmt->execute([
                ':tipo' => $Rol,
                ':estado' => $estadoSuscripcion,
                ':id' => $idUsuario
            ]);
    
            return $success; // Devuelve true si la actualización fue exitosa
    
        } catch (PDOException $e) {
            return ['error' => 'Error al actualizar el rol: ' . $e->getMessage()];
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
            $nuevaImgUrl = "http://$ipServidor/classbridgeapi/uploads/profiles/000/profile.png";
    
            // Consulta para actualizar todos los usuarios
            $query = "UPDATE usuarios SET img_url = :nueva_url";
    
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
    
    

}