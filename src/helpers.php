<?php

declare(strict_types=1);

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
