<?php

declare(strict_types=1);

namespace router_functions;

use Closure;
use Exception;
use RuntimeException;
use stdClass;

use function router_helpers\get_request_method;
use function router_helpers\send_json_response;

define('HTTP_GET', 'GET');
define('HTTP_POST', 'POST');
define('HTTP_PUT', 'PUT');
define('HTTP_PATCH', 'PATCH');
define('HTTP_DELETE', 'DELETE');
const ROUTER_NAMESPACE = '__routes_data_collection_v1.0.0__';
$GLOBALS[ROUTER_NAMESPACE] = [];

/**
 * Asigna un prefijo de ruta global para todas las rutas registradas
 * 
 * @param string $prefix Prefijo global
 * @return void
 */
function set_router_prefix(string $prefix): void
{
    $GLOBALS[ROUTER_NAMESPACE]['router_prefix'] = format_str_path($prefix);
}

/**
 * Devuelve el actual prefijo global del router
 * 
 * @return string
 */
function get_router_prefix(): string
{
    return $GLOBALS[ROUTER_NAMESPACE]['router_prefix'] ?? '';
}

/**
 * Devuelve el resultado de ejecutar una secuencia de funciones en pipeline sobre un valor específico.
 * Ej. `pipe('strtolower', 'ucwords', 'trim')('  jOHn dOE  ')` devuelve 'John Doe'
 * 
 * @param array<Closure|string> $fns Listado de funciones a ejecutar en cadena
 * @return mixed El resultado de aplicar las funciones sobre un valor
 */
function pipe(...$fns)
{
    return fn($initial_value) =>
    array_reduce($fns, function ($accumulator, $func) {
        return call_user_func($func, $accumulator);
    }, $initial_value);
}

/**
 * Registra un grupo de rutas bajo un mismo prefijo de ruta. Permite anidamiento de grupos.
 * 
 * @param string $prefix Prefijo del grupo de rutas
 * @param Closure Función anónima con el registro de rutas
 * @return void
 */
function add_route_group(string $prefix, Closure $callback): void
{
    $current_prefix = get_last_group_prefix();
    $new_prefix = $current_prefix . format_str_path($prefix);
    set_last_group_prefix($new_prefix);
    $callback();
    set_last_group_prefix($current_prefix);
}

/**
 * Asigna un prefijo de grupo de rutas
 * 
 * @param string $group_prefix El actual prefijo del grupo de rutas
 * @return void
 */
function set_last_group_prefix(string $group_prefix): void
{
    $GLOBALS[ROUTER_NAMESPACE]['last_group_prefix'] = format_str_path($group_prefix);
}

/**
 * Devuelve el último prefijo de grupo de ruta registrado
 * 
 * @return string
 */
function get_last_group_prefix(): string
{
    return $GLOBALS[ROUTER_NAMESPACE]['last_group_prefix'] ?? '';
}

/**
 * Agrega una ruta
 * 
 * @param string $http_method Método HTTP de la ruta
 * @param string $path URI relativa de la ruta
 * @param callable $controller Controlador de la ruta
 * @return void
 */
function add_route(string $http_method, string $path, callable $controller): void
{
    $method = pipe('strtoupper', 'trim')($http_method);
    $format_path = format_str_path($path);
    $full_path = format_str_path(get_last_group_prefix() . $format_path);
    $GLOBALS[ROUTER_NAMESPACE][$method][] = [
        'path' => $full_path,
        'method' => $method,
        'controller' => $controller
    ];
}

/** Agrega una ruta de tipo de petición GET */
function add_get_route(string $path, callable $controller)
{
    add_route(HTTP_GET, $path, $controller);
}
/** Agrega una ruta de tipo de petición POST */
function add_post_route(string $path, callable $controller)
{
    add_route(HTTP_POST, $path, $controller);
}
/** Agrega una ruta de tipo de petición PUT */
function add_put_route(string $path, callable $controller)
{
    add_route(HTTP_PUT, $path, $controller);
}
/** Agrega una ruta de tipo de petición PATCH */
function add_patch_route(string $path, callable $controller)
{
    add_route(HTTP_PATCH, $path, $controller);
}
/** Agrega una ruta de tipo de petición DELETE */
function add_delete_route(string $path, callable $controller)
{
    add_route(HTTP_DELETE, $path, $controller);
}

/**
 * Inicia el router y despacha las solicitudes de rutas
 * 
 * @param string $http_request_method Método HTTP de la solicitud
 * @param string $http_request_uri URI solicitada
 * @return void
 * @throws RuntimeException Cuando la ruta solicitada no se encuentra
 */
