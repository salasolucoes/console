<?php

declare(strict_types=1);

namespace Sala\Console\Contracts;

interface PhinxRunnerInterface
{
    public function run(string $action, ?string $name = null, array $extra = []): void;
}
