<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Table
{
    public readonly string $name;

    public readonly ?string $comment;

    public readonly Collection $columns;

    public readonly Collection $indexes;

    public readonly Collection $foreignKeys;

    public readonly ?string $collation;

    public readonly ?string $engine;

    private readonly string $dbName;

    public function __construct(array $table)
    {
        $this->name = $table['name'];
        $this->comment = $table['comment'];
        $this->columns = $this->getColumns();
        $this->indexes = $this->getIndexes();
        $this->foreignKeys = $this->getForeignKeys();
        $this->collation = $table['collation'];
        $this->engine = $table['engine'];

        $this->dbName = DB::connection()->getDatabaseName();
    }

    private function getColumns(): Collection
    {
        $columns = collect();
        
        collect(Schema::getColumns($this->name))->each(function (array $column) use ($columns) {
            $columns->push(new Column($column));
        });
        
        return $columns;
    }

    private function getIndexes(): Collection
    {
        $indexes = collect();

        collect(Schema::getIndexes($this->name))
            ->each(function (array $index) use ($indexes) {
                $indexes->push(new Index($index));
            });
        
        return $indexes;
    }

    private function getForeignKeys(): Collection
    {
        $indexes = collect();

        collect(Schema::getForeignKeys($this->name))
            ->each(function (array $index) use ($indexes) {
                $indexes->push(new ForeignKey($index));
            });
        
        return $indexes;
    }

    private function generateDropQuery(): string
    {
        return "DROP TABLE `{$this->dbName}`.`{$this->name}`;";
    }

    public function toSql(): string
    {
        $sql = $this->generateDropQuery() . PHP_EOL;
        $sql .= "CREATE TABLE `{$this->name}` (" . PHP_EOL;
        
        // columns
        $this->columns->each(function (Column $column, int $key) use (&$sql) {
            $sql .= $column->toSql();
            
            if ($key !== $this->columns->count() - 1) {
                $sql .= ',' . PHP_EOL;
            }
        });

        // primary keys
        $this->indexes->each(function (Index $index) use (&$sql) {
            $sql .= ', ' . $index->toSql() . PHP_EOL;
        });

        // foreign keys
        $this->foreignKeys->each(function (ForeignKey $foreignKey) use (&$sql) {
            $sql .= ', ' . $foreignKey->toSql() . PHP_EOL;
        });

        $sql .= ');';

        return $sql;
    }
}
