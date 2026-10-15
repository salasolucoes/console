<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Psy\Configuration;
use Psy\Exception\BreakException;
use Psy\Shell;
use Sala\Console\Console\Tinker\EloquentCaster;
use Sala\Console\ConsoleApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'tinker',
    description: 'Shell interativa (REPL) para explorar a aplicação e a base de dados',
    aliases: ['shell'],
)]
class Tinker extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('execute', 'e', InputOption::VALUE_REQUIRED, 'Executa o código indicado e sai (ex.: -e "db()->select(\'select 1\')")')
            ->addOption('no-interactive', null, InputOption::VALUE_NONE, 'Não abre shell interativa; lê o código de STDIN')
            ->addOption('raw-output', 'r', InputOption::VALUE_NONE, 'Mostra valores no formato var_export (com --execute/STDIN)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(Shell::class)) {
            $output->writeln('<error>psysh não está instalado. Execute: composer require psy/psysh</error>');

            return Command::FAILURE;
        }

        // Garante que os helpers (db(), ...) existem também no modo --execute.
        require_once __DIR__.'/../Tinker/bootstrap.php';

        $config = $this->buildConfiguration($input);
        $shell = new Shell($config);
        $shell->setScopeVariables($this->scopeVariables());

        $code = $input->getOption('execute');

        if (is_string($code) && trim($code) !== '') {
            return $this->runCode($shell, $config, $code, (bool) $input->getOption('raw-output'), $output);
        }

        return $shell->run();
    }

    private function runCode(Shell $shell, Configuration $config, string $code, bool $rawOutput, OutputInterface $output): int
    {
        $shell->setOutput($config->getOutput());

        try {
            $result = $shell->execute($code, true);
        } catch (BreakException $e) {
            return (int) $e->getCode();
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $shell->writeReturnValue($result, $rawOutput);

        return Command::SUCCESS;
    }

    private function buildConfiguration(InputInterface $input): Configuration
    {
        $settings = $this->tinkerConfig();
        $root = $this->projectRoot();
        $historyDir = $this->absolutePath($root, (string) ($settings['history'] ?? 'storage/psysh'));

        if (! is_dir($historyDir) && ! mkdir($historyDir, 0777, true) && ! is_dir($historyDir)) {
            // Sem diretório de histórico/config: o PsySH funciona com os defaults.
        }

        $options = [
            'configDir' => $historyDir,
            'dataDir' => $historyDir,
            'updateCheck' => 'never',
            'startupMessage' => $this->startupMessage($settings),
        ];

        if ($input->getOption('no-interactive')) {
            $options['interactiveMode'] = Configuration::INTERACTIVE_MODE_DISABLED;
        }

        if ($input->getOption('raw-output')) {
            $options['rawOutput'] = true;
        }

        if (($settings['casters'] ?? true) !== false) {
            $options['casters'] = [
                Model::class => [EloquentCaster::class, 'castModel'],
                Collection::class => [EloquentCaster::class, 'castCollection'],
            ];
        }

        $includes = [__DIR__.'/../Tinker/bootstrap.php'];

        foreach ((array) ($settings['includes'] ?? []) as $include) {
            if (is_string($include) && $include !== '') {
                $includes[] = $this->absolutePath($root, $include);
            }
        }

        $options['defaultIncludes'] = $includes;

        return new Configuration($options);
    }

    /**
     * @return array<string, mixed>
     */
    private function scopeVariables(): array
    {
        $variables = [];

        $app = $this->getApplication();

        if ($app !== null) {
            $variables['app'] = $app;
        }

        $container = class_exists(Container::class) ? Container::getInstance() : null;

        if ($container !== null && $container->bound('config')) {
            $variables['config'] = $container->make('config');
        }

        $configured = $this->tinkerConfig()['variables'] ?? [];

        foreach ((array) $configured as $name => $value) {
            if (is_string($value) && $value !== '' && $container !== null
                && ($container->bound($value) || class_exists($value))) {
                try {
                    $value = $container->make($value);
                } catch (\Throwable) {
                    // Mantém o valor literal se não for possível resolver.
                }
            }

            $variables[(string) $name] = $value;
        }

        return $variables;
    }

    /**
     * @return array<string, mixed>
     */
    private function tinkerConfig(): array
    {
        $app = $this->getApplication();

        if ($app instanceof ConsoleApplication) {
            return (array) ($app->getConfig()['tinker'] ?? []);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function startupMessage(array $settings): string
    {
        $startup = $settings['startup'] ?? null;

        if (is_string($startup) && $startup !== '') {
            return $startup;
        }

        return 'Variáveis: $app, $config. Helpers: db(). "exit" para sair.';
    }

    private function projectRoot(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : getcwd();
    }

    private function absolutePath(string $root, string $path): string
    {
        if ($path === '') {
            return $root;
        }

        if (preg_match('#^(?:[A-Za-z]:[\\\\/]|[\\\\/])#', $path) === 1) {
            return $path;
        }

        return $root.'/'.ltrim($path, '/\\');
    }
}
