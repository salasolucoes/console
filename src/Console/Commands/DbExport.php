<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Sala\Console\Console\DatabaseExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:export', description: 'Exporta a base de dados para SQL (PHP puro, sem mysqldump)')]
class DbExport extends Command
{
    public function __construct(
        private DatabaseExporter $exporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Caminho do ficheiro SQL (default: storage/dumps/<DB>_<data>.sql)')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Imprime o SQL no terminal em vez de gravar em ficheiro')
            ->addOption('structure-only', null, InputOption::VALUE_NONE, 'Exporta apenas a estrutura, sem dados')
            ->addOption('no-db-header', null, InputOption::VALUE_NONE, 'Omite CREATE DATABASE/USE (importar direto numa BD escolhida, ex.: phpMyAdmin do InfinityFree)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $_ENV['DB_DATABASE'] ?? '';
        if (! is_string($database) || $database === '') {
            $output->writeln('<error>DB_DATABASE não definido no ficheiro .env.</error>');

            return Command::FAILURE;
        }

        try {
            $dump = $this->exporter->dump(
                $database,
                $input->getOption('structure-only') === true,
                $input->getOption('no-db-header') === true,
            );
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($input->getOption('stdout')) {
            $output->write($dump);

            return Command::SUCCESS;
        }

        $path = $this->resolveOutputPath($input->getOption('output'), $database);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $output->writeln(sprintf('<error>Não foi possível criar o directório %s.</error>', $directory));

            return Command::FAILURE;
        }

        if (file_put_contents($path, $dump) === false) {
            $output->writeln(sprintf('<error>Não foi possível gravar o ficheiro %s.</error>', $path));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Base de dados exportada para %s (%d bytes).</info>', $path, strlen($dump)));

        return Command::SUCCESS;
    }

    private function resolveOutputPath(mixed $option, string $database): string
    {
        if (is_string($option) && $option !== '') {
            return $option;
        }

        $root = defined('BASE_PATH') ? BASE_PATH : getcwd();

        return $root.'/storage/dumps/'.$database.'_'.date('Ymd_His').'.sql';
    }
}
