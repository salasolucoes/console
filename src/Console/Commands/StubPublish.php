<?php

declare(strict_types=1);

namespace Sala\Console\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'stub:publish', description: 'Publica stubs do pacote para o projeto (permite customização)')]
class StubPublish extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceDir = __DIR__.'/../../../stubs';
        $targetDir = getcwd().'/stubs';

        if (! is_dir($sourceDir)) {
            $output->writeln("<error>Diretório de stubs do pacote não encontrado: {$sourceDir}</error>");

            return Command::FAILURE;
        }

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
            $output->writeln("<info>Diretório criado: {$targetDir}</info>");
        }

        $files = glob($sourceDir.'/*.stub');
        $count = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            $target = $targetDir.'/'.$filename;

            if (file_exists($target)) {
                $output->writeln("<comment>Já existe: {$filename} (pulando)</comment>");

                continue;
            }

            copy($file, $target);
            $output->writeln("<info>Publicado: {$filename}</info>");
            $count++;
        }

        if ($count === 0) {
            $output->writeln('<comment>Nenhum stub novo para publicar.</comment>');
        } else {
            $output->writeln(sprintf('<info>%d stub(s) publicado(s) em %s</info>', $count, $targetDir));
        }

        return Command::SUCCESS;
    }
}
