<?php

declare(strict_types=1);

namespace Sala\Console;

use Psy\Shell;
use Sala\Console\Console\Commands\ConfigPublish;
use Sala\Console\Console\Commands\DbCreate;
use Sala\Console\Console\Commands\DbDrop;
use Sala\Console\Console\Commands\DbExport;
use Sala\Console\Console\Commands\DbSeed;
use Sala\Console\Console\Commands\MakeController;
use Sala\Console\Console\Commands\MakeMiddleware;
use Sala\Console\Console\Commands\MakeMigration;
use Sala\Console\Console\Commands\MakeModel;
use Sala\Console\Console\Commands\MakeRepository;
use Sala\Console\Console\Commands\MakeSeed;
use Sala\Console\Console\Commands\MakeService;
use Sala\Console\Console\Commands\MakeView;
use Sala\Console\Console\Commands\Migrate;
use Sala\Console\Console\Commands\MigrateBreakpoint;
use Sala\Console\Console\Commands\MigrateRollback;
use Sala\Console\Console\Commands\MigrateStatus;
use Sala\Console\Console\Commands\Serve;
use Sala\Console\Console\Commands\StubPublish;
use Sala\Console\Console\Commands\Tinker;
use Sala\Console\Console\DatabaseExporter;
use Sala\Console\Console\DatabaseManager;
use Sala\Console\Console\PhinxRunner;
use Sala\Console\Console\StubService;
use Symfony\Component\Console\Application;

class ConsoleApplication extends Application
{
    private ?DatabaseManager $dbManager = null;

    private ?PhinxRunner $phinxRunner = null;

    private ?StubService $stubService = null;

    private array $config = [];

    public function __construct(string $name = 'Console', string $version = '1.0.0')
    {
        parent::__construct($name, $version);
        $this->registerCoreCommands();
    }

    public function registerCoreCommands(): void
    {
        // Comandos sem dependências
        $this->addCommand(new Serve);
        $this->addCommand(new StubPublish);
        $this->addCommand(new ConfigPublish);

        // tinker/shell só é registado se o psysh estiver disponível.
        if (class_exists(Shell::class)) {
            $this->addCommand(new Tinker);
        }

        // Serviços serão injetados via setSharedServices
        // Os comandos serão registrados lazy quando os serviços estiverem disponíveis
    }

    public function setSharedServices(
        DatabaseManager $dbManager,
        PhinxRunner $phinxRunner,
        StubService $stubService
    ): void {
        $this->dbManager = $dbManager;
        $this->phinxRunner = $phinxRunner;
        $this->stubService = $stubService;

        // Registra comandos que dependem dos serviços
        $this->registerServiceCommands();
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    private function registerServiceCommands(): void
    {
        if (! $this->dbManager || ! $this->phinxRunner || ! $this->stubService) {
            return;
        }

        $exporter = new DatabaseExporter($this->dbManager);

        // Comandos com DatabaseManager
        $this->addCommand(new DbCreate($this->dbManager));
        $this->addCommand(new DbDrop($this->dbManager));
        $this->addCommand(new DbExport($exporter));

        // Comandos com PhinxRunner
        $this->addCommand(new DbSeed($this->phinxRunner));
        $this->addCommand(new MigrateRollback($this->phinxRunner));
        $this->addCommand(new MigrateStatus($this->phinxRunner));
        $this->addCommand(new MigrateBreakpoint($this->phinxRunner));
        $this->addCommand(new MakeMigration($this->phinxRunner));
        $this->addCommand(new MakeSeed($this->phinxRunner));

        // Comandos com StubService
        $this->addCommand(new MakeController($this->stubService));
        $this->addCommand(new MakeMiddleware($this->stubService));
        $this->addCommand(new MakeModel($this->stubService));
        $this->addCommand(new MakeRepository($this->stubService));
        $this->addCommand(new MakeService($this->stubService));
        $this->addCommand(new MakeView($this->stubService));

        // Comandos com dependências múltiplas
        $this->addCommand(new Migrate($this->dbManager, $this->phinxRunner));
    }
}
