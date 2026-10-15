<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Sala\Console\Console\DatabaseManager;
use Sala\Console\Console\PhinxRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate', description: 'Executa migrações pendentes')]
class Migrate extends Command
{
    public function __construct(
        private DatabaseManager $db,
        private PhinxRunner $phinx,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Versão alvo para migrar')
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Migrar até uma data específica')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mostrar queries sem executar')
            ->addOption('fake', null, InputOption::VALUE_NONE, 'Marcar migrações como executadas')
            ->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Ambiente alvo')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Ficheiro de configuração')
            ->addOption('parser', 'p', InputOption::VALUE_REQUIRED, 'Parser de configuração')
            ->addOption('seed', null, InputOption::VALUE_NONE, 'Executar seeders após migrações');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = (string) ($_ENV['DB_DATABASE'] ?? '');
        $this->db->assertValidName($database);

        try {
            $this->db->createOrFail($database, $input, $output);
        } catch (\RuntimeException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        $extra = [];

        foreach (['target', 'date', 'environment', 'configuration', 'parser'] as $opt) {
            if ($value = $input->getOption($opt)) {
                $extra[] = sprintf('--%s=%s', $opt, escapeshellarg($value));
            }
        }

        if ($input->getOption('dry-run')) {
            $extra[] = '--dry-run';
        }

        if ($input->getOption('fake')) {
            $extra[] = '--fake';
        }

        $this->phinx->run('migrate', null, $extra);

        if ($input->getOption('seed')) {
            $output->writeln('<info>Executando seeders...</info>');
            $this->phinx->run('seed:run');
        }

        return Command::SUCCESS;
    }
}
