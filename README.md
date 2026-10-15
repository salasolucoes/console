# Sala Console

Console commands for Sala Framework — `make:*`, `migrate*`, `db:*`, `serve`.

## Instalação

```bash
composer require sala/console
```

## Uso

O pacote expõe o binário `console`:

```bash
# Via vendor/bin
php vendor/bin/console list

# Ou se copiado para root do projeto (post-install)
php console list
```

## Comandos disponíveis

| Comando | Descrição |
|---|---|
| `serve` | Sobe dev server PHP + Vite (`npm run dev`) |
| `make:controller` | Cria Controller |
| `make:model` | Cria Model Eloquent |
| `make:service` | Cria Service |
| `make:middleware` | Cria Middleware |
| `make:repository` | Cria Repository |
| `make:view` | Cria View Blade |
| `make:migration` | Cria migration Phinx |
| `make:seed` | Cria seeder Phinx |
| `migrate` | Executa migrações pendentes (cria DB se necessário) |
| `rollback` | Reverte última migração |
| `status` | Estado das migrações |
| `breakpoint` | Breakpoint Phinx |
| `db:create` | Cria banco via PDO |
| `db:drop` | Remove banco via PDO |
| `db:export` | Exporta banco para SQL (sem mysqldump) |
| `seed:run` | Executa seeders |
| `tinker` / `shell` | Shell interativa (REPL) para explorar a aplicação e a base de dados |
| `stub:publish` | Copia os stubs do pacote para `stubs/` do projeto (permite customizar) |
| `config:publish` | Copia `config/console.php` do pacote para `config/` do projeto |

## Tinker (`tinker` / `shell`)

Shell interativa (REPL) baseada no [PsySH](https://psysh.org) para explorar a
aplicação, os modelos e a base de dados — ao estilo do Laravel Tinker.

```bash
# Abre a shell interativa
php console tinker
php console shell        # alias

# Executa código e sai
php console tinker --execute='db()->table("users")->count()'
php console tinker -e '$app->getName()'

# Executa a partir de STDIN (não-interativo)
php console tinker < script.php
echo '1 + 1' | php console tinker

# Valores no formato var_export
php console tinker --raw-output -e '["a" => 1]'
```

No arranque ficam disponíveis:

- `$app` — instância de `Sala\Console\ConsoleApplication`
- `$config` — repositório de configuração (Illuminate), quando existe
- `db()` — helper que devolve a ligação Eloquent (`Capsule`)

Os modelos e coleções Eloquent são apresentados de forma legível (atributos
visíveis sem marcador, ocultos com `#`, relações e *appends* incluídos), através
dos *casters* registados automaticamente.

Opções: `-e, --execute`, `--no-interactive` e `-r, --raw-output`.

O histórico e a configuração do PsySH ficam guardados por projeto em
`storage/psysh/` — adicione `/storage/psysh/` ao `.gitignore`.

### Configuração (`tinker` em `config/console.php`)

```php
'tinker' => [
    'startup' => null,           // mensagem mostrada ao abrir a shell
    'includes' => [],            // ficheiros PHP extra incluídos na shell
    'variables' => [],           // variáveis extra: nome => valor/classe/binding
    'history' => 'storage/psysh',
    'casters' => true,           // casters Eloquent (Model/Collection)
],
```

## Integração no projeto

O `bin/console` do pacote é **universal**: deteta automaticamente a raiz do
projeto a partir do `vendor/autoload.php`, carrega o `.env` e os `config/*.php`,
e inicializa o Eloquent quando existe configuração de base de dados. Não é
preciso escrever um wrapper manual.

Três formas de o executar:

```bash
# 1. Via Composer (recomendado — funciona sempre)
php vendor/bin/console list

# 2. Copiado para a raiz do projeto (ex.: via script post-install do Composer)
php console list
```

Para gerar o `console` na raiz automaticamente, adicione ao `composer.json` do projeto:

```json
"scripts": {
    "post-install-cmd": [
        "@php -r \"copy('vendor/sala/console/bin/console', 'console'); chmod('console', 0755);\""
    ],
    "post-update-cmd": [
        "@php -r \"copy('vendor/sala/console/bin/console', 'console'); chmod('console', 0755);\""
    ]
}
```

> O `console` gerado é um artefacto — adicione `/console` ao `.gitignore`.

Comandos customizados do projeto podem ser registados no wrapper, se necessário:

```php
$app = new \Sala\Console\ConsoleApplication('Sala Console', '1.0.0');
// ... setSharedServices / setConfig ...
$app->addCommand(new \App\Console\Commands\MeuComando());
$app->run();
```

## Publicar stubs e config

Para customizar os templates ou a configuração sem depender dos defaults do pacote:

```bash
php console config:publish   # cria config/console.php no projeto (não sobrescreve)
php console stub:publish     # copia os stubs para stubs/ do projeto (não sobrescreve)
```

Os ficheiros já existentes nunca são sobrescritos, pelo que é seguro voltar a executar.

## Stubs customizados

Crie `stubs/{nome}.stub` na raiz do projeto para sobrescrever os defaults do pacote.

## Configuração (`config/console.php`)

```php
return [
    'paths' => [
        'controllers' => 'app/Http/Controllers',
        'models' => 'app/Infrastructure/Persistence/Eloquent',
        // ...
    ],
    'namespaces' => [
        'controllers' => 'App\\Http\\Controllers',
        // ...
    ],
    'suffixes' => [
        'controller' => 'Controller',
        'model' => '',
        // ...
    ],
];
```

## Requisitos

- PHP ^8.4
- Symfony Console ^7.0
- Phinx ^0.16
- PsySH ^0.12 (`tinker`/`shell`)
- Illuminate (Database, Container, Config, Filesystem, Events, Support) ^10|^11|^12

## Licença

MIT