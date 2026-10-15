<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Sala\Console\Console\PhinxRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'status', description: 'Mostra o estado das migrações')]
class MigrateStatus extends Command
{
    public function __construct(
        private PhinxRunner $phinx,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->phinx->run('status');

        return Command::SUCCESS;
    }
}
