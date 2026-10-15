<?php

declare(strict_types=1);

return [
    'paths' => [
        'controllers' => 'app/Http/Controllers',
        'models' => 'app/Infrastructure/Persistence/Eloquent',
        'repositories' => 'app/Infrastructure/Persistence/Repositories',
        'services' => 'app/Application/Services',
        'middlewares' => 'app/Http/Middleware',
        'views' => 'resources/views',
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds',
        'stubs' => 'stubs',
    ],
    'namespaces' => [
        'controllers' => 'App\\Http\\Controllers',
        'models' => 'App\\Infrastructure\\Persistence\\Eloquent',
        'repositories' => 'App\\Infrastructure\\Persistence\\Repositories',
        'services' => 'App\\Application\\Services',
        'middlewares' => 'App\\Http\\Middleware',
    ],
    'suffixes' => [
        'controller' => 'Controller',
        'model' => '',
        'service' => 'Service',
        'middleware' => 'Middleware',
        'repository' => 'Repository',
    ],
    'tinker' => [
        // Mensagem personalizada mostrada ao abrir a shell (null = padrão do pacote).
        'startup' => null,
        // Ficheiros PHP extra incluídos na shell (relativos à raiz do projeto).
        'includes' => [],
        // Variáveis extra no scope: nome => valor literal, classe ou binding do container.
        'variables' => [],
        // Diretório de histórico/config do psysh (relativo à raiz do projeto).
        'history' => 'storage/psysh',
        // Regista os casters Eloquent (Model/Collection) para dumps legíveis.
        'casters' => true,
    ],
];
