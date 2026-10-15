<?php

declare(strict_types=1);

namespace Sala\Console\Console;

use PDO;
use RuntimeException;

/**
 * Exporta uma base de dados MySQL para SQL em PHP puro (sem mysqldump).
 *
 * Gera um dump com CREATE DATABASE/USE, DROP + CREATE TABLE por tabela e
 * INSERT em lotes, com valores escapados e blobs em hexadecimal.
 */
class DatabaseExporter
{
    private const INSERT_BATCH_SIZE = 500;

    public function __construct(
        protected DatabaseManager $db,
    ) {}

    /**
     * @return string dump SQL completo (estrutura + dados, se não structureOnly)
     */
    public function dump(string $database, bool $structureOnly = false, bool $noDbHeader = false): string
    {
        $this->db->assertValidName($database);
        $pdo = $this->db->pdo();

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database,
        ));
        $pdo->exec(sprintf('USE `%s`', $database));

        $lines = [
            '-- ------------------------------------------------------------',
            sprintf('-- Dump gerado por db:export (%s)', $database),
            '-- Gerado em '.date('Y-m-d H:i:s'),
            '-- ------------------------------------------------------------',
        ];

        if (! $noDbHeader) {
            $lines[] = sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $database);
            $lines[] = sprintf('USE `%s`;', $database);
        }

        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
        $lines[] = '';

        foreach ($this->tables($pdo) as $table) {
            $lines[] = $this->createTable($pdo, $table);

            if (! $structureOnly) {
                foreach ($this->inserts($pdo, $table) as $insert) {
                    $lines[] = $insert;
                }
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';
        $lines[] = '-- Fim do dump';

        return implode("\n", $lines)."\n";
    }

    /**
     * Formata um valor para SQL sem depender de uma ligação ativa.
     *
     * @return string literal SQL (NULL, número, hex 0x... ou string escapada)
     */
    public static function quoteValue(mixed $value, string $columnType = ''): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? (string) $value : 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_string($value)) {
            return 'NULL';
        }

        $type = strtolower($columnType);
        if (str_contains($type, 'blob') || str_contains($type, 'binary')) {
            return '0x'.strtoupper(bin2hex($value));
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "''"], $value)."'";
    }

    /**
     * @return list<string>
     */
    private function tables(PDO $pdo): array
    {
        $rows = $pdo->query('SHOW TABLES');
        if ($rows === false) {
            throw new RuntimeException('Não foi possível listar as tabelas da base de dados.');
        }

        /** @var list<string> $tables */
        $tables = $rows->fetchAll(PDO::FETCH_COLUMN);

        return $tables;
    }

    private function createTable(PDO $pdo, string $table): string
    {
        $result = $pdo->query(sprintf('SHOW CREATE TABLE `%s`', $table));
        if ($result === false) {
            throw new RuntimeException(sprintf('Não foi possível ler a estrutura da tabela `%s`.', $table));
        }

        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (! is_array($row) || ! isset($row['Create Table']) || ! is_string($row['Create Table'])) {
            throw new RuntimeException(sprintf('Não foi possível ler a estrutura da tabela `%s`.', $table));
        }

        return sprintf("DROP TABLE IF EXISTS `%s`;\n%s;\n", $table, $row['Create Table']);
    }

    /**
     * @return list<string> comandos INSERT em lotes
     */
    private function inserts(PDO $pdo, string $table): array
    {
        $columns = $this->columns($pdo, $table);
        if ($columns === []) {
            return [];
        }

        $result = $pdo->query(sprintf('SELECT * FROM `%s`', $table));
        if ($result === false) {
            throw new RuntimeException(sprintf('Não foi possível ler os dados da tabela `%s`.', $table));
        }

        $columnList = implode(', ', array_map(
            static fn (string $column): string => '`'.$column.'`',
            array_keys($columns),
        ));

        $batch = [];
        $inserts = [];

        while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== false) {
            $values = [];
            foreach ($columns as $column => $type) {
                $values[] = self::quoteValue($row[$column] ?? null, $type);
            }
            $batch[] = '('.implode(', ', $values).')';

            if (count($batch) >= self::INSERT_BATCH_SIZE) {
                $inserts[] = $this->buildInsert($table, $columnList, $batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $inserts[] = $this->buildInsert($table, $columnList, $batch);
        }

        return $inserts;
    }

    /**
     * @param  list<string>  $rows  tuplos já formatados
     */
    private function buildInsert(string $table, string $columnList, array $rows): string
    {
        return 'INSERT INTO `'.$table.'` ('.$columnList.') VALUES'."\n".implode(",\n", $rows).";\n";
    }

    /**
     * @return array<string, string> nome da coluna => tipo
     */
    private function columns(PDO $pdo, string $table): array
    {
        $result = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`', $table));
        if ($result === false) {
            throw new RuntimeException(sprintf('Não foi possível ler as colunas da tabela `%s`.', $table));
        }

        $columns = [];
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $column) {
            if (isset($column['Field'], $column['Type']) && is_string($column['Field']) && is_string($column['Type'])) {
                $columns[$column['Field']] = $column['Type'];
            }
        }

        return $columns;
    }
}