function dispatch_router(string $http_request_method, string $http_request_uri): void
{
    static $invoked;
    if ($invoked) return;

    if ([] === $GLOBALS[ROUTER_NAMESPACE]) {
        send_json_response([
            'message' => 'Welcome to router-functions!',
            'status' => 'No routes registered',
            'documentation' => 'https://github.com/rguezque/router-functions',
            'hints' => [
                'Add routes using add_route() or shortcuts: add_get_route(), add_post_route(), add_put_route(), add_patch_route(), add_delete_route()',
                'Check your route configuration',
                'Ensure controllers are properly set up'
            ]
        ]);
        return;
    }

    $http_request_method = strtoupper($http_request_method);
    $http_request_uri = strtok($http_request_uri, '?');

    if ('/' !== $http_request_uri) {
        $http_request_uri = rtrim($http_request_uri, '/\\');
    }

    $routes = $GLOBALS[ROUTER_NAMESPACE][$http_request_method] ?? [];

    foreach ($routes as $route) {
        $full_path = format_str_path(get_router_prefix() . $route['path']);

        if (preg_match(route_to_regex($full_path), $http_request_uri, $params)) {
            array_shift($params);
            $invoked = true;
            call_user_func($route['controller'], $params);
            return;
        }
    }

    throw new RuntimeException(sprintf('The request URI "%s" do not match any route.', $http_request_uri));
}

/**
 * Genera una expresión regular para una ruta con parámetros nombrados.
 * Ejemplo: '/user/{id}/post/{slug}' => '#^/user/(?P<id>[^/]+)/post/(?P<slug>[^/]+)$#'
 *
 * @param string $path Ruta con parámetros nombrados entre llaves
 * @return string Expresión regular para hacer match con la ruta
 */
function route_to_regex(string $path): string
{
    $path = str_replace('/', '\/', $path);
    // Reemplaza {param} o {param:regex} por el grupo de captura de la regex.
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)(:([^{}]+))?\}/', '(?P<\1>[^/]+)', $path);
    // Escapar caracteres especiales y delimitar
    return '#^' . $pattern . '$#';
}

/**
 * Recupera el contenido de una plantilla para ser renderizada
 * 
 * @param string $template Archivo de plantilla de vista
 * @param array $data Variables enviadas a la plantilla
 * @return string
 */
function fetch_view(string $template, array $data = []): string
{
    if ('' !== get_views_directory()) {
        $template = rtrim(get_views_directory(), '/\\ ') . DIRECTORY_SEPARATOR . trim($template, '/\\ ');
    }
    if (!file_exists($template)) {
        throw new Exception(sprintf('No existe el archivo de plantilla "%s".', $template));
    }

    ob_start();
    extract($data);
    require $template;
    return ob_get_clean();
}

function set_views_directory(string $path): void
{
    $GLOBALS[ROUTER_NAMESPACE]['views_directory'] = $path;
}

function get_views_directory(): string
{
    return $GLOBALS[ROUTER_NAMESPACE]['views_directory'] ?? '';
}

/**
 * Convierte un array asociativo en un objeto stdClass,
 * donde cada clave del array se convierte en un atributo público.
 *
 * @param array $array Array a ser convertido a objeto
 * @return stdClass
 */
function array_to_object(array $array): stdClass
{
    $object = new stdClass();
    foreach ($array as $key => $value) {
        $object->$key = $value;
    }

    return $object;
}

/**
 * Devuelve el array de todas las rutas registradas
 * 
 * @return array
 */
function get_all_routes(): array
{
    return $GLOBALS[ROUTER_NAMESPACE];
}

/**
 * Formatea un string de ruta con la sintaxis requerida por el router
 * 
 * @param string $path Cadena de texto de la ruta
 * @return string
 */
function format_str_path(string $path): string
{
    return '/' . trim($path, '/\\ ');
}

/**
 * Configura CORS: `origin`, `methods`, `headers` y `max_age`.
 * Donde: 
 * * `origin` es el dominio origen permitido para hacer peticiones.
 * * `methods` es una cadena de texto con metodos de petición permitidos (separados por comas).
 * * `headers` es una cadena de texto con encabezados HTTP permitidos en la petición (separados por comas).
 * * `max_age` es el tiempo que dura el acceso a las peticiones
 * 
 * @param array<string, string> Configuraciones de CORS
 * @return void
 */
function cors_config(array $cors_settings = []): void
{
    $cors_default = [
        'origin' => '*',
        'methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'headers' => 'Content-Type, Authorization',
        'max_age' => 86400
    ];
    $cors = array_merge($cors_default, $cors_settings);
    // Si es una solicitud OPTIONS (pre-flight)
    if ('OPTIONS' === get_request_method()) {
        http_response_code(204); // No content
        header(sprintf('Access-Control-Allow-Origin: %s', $cors['origin']));
        header(sprintf('Access-Control-Allow-Methods: %s', $cors['methods']));
        header(sprintf('Access-Control-Allow-Headers: %s', $cors['headers']));
        header(sprintf('Access-Control-Max-Age: %s', (string) $cors['max_age']));
        return;
    }
    // Para cualquier otra solicitud
    header(sprintf('Access-Control-Allow-Origin: %s', $cors['origin']));
    header(sprintf('Access-Control-Allow-Methods: %s', $cors['methods']));
    header(sprintf('Access-Control-Allow-Headers: %s', $cors['headers']));
}


