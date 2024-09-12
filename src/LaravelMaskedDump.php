<?php

namespace BeyondCode\LaravelMaskedDumper;

use BeyondCode\LaravelMaskedDumper\TableDefinitions\TableDefinition;
use Illuminate\Console\OutputStyle;

class LaravelMaskedDump
{
    /** @var DumpSchema */
    protected $definition;

    /** @var OutputStyle */
    protected $output;

    /** @var string */
    protected $outputFile;

    /** @var bool */
    protected $gzip;

    public function __construct(DumpSchema $definition, OutputStyle $output, string $outputFile, bool $gzip = false)
    {
        $this->definition = $definition;
        $this->output = $output;
        $this->outputFile = $outputFile;
        $this->gzip = $gzip;
    }

    public function dump()
    {
        $tables = $this->definition->getDumpTables();

        $overallTableProgress = $this->output->createProgressBar(count($tables));

        if ($this->gzip) {
            $gz = gzopen($this->outputFile . '.gz', 'w9');
        } else {
            file_put_contents($this->outputFile, '');
        }

        foreach ($tables as $tableName => $table) {
            $query = "DROP TABLE IF EXISTS `$tableName`;" . PHP_EOL;
            $query .= $this->dumpSchema($table);

            if ($table->shouldDumpData()) {
                $query .= $this->lockTable($tableName);

                $query .= $this->dumpTableData($table);

                $query .= $this->unlockTable($tableName);
            }

            if ($this->gzip) {
                gzwrite($gz, $query);
            } else {
                file_put_contents($this->outputFile, $query, FILE_APPEND);
            }

            $overallTableProgress->advance();
        }

        $this->output->newLine();

        if ($this->gzip) {
            gzclose($gz);
            $this->output->info('Wrote database dump to ' . $this->outputFile . '.gz');
        } else {
            $this->output->info('Wrote database dump to ' . $this->outputFile);
        }
    }

    protected function transformResultForInsert($row, TableDefinition $table)
    {
        return collect($row)->map(function ($value, $column) use ($table) {
            if ($columnDefinition = $table->findColumn($column)) {
                $value = $columnDefinition->modifyValue($value);
            }

            if ($value === null) {
                return 'NULL';
            }
            if ($value === '') {
                return '""';
            }

            return $this->escapeQuote($value);
        })->toArray();
    }

    protected function dumpSchema(TableDefinition $table)
    {
        return $table->getDoctrineTable()->toSql();
    }

    protected function lockTable(string $tableName)
    {
        return "LOCK TABLES `$tableName` WRITE;" . PHP_EOL .
            "ALTER TABLE `$tableName` DISABLE KEYS;" . PHP_EOL;
    }

    protected function unlockTable(string $tableName)
    {
        return "ALTER TABLE `$tableName` ENABLE KEYS;" . PHP_EOL .
            "UNLOCK TABLES;" . PHP_EOL;
    }

    protected function dumpTableData(TableDefinition $table)
    {
        $query = '';

        $queryBuilder = $this->definition->getConnection()
            ->table($table->getDoctrineTable()->getName());

        $table->modifyQuery($queryBuilder);

        $queryBuilder->get()
            ->each(function ($row, $index) use ($table, &$query) {
                $row = $this->transformResultForInsert((array)$row, $table);
                $tableName = $table->getDoctrineTable()->getName();

                $query .= "INSERT INTO `${tableName}` (`" . implode('`, `', array_keys($row)) . '`) VALUES ';
                $query .= "(";

                $firstColumn = true;
                foreach ($row as $value) {
                    if (!$firstColumn) {
                        $query .= ", ";
                    }
                    $query .= $value;
                    $firstColumn = false;
                }

                $query .= ");" . PHP_EOL;
            });

        return $query;
    }

    protected function escapeQuote(string $str): string
    {
        $c = "'";

        return $c . str_replace($c, $c . $c, $str) . $c;
    }
}
