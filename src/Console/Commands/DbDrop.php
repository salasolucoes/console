<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Sala\Console\Console\DatabaseManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:drop', description: 'Elimina a base de dados definida em DB_DATABASE')]
class DbDrop extends Command
{
    public function __construct(
        private DatabaseManager $db,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = (string) ($_ENV['DB_DATABASE'] ?? '');
        $this->db->assertValidName($database);
        $this->db->drop($database);

        $output->writeln("<info>Base de dados '{$database}' eliminada (se existia).</info>");

        return Command::SUCCESS;
    }
}
