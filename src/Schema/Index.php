<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

use Illuminate\Support\Collection;

final class Index
{
    /** @var string */
    protected $name;

    /** @var string|null */
    protected $type;

    /** @var Collection */
    protected $columns;

    /** @var bool */
    protected $unique;

    /** @var bool */
    protected $primary;

    public function __construct(array $index)
    {
        $this->name = $index['name'];
        $this->type = $index['type'];
        $this->columns = collect($index['columns']);
        $this->unique = $index['unique'];
        $this->primary = $index['primary'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function getPrimary(): bool
    {
        return $this->primary;
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
