<?php

declare(strict_types=1);

namespace Sala\Console\Console;

use PDO;
use RuntimeException;
use Sala\Console\Contracts\DatabaseManagerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DatabaseManager implements DatabaseManagerInterface
{
    private ?PDO $pdo = null;

    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function assertValidName(string $name): void
    {
        if ($name === '') {
            throw new RuntimeException('DB_DATABASE não pode ser vazio.');
        }

        if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Nome de base de dados inválido.');
        }
    }

    public function exists(string $name): bool
    {
        $stmt = $this->pdo()->query(
            'SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '.$this->pdo()->quote($name)
        );

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name): void
    {
        $this->pdo()->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $name
        ));
    }

    /**
     * Cria a base de dados se não existir.
     * Pergunta ao utilizador antes de criar. Se negar, lança RuntimeException.
     */
    public function createOrFail(string $name, InputInterface $input, OutputInterface $output): void
    {
        if ($this->exists($name)) {
            return;
        }

        $helper = new QuestionHelper;
        $question = new ConfirmationQuestion(
            sprintf("Base de dados '%s' não existe. Criar? [Y/n] ", $name),
            true,
        );

        if (! $helper->ask($input, $output, $question)) {
            throw new RuntimeException('Cancelado.');
        }

        $this->create($name);
        $output->writeln("<info>Base de dados '{$name}' criada.</info>");
    }

    public function drop(string $name): void
    {
        $this->pdo()->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $name));
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $host = $this->config['host'] ?? $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $this->config['port'] ?? $_ENV['DB_PORT'] ?? 3306;
            $username = $this->config['username'] ?? $_ENV['DB_USERNAME'] ?? 'root';
            $password = $this->config['password'] ?? $_ENV['DB_PASSWORD'] ?? '';
            $driver = $this->config['driver'] ?? $_ENV['DB_CONNECTION'] ?? 'mysql';

            if ($driver !== 'mysql') {
                throw new RuntimeException("Apenas o driver 'mysql' é suportado.");
            }

            $this->pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;charset=utf8mb4',
                    (string) $host,
                    (int) $port,
                ),
                (string) $username,
                (string) $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        }

        return $this->pdo;
    }
}
