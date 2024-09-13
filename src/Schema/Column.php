<?php

namespace BeyondCode\LaravelMaskedDumper\Schema;

final class Column
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var bool */
    protected $nullable;

    /** @var string|null */
    protected $default;

    /** @var bool */
    protected $auto_increment;

    /** @var string|null */
    protected $comment;

    public function __construct(array $column)
    {
        $this->name = $column['name'];
        $this->type = $column['type'];
        $this->nullable = $column['nullable'];
        $this->default = $column['default'];
        $this->auto_increment = $column['auto_increment'];
        $this->comment = $column['comment'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function getAutoIncrement(): bool
    {
        return $this->auto_increment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
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
