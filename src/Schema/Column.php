<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

final class Column
{
    public readonly string $name;

    public readonly string $type;

    public readonly bool $nullable;

    public readonly ?string $default;

    public readonly bool $auto_increment;

    public readonly ?string $comment;

    public function __construct(array $column)
    {
        $this->name = $column['name'];
        $this->type = $column['type'];
        $this->nullable = $column['nullable'];
        $this->default = $column['default'];
        $this->auto_increment = $column['auto_increment'];
        $this->comment = $column['comment'];
    }

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";
        $sql .= !$this->nullable ? ' NOT NULL' : ' NULL';
        $sql .= !is_null($this->default) ? " DEFAULT '{$this->default}'" : '';
        $sql .= $this->nullable && is_null($this->default) ? ' DEFAULT NULL' : '';
        $sql .= !is_null($this->comment) ? " COMMENT '{$this->comment}'" : '';
        $sql .= $this->auto_increment ? ' AUTO_INCREMENT' : '';

        return $sql;
    }
}
