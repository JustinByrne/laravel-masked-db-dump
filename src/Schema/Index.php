<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

use Illuminate\Support\Collection;

final class Index
{
    public readonly string $name;

    public readonly ?string $type;

    public readonly Collection $columns;

    public readonly bool $unique;

    public readonly bool $primary;

    public function __construct(array $index)
    {
        $this->name = $index['name'];
        $this->type = $index['type'];
        $this->columns = collect($index['columns']);
        $this->unique = $index['unique'];
        $this->primary = $index['primary'];
    }

    public function toSql(): string
    {
        $sql = $this->name === 'primary'
            ? 'PRIMARY KEY ('
            : "KEY `{$this->name}` (";

        $sql .= '`' . implode('`,`', $this->columns->toArray()) . '`)';

        return $sql;
    }
}
