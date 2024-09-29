<?php

namespace BeyondCode\LaravelMaskedDumper;

use BeyondCode\LaravelMaskedDumper\TableDefinitions\TableDefinition;
use Carbon\Carbon;
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
            $gz = null;
            file_put_contents($this->outputFile, '');
        }

        $this->writeToFile($this->createHeader(), $gz);
        
        $this->writeToFile('SET FOREIGN_KEY_CHECKS=0;' . PHP_EOL . PHP_EOL, $gz);

        foreach ($tables as $tableName => $table) {
            $this->writeToFile("DROP TABLE IF EXISTS `$tableName`;" . PHP_EOL . PHP_EOL, $gz);
            $this->writeToFile($this->dumpSchema($table), $gz);

            if ($table->shouldDumpData()) {
                $this->writeToFile($this->lockTable($tableName), $gz);

                $this->dumpTableData($table, $gz);

                $this->writeToFile($this->unlockTable($tableName), $gz);
            }

            $overallTableProgress->advance();
        }

        $this->writeToFile('SET FOREIGN_KEY_CHECKS=1;', $gz);

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

            if (str_contains($value, "\\")) {
                $value = str_replace('\\', '\\\\', $value);
            }

            if ($value === null) {
                return 'NULL';
            }
            if ($value === '') {
                return '""';
            }
            if (str_starts_with($value, 'ST_GeomFromText')) {
                return $value;
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
        return "LOCK TABLES `$tableName` WRITE;" . PHP_EOL;
    }

    protected function unlockTable(string $tableName)
    {
        return "UNLOCK TABLES;" . PHP_EOL . PHP_EOL;
    }

    protected function dumpTableData(TableDefinition $table, $file)
    {
        $queryBuilder = $this->definition->getConnection()
            ->table($table->getDoctrineTable()->getName());

        $table->modifyQuery($queryBuilder);

        $queryBuilder->get()
            ->each(function ($row, $index) use ($table, $file) {
                $row = $this->transformResultForInsert((array)$row, $table);
                $tableName = $table->getDoctrineTable()->getName();

                $this->writeToFile("INSERT INTO `$tableName` (`" . implode('`, `', array_keys($row)) . '`) VALUES ', $file);
                $this->writeToFile("(", $file);

                $firstColumn = true;
                foreach ($row as $value) {
                    if (!$firstColumn) {
                        $this->writeToFile(", ", $file);
                    }
                    $this->writeToFile($value, $file);
                    $firstColumn = false;
                }

                $this->writeToFile(");" . PHP_EOL, $file);
            });

        return;
    }

    protected function escapeQuote(string $str): string
    {
        $c = "'";

        return $c . str_replace($c, $c . $c, $str) . $c;
    }

    protected function writeToFile(string $dump, $file): void
    {
        if ($this->gzip) {
            gzwrite($file, $dump);
        } else {
            file_put_contents($this->outputFile, $dump, FILE_APPEND);
        }
    }

    protected function createHeader(): string
    {
        return '-- -------------------------------------------------------------' . PHP_EOL
            . '-- -------------------------------------------------------------' . PHP_EOL
            . '--' . PHP_EOL
            . '-- Laravel Masked DB Dump' . PHP_EOL
            . '--' . PHP_EOL
            . '-- Generated Time: ' . Carbon::now()->format('Y-m-d H:i:s:u') . PHP_EOL
            . '-- -------------------------------------------------------------' . PHP_EOL . PHP_EOL;
    }
}
