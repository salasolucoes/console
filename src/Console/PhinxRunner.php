<?php

declare(strict_types=1);

namespace Sala\Console\Console;

use Sala\Console\Contracts\PhinxRunnerInterface;

class PhinxRunner implements PhinxRunnerInterface
{
    private ?string $phinxPath = null;

    private array $config = [];

    public function __construct(?string $phinxPath = null, array $config = [])
    {
        $this->phinxPath = $phinxPath;
        $this->config = $config;
    }

    public function setPhinxPath(string $path): void
    {
        $this->phinxPath = $path;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function run(string $action, ?string $name = null, array $extra = []): void
    {
        $phinx = $this->phinxPath ?? $this->resolvePhinxPath();
        $command = "php {$phinx} {$action}";

        if ($name) {
            $command .= ' '.escapeshellarg($name);
        }

        foreach ($extra as $flag) {
            $command .= ' '.$flag;
        }

        // Adiciona configuração do phinx se fornecida
        if (! empty($this->config['configuration'])) {
            $command .= ' --configuration='.escapeshellarg($this->config['configuration']);
        }

        passthru($command);
    }

    private function resolvePhinxPath(): string
    {
        // Tenta encontrar vendor/bin/phinx relativo ao pacote ou ao cwd
        $paths = [
            __DIR__.'/../../../../vendor/bin/phinx', // pacote instalado
            getcwd().'/vendor/bin/phinx',            // projeto consumidor
            __DIR__.'/../../../vendor/bin/phinx',    // mono-repo
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return escapeshellarg($path);
            }
        }

        // Fallback: assume que está no PATH
        return escapeshellarg('phinx');
    }
}
