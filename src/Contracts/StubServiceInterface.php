<?php

declare(strict_types=1);

namespace Sala\Console\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface StubServiceInterface
{
    public function studly(string $value): string;

    public function renderStub(string $stubName, array $replacements): string;

    public function putClassFile(string $path, string $content, OutputInterface $output): void;
}
