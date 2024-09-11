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

    public function __construct(array $table)
    {
        $this->name = $table['name'];
        $this->comment = $table['comment'];
        $this->columns = $this->getColumns();
        $this->indexes = $this->getIndexes();
        $this->foreignKeys = $this->getForeignKeys();
        $this->collation = $table['collation'];
        $this->engine = $table['engine'];
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

    public function toSql(): string
    {
        $sql = "CREATE TABLE `{$this->name}` (" . PHP_EOL;
        
        // columns
        $this->columns->each(function (Column $column, int $key) use (&$sql) {
            $sql .= $column->toSql();
            
            if ($key !== $this->columns->count() - 1) {
                $sql .= ',' . PHP_EOL;
            }
        });

        // primary keys
        $this->indexes->each(function (Index $index) use (&$sql) {
            $sql .= ', ' . PHP_EOL . $index->toSql();
        });

        // foreign keys
        $this->foreignKeys->each(function (ForeignKey $foreignKey) use (&$sql) {
            $sql .= ', ' . PHP_EOL . $foreignKey->toSql();
        });

        $sql .= PHP_EOL . ');' . PHP_EOL;

        return $sql;
    }
}
