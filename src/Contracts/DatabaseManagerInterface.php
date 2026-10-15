<?php

declare(strict_types=1);

namespace Sala\Console\Contracts;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface DatabaseManagerInterface
{
    public function assertValidName(string $name): void;

    public function exists(string $name): bool;

    public function create(string $name): void;

    public function createOrFail(string $name, InputInterface $input, OutputInterface $output): void;

    public function drop(string $name): void;

    public function pdo(): \PDO;
}
