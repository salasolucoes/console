<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'serve', description: 'Serve the application on the PHP development server')]
class Serve extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Serve the application on the PHP development server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on', '8000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');

        $output->writeln("<info>Starting Development Server:</info> http://{$host}:{$port}");
        $output->writeln('<comment>Press Ctrl+C to stop the server</comment>');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => STDOUT,
            2 => STDERR,
        ];

        $commandsToRun = [
            'php' => sprintf('php -S %s:%s -t public', $host, $port),
        ];

        if (file_exists(getcwd().'/package.json')) {
            $output->writeln('<info>Starting NPM bundler...</info>');
            $npmCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'npm.cmd run dev' : 'npm run dev';
            $commandsToRun['npm'] = $npmCommand;
        }

        $processes = [];
        foreach ($commandsToRun as $cmd) {
            $process = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                $processes[] = $process;
            }
        }

        // Loop para manter o comando vivo e escutando processos
        while (count($processes) > 0) {
            foreach ($processes as $key => $process) {
                $status = proc_get_status($process);
                if (! $status['running']) {
                    proc_close($process);
                    unset($processes[$key]);
                }
            }
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
