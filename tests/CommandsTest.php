<?php

declare(strict_types=1);

use Sala\Console\Console\DatabaseManager;
use Sala\Console\Console\PhinxRunner;
use Sala\Console\Console\StubService;
use Sala\Console\ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    $this->app = new ConsoleApplication('Test Console', '1.0.0');
    $this->dbManager = new DatabaseManager([]);
    $this->phinxRunner = new PhinxRunner;
    $this->stubService = new StubService;
    $this->app->setSharedServices($this->dbManager, $this->phinxRunner, $this->stubService);
});

it('registers all core commands', function () {
    $commands = $this->app->all();

    foreach ([
        'serve',
        'db:create',
        'db:drop',
        'db:export',
        'seed:run',
        'migrate',
        'rollback',
        'status',
        'breakpoint',
        'make:migration',
        'make:seed',
        'make:controller',
        'make:model',
        'make:service',
        'make:middleware',
        'make:repository',
        'make:view',
        'stub:publish',
        'config:publish',
        'tinker',
    ] as $name) {
        expect($commands)->toHaveKey($name);
    }
});

it('registers tinker with the shell alias', function () {
    $commands = $this->app->all();

    expect($commands)->toHaveKey('tinker')
        ->and($commands)->toHaveKey('shell')
        ->and($this->app->find('shell')->getName())->toBe('tinker');
});

it('exposes tinker options', function () {
    $definition = $this->app->find('tinker')->getDefinition();

    expect($definition->hasOption('execute'))->toBeTrue()
        ->and($definition->hasOption('no-interactive'))->toBeTrue()
        ->and($definition->hasOption('raw-output'))->toBeTrue();
});

it('exposes the make:controller command', function () {
    $command = $this->app->find('make:controller');
    $tester = new CommandTester($command);

    expect($command->getName())->toBe('make:controller');
});

it('renders stubs with replacements', function () {
    $content = $this->stubService->renderStub('model', [
        'namespace' => 'App\\Test',
        'class' => 'User',
        'table' => 'users',
    ]);

    expect($content)
        ->toContain('namespace App\\Test;')
        ->toContain('class User')
        ->toContain("protected \$table = 'users';");
});

it('converts strings to studly case', function () {
    expect($this->stubService->studly('user_model'))->toBe('UserModel')
        ->and($this->stubService->studly('user-model'))->toBe('UserModel')
        ->and($this->stubService->studly('user model'))->toBe('UserModel')
        // studly() lowercases first, so 'UserModel' -> 'Usermodel'
        ->and($this->stubService->studly('UserModel'))->toBe('Usermodel');
});

it('validates database names', function () {
    $this->dbManager->assertValidName('valid_name');
    $this->dbManager->assertValidName('valid123');

    expect(fn () => $this->dbManager->assertValidName(''))
        ->toThrow(RuntimeException::class);

    expect(fn () => $this->dbManager->assertValidName('invalid-name'))
        ->toThrow(RuntimeException::class);
});
