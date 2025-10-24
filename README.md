# router *functions*

*Un sencillo router basado en funciones.*

## Registrar rutas y despachar el router

Los tipos de rutas disponibles son `GET`, `POST`, `PUT`, `PATCH` y `DELETE`.

```php
use function router_functions\{
    add_route,
    dispatch_router,
    get_request_method,
    get_request_uri,
    send_response,
};

// Una ruta GET
add_route(HTTP_GET, '/', fn() => send_response('Hola mundo'););

// Una ruta POST
add_route(HTTP_POST, '/foo', fn() => send_response('Ruta POST'););

$method = get_request_method();
$uri = get_request_uri();

try {
    dispatch_router($method, $uri);
} catch(RuntimeException $e) {
    send_response($e->getMessage(), 404);
}
```

También se pueden usar los atajos `add_get_route()`, `add_post_route()`, `add_put_route()`, `add_patch_route()` y `add_delete_route()`.

```php
// Una ruta GET
add_get_route('/', fn() => send_response('Hola mundo'););

// Una ruta POST
add_post_route('/foo', fn() => send_response('Ruta POST'););
```

Despacha el router con `dispatch_router()`, que recibe dos argumentos; el método HTTP de la petición y la URi solicitada.

## Parámetros nombrados

El router pérmite parámetros nombrados en las rutas (*wildcards*), los cuales son recuperados en un array asociativo y enviados como argumento al controlador de la ruta.

```php
add_route(HTTP_GET, '/product/{id}/{slug}', function($params) {
    send_response(sprintf('Producto %s con ID %s', $params['slug'], $params['id']));
});
```

## Devolver respuestas JSON

La función `send_json_response()` permite enviar un *array* de datos que son transformados a formato `JSON` con la cabecera HTTP correspondiente de forma automática. Esta función recibe 4 argumentos; el primero es obligatorio y los demás opcionales. Donde:

- `data`: El *array* asociativo de datos.
- `http_status`: Un número entero de estatus HTTP.
- `http_headers`: Un *array* asociativo de la forma clave => valor, donde la clave es el nombre de la cabecera y valor es el contenido de la cabecera HTTP.
- `flag`: Un número entero para opciones de transformación JSON de los datos.

```php
add_route(HTTP_GET, '/product/{id}/{slug}', function($params) {
    $data = [
        'id_product' => $params['id'],
        'description_product' => $params['slug']
    ];
    send_json_response($data);
});
```

## Renderizar vistas

La función `fetch_view()` permite devolver una plantilla como texto para ser enviada posteriormente en un `send_response()`. Se pueden enviar argumentos a la plantilla en un *array* asociativo.

```php
add_route(HTTP_GET, '/', function() {
    // Se envía el argumento `message` a la plantilla `home.view.php`
    $view = fetch_view(__DIR__.'/templates/home.view.php', ['message' => 'Hola mundo']);
    // Se envía con la cabecera HTTP que especifica que es contenido HTML
    send_response(content: $view, http_headers: ['Content-TYpe' => 'text/html;charset=utf-8']);
});
```

Con `set_views_directory()` especifica un directorio default donde buscar las plantillas. De esta forma solo se envía el nombre de la plantilla.

```php
add_route(HTTP_GET, '/', function() {
    send_response(content: fetch_view('home.view.php'), http_headers: ['Content-TYpe' => 'text/html;charset=utf-8']);
});

set_views_directory(__DIR__.'/templates');
```

>[!NOTE]
>Puede definirse en cualquier lugar antes de invocar `dispatch_router()` ya que la vista se renderiza en tiempo de ejecución

## Grupos de rutas

De fine grupos de rutas con un mismo prefijo con `add_routes_group()`. Define el prefijo y una función anónima con la definición de las rutas. Todas las rutas herederan el prefijo especificado.

```php
add_route_group('/admin', function() {
    add_route(HTTP_GET, '/', function() {
        send_json_response(['message'=> 'ADMIN AREA']);
    });

    add_route(HTTP_GET, '/users', function() {
        send_json_response(['message'=> 'USERS AREA']);
    });
    
    // También se pueden anidar sub grupos
    add_route_group('/superadmin', function() {
        add_route(HTTP_GET, '/', function() {
            send_json_response(['message'=> 'ADMIN->SUPERADMIN HOME AREA']);
        });

        add_route(HTTP_GET, '/all_users', function() {
            send_json_response(['message'=> 'ADMIN->SUPERADMIN ALL USERS AREA']);
        });
    });
});
```

## Prefijo global

Asigna un prefijo global al router con `set_router_prefix()`. Este prefijo se heredará a todas las rutas que se declaren; aun dentro de los grupos de rutas.

```php
set_router_prefix('/api/v1/');
```

>[!NOTE]
>Puede definirse en cualquier lugar antes de invocar `dispatch_router()` ya que se evalua en tiempo de ejecución

## Globales de PHP

Las siguientes funciones permiten recuperar valores de una solicitud HTTP. Se especifica el nombre de la variable y si no existe devolverá un valor default especificado. Si se invican sin argumentos devolverá el *array* entero de datos correspondientes.

- `get_query(?string $key = null, mixed $default = null)`: Devuelve una variable de `$_GET`.
- `get_post(?string $key = null, mixed $default = null)`: Devuelve una variable de `$_POST`.
- `get_server(?string $key = null, mixed $default = null)`: Devuelve una variable de `$_SERVER`.
- `get_files(?string $key = null, mixed $default = null)`: Devuelve una variable de `$_FILES`.
- `get_cookie(?string $key = null, mixed $default = null)`: Devuelve una variable de `$_COOKIE`.

>[!IMPORTANT]
>No se incluye `$_SESSION` ya que existen funciones nativas para manejo de sesiones; a excepción de la asignación de variables de sesión manualmente.