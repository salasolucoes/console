<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Helpers do tinker
|--------------------------------------------------------------------------
|
| Este ficheiro é incluído pela shell do PsySH (e carregado pelo comando
| `tinker` antes de executar código). Define helpers globais no estilo
| Laravel, protegidos por `function_exists()` para nunca colidirem com
| funções já existentes no projeto.
|
*/

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

if (! function_exists('db')) {
    /**
     * Acede à ligação Eloquent (Capsule) ou a um query builder.
     *
     * Uso: db()->table('users')->get(), db()->select('select 1').
     */
    function db(?string $connection = null): Connection
    {
        return Capsule::connection($connection);
    }
}
