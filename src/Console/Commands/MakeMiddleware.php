<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Sala\Console\Console\StubService;
use Sala\Console\ConsoleApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:middleware', description: 'Cria um Middleware')]
class MakeMiddleware extends Command
{
    public function __construct(
        private StubService $stubs,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Nome(s) da classe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($input->getArgument('name') as $name) {
            $class = $this->stubs->studly($name);

            if (! str_ends_with($class, 'Middleware')) {
                $class .= 'Middleware';
            }

            $config = $this->getApplication() instanceof ConsoleApplication
                ? $this->getApplication()->getConfig()
                : [];
            $basePath = $config['paths']['middlewares'] ?? 'app/Http/Middleware';
            $namespace = $config['namespaces']['middlewares'] ?? 'App\\Http\\Middleware';

            $path = getcwd()."/{$basePath}/{$class}.php";

            $content = $this->stubs->renderStub('middleware', [
                'namespace' => $namespace,
                'class' => $class,
            ]);

            $this->stubs->putClassFile($path, $content, $output);
        }

        return Command::SUCCESS;
    }
}
