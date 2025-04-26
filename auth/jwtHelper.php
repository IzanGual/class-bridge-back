<?php

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

// Creamos dotenv para poder acceder a las variables de entorno en .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

// Clave secreta
define('JWT_SECRET', $_ENV['JWT_SECRET']);

/**
 * Verifica si el token es válido y lo decodifica.
 * 
 * @param string $jwt
 * @return bool|object Devuelve el objeto decodificado del token si es válido, o false si no lo es.
 */
function verifyJwt($jwt) {
    try {
        // Decodificar el JWT con la clave secreta
        return JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    } catch (ExpiredException $e) {
        return false;
    } catch (BeforeValidException $e) {
        return false;
    } catch (SignatureInvalidException $e) {
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Extrae el token JWT de la cabecera Authorization.
 * 
 * @return string|null El token JWT si existe, o null si no existe.
 */
function getJwtFromHeader() {
    $headers = getallheaders();
    return isset($headers['Authorization']) ? str_replace("Bearer ", "", $headers['Authorization']) : null;
}

/**
 * Verifica si el token JWT está presente en la solicitud y es válido.
 * 
 * @return bool Devuelve `true` si el token es válido, o `false` si no lo es.
 */
function validateToken() {
    $jwt = getJwtFromHeader();

    if (!$jwt) {
        // Si no hay token
        return false;
    }

    // Si el JWT es válido, devuelve true
    return verifyJwt($jwt) ? true : false;
}

/**
 * Obtiene el ID del usuario del token JWT si es válido.
 * 
 * @return mixed El ID del usuario si el token es válido, o false si no lo es.
 */
function getUserIdFromToken() {
    $jwt = getJwtFromHeader();

    if (!$jwt) {
        return false; // Si no hay token
    }

    // Verificamos si el token es válido y obtenemos el objeto decodificado
    $decoded = verifyJwt($jwt);

    if ($decoded) {
        // Si el token es válido, devolver el ID
        return $decoded->sub;  // Suponiendo que el JWT contiene un campo 'id'
    }

    return false; // Si el token no es válido
}

?>
