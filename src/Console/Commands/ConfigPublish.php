<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:publish', description: 'Publica config/console.php do pacote para o projeto')]
class ConfigPublish extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceFile = __DIR__.'/../../../config/console.php';
        $targetDir = getcwd().'/config';
        $targetFile = $targetDir.'/console.php';

        if (! file_exists($sourceFile)) {
            $output->writeln("<error>Arquivo de config do pacote não encontrado: {$sourceFile}</error>");

            return Command::FAILURE;
        }

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
            $output->writeln("<info>Diretório criado: {$targetDir}</info>");
        }

        if (file_exists($targetFile)) {
            $output->writeln('<comment>Já existe: config/console.php (pulando)</comment>');

            return Command::SUCCESS;
        }

        copy($sourceFile, $targetFile);
        $output->writeln('<info>Publicado: config/console.php</info>');

        return Command::SUCCESS;
    }
}
