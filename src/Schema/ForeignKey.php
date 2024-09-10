<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

use Illuminate\Support\Collection;

final class ForeignKey
{
    public readonly string $name;

    public readonly Collection $columns;

    public readonly string $foreign_schema;

    public readonly string $foreign_table;

    public readonly Collection $foreign_columns;

    public readonly string $on_update;

    public readonly string $on_delete;
    
    public function __construct(array $foreign)
    {
        $this->name = $foreign['name'];
        $this->columns = collect($foreign['columns']);
        $this->foreign_schema = $foreign['foreign_schema'];
        $this->foreign_table = $foreign['foreign_table'];
        $this->foreign_columns = collect($foreign['foreign_columns']);
        $this->on_update = $foreign['on_update'];
        $this->on_delete = $foreign['on_delete'];
    }

    public function toSql(): string
    {
        $sql = "CONSTRAINT {$this->name} FOREIGN KEY (";
        $sql .= implode(',', $this->columns->toArray());
        $sql .= ") REFERENCES {$this->foreign_table} (`";
        $sql .= implode('`, `', $this->foreign_columns->toArray());
        $sql .= '`)' . PHP_EOL;

        return $sql;
    }
}
