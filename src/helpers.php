<?php

declare(strict_types=1);

namespace router_helpers;

if (!function_exists('env')) {
    /**
     * Devuelve una variabe de entorno; si no existe devuelve el valor default especificado
     * 
     * @param string $name Nombre de la variable
     * @param mixed $default Valor default a devolver
     * @return mixed
     */
    function env(string $name, mixed $default = null)
    {
        return $_ENV[trim($name)] ?? $default;
    }
}

if(!function_exists('session_started')) {
    /**
     * Devuelve `true` si ya existe una sesión activa; o `false` en caso contrario.
     * 
     * @return bool
     */
    function session_started(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}

if(!function_exists('session_start_once')) {
    /**
     * Inicia una sesión si no existe ya una activa.
     * 
     * @return void
     */
    function session_start_once() {
        if (!session_started()) {
            session_start();
        }
    }
}

if (!function_exists('session_get')) {
    function session_get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[trim($key)] ?? $default;
    }
}

if (!function_exists('session_set')) {
    function session_set(string $key, mixed $value): void
    {
        $_SESSION[trim($key)] = $value;
    }
}

if (!function_exists('session_has')) {
    function session_has(string $key): bool
    {
        return array_key_exists(trim($key), $_SESSION);
    }
}

if (!function_exists('session_valid')) {
    function session_valid(string $key): bool
    {
        return session_has($key) && !empty($_SESSION[trim($key)]) && null !== $_SESSION[trim($key)];
    }
}

if (!function_exists('session_clear')) {
    function session_clear(string $key): void
    {
        $_SESSION = [];
    }
}

if (!function_exists('get_php_global')) {
    /**
     * Devuelve una variable de alguna de los globales PHP ($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE)
     * 
     * @param array $global El array global solicitado
     * @param ?string $key El nombre de la variable a recuperar
     * @param mixed $default Valor default a devolver si la variable no existe
     * @return mixed
     */
    function get_php_global(array $global, ?string $key = null, mixed $default = null): mixed
    {
        return is_null($key) ? $global : (isset($global[trim($key)]) ? $global[trim($key)] : $default);
    }
}

if (!function_exists('get_query')) {
    /**
     * Devuelve una variable de $_GET por nombre; si no existe devuelve un valor default. 
     * Si no se define un nombre se devuelve todo el array $_GET
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor default a devolver si no existe la variable
     * @return mixed
     */
    function get_query(?string $key = null, mixed $default = null): mixed
    {
        return get_php_global($_GET, $key, $default);
    }
}

if (!function_exists('get_post')) {
    /**
     * Devuelve una variable de $_POST por nombre; si no existe devuelve un valor default. 
     * Si no se define un nombre se devuelve todo el array $_POST
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor default a devolver si no existe la variable
     * @return mixed
     */
    function get_post(?string $key = null, mixed $default = null)
    {
        return get_php_global($_POST, $key, $default);
    }
}

if (!function_exists('get_server')) {
    /**
     * Devuelve una variable de $_SERVER por nombre; si no existe devuelve un valor default. 
     * Si no se define un nombre se devuelve todo el array $_SERVER
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor default a devolver si no existe la variable
     * @return mixed
     */
    function get_server(?string $key = null, mixed $default = null)
    {
        return get_php_global($_SERVER, $key, $default);
    }
}

if (!function_exists('get_files')) {
    /**
     * Devuelve una variable de $_FILES por nombre; si no existe devuelve un valor default. 
     * Si no se define un nombre se devuelve todo el array $_FILES
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor default a devolver si no existe la variable
     * @return mixed
     */
    function get_files(?string $key = null, mixed $default = null)
    {
        return get_php_global($_FILES, $key, $default);
    }
}

if (!function_exists('get_cookie')) {
    /**
     * Devuelve una variable de $_COOKIE por nombre; si no existe devuelve un valor default. 
     * Si no se define un nombre se devuelve todo el array $_COOKIE
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor default a devolver si no existe la variable
     * @return mixed
     */
    function get_cookie(?string $key = null, mixed $default = null)
    {
        return get_php_global($_COOKIE, $key, $default);
    }
}

if(!function_exists('get_request_header')) {
    function get_request_header(string $key, mixed $default = null): mixed{
        return getallheaders()[trim($key)] ?? $default;
    }
}

if(!function_exists('send_response')) {
    /**
     * Envía una respuesta desde el servidor
     * 
     * @param string $content Cuerpo de la respuesta
     * @param int $htpp_status Código númerico entero de estatus HTTP
     * @param array<string, string> $http_headers Definición de encabezados HTTP de la respuesta, de la forma `[clave => valor]`
     * @return void
     */
    function send_response(string $content, int $http_status = 200, array $http_headers = []): void
    {
        http_response_code($http_status);
    
        if (!headers_sent()) {
            foreach ($http_headers as $name => $value) {
                header("$name: $value");
            }
        }
    
        echo $content;
    }
}

if(!function_exists('send_json_response')) {
    /**
     * Envía una respuesta en formato JSON
     * 
     * @param array<string, mixed> $data Datos a ser codificados a JSON
     * @param int $http_status Código númerico entero de estatus HTTP
     * @param array<string, string> $http_headers Definición de encabezados HTTP de la respuesta, de la forma `[clave => valor]`
     * @param int $flag Opciones al codificar los datos
     * @return void
     */
    function send_json_response(array $data, int $http_status = 200, array $http_headers = [], int $flag = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT): void
    {
        $json = json_encode($data, $flag);
        $headers = array_merge($http_headers, ['Content-Type' => 'application/json;charset=utf-8']);
        send_response($json, $http_status, $headers);
    }
}

if(!function_exists('send_html_response')) {
    /**
     * Envía una respuesta como contenido HTML
     * 
     * @param string $data Cadena HTML a ser enviada
     * @param int $http_status Código númerico entero de estatus HTTP
     * @param array<string, string> $http_headers Definición de encabezados HTTP de la respuesta, de la forma `[clave => valor]`
     * @return void
     */
    function send_html_response(string $data, int $http_status = 200, array $http_headers = []): void
    {
        $headers = array_merge($http_headers, ['Content-Type' => 'text/html;charset=utf-8']);
        send_response($data, $http_status, $headers);
    }
}

if(!function_exists('get_request_method')) {
    /**
     * Devuelve el actual método HTTP de una petición
     * 
     * @return string
     */
    function get_request_method(): string
    {
        return get_server('REQUEST_METHOD');
    }
}

if(!function_exists('get_request_uri')) {
    /**
     * Devuelve la actual URI solicitada
     * 
     * @return string
     */
    function get_request_uri()
    {
        return get_server('REQUEST_URI');
    }
}