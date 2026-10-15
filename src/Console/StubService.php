<?php

declare(strict_types=1);

namespace Sala\Console\Console;

use RuntimeException;
use Sala\Console\Contracts\StubServiceInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StubService implements StubServiceInterface
{
    private string $stubsDir;

    public function __construct(?string $stubsDir = null)
    {
        $this->stubsDir = $stubsDir ?? __DIR__.'/../../stubs';
    }

    public function setStubsDir(string $dir): void
    {
        $this->stubsDir = $dir;
    }

    public function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords(strtolower($value));

        return str_replace(' ', '', $value);
    }

    public function renderStub(string $stubName, array $replacements): string
    {
        // 1. Tenta stub do projeto (override)
        $projectStub = getcwd().'/stubs/'.$stubName.'.stub';
        if (file_exists($projectStub)) {
            $stubPath = $projectStub;
        } else {
            // 2. Fallback: stub do pacote
            $stubPath = $this->stubsDir.'/'.$stubName.'.stub';
        }

        if (! file_exists($stubPath)) {
            throw new RuntimeException("Stub não encontrado: {$stubName} (procurado em {$projectStub} e {$this->stubsDir})");
        }

        $content = file_get_contents($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{ '.$key.' }}', $value, $content);
        }

        return $content;
    }

    public function putClassFile(string $path, string $content, OutputInterface $output): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_exists($path)) {
            $output->writeln("<comment>Ficheiro já existe: {$path}</comment>");

            return;
        }

        file_put_contents($path, $content);
        $output->writeln("<info>Criado: {$path}</info>");
    }
}
