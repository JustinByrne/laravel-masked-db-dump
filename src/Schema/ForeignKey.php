<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

use Illuminate\Support\Collection;

final class ForeignKey
{
    /** @var string */
    protected $name;

    /** @var Collection */
    protected $columns;

    /** @var string */
    protected $foreign_schema;

    /** @var string */
    protected $foreign_table;

    /** @var Collection */
    protected $foreign_columns;

    /** @var string */
    protected $on_update;

    /** @var string */
    protected $on_delete;
    
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function getForeignSchema(): string
    {
        return $this->foreign_schema;
    }

    public function getForeignTable(): string
    {
        return $this->foreign_table;
    }

    public function getForeignColumns(): Collection
    {
        return $this->foreign_columns;
    }

    public function getOnUpdate(): string
    {
        return $this->on_update;
    }

    public function getOnDelete(): string
    {
        return $this->on_delete;
    }

    public function toSql(): string
    {
        $sql = "CONSTRAINT `{$this->name}` FOREIGN KEY (`";
        $sql .= implode('`, `', $this->columns->toArray());
        $sql .= "`) REFERENCES `{$this->foreign_table}` (`";
        $sql .= implode('`, `', $this->foreign_columns->toArray());
        $sql .= '`)';

        return $sql;
    }
}
