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

#[AsCommand(name: 'make:controller', description: 'Cria um Controller')]
class MakeController extends Command
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

            if (! str_ends_with($class, 'Controller')) {
                $class .= 'Controller';
            }

            $config = $this->getApplication() instanceof ConsoleApplication
                ? $this->getApplication()->getConfig()
                : [];
            $basePath = $config['paths']['controllers'] ?? 'app/Http/Controllers';
            $namespace = $config['namespaces']['controllers'] ?? 'App\\Http\\Controllers';

            $path = getcwd()."/{$basePath}/{$class}.php";

            $content = $this->stubs->renderStub('controller', [
                'namespace' => $namespace,
                'class' => $class,
            ]);

            $this->stubs->putClassFile($path, $content, $output);
        }

        return Command::SUCCESS;
    }
}
