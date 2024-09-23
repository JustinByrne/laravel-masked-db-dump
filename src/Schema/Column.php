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

    /** @var string|null */
    protected $collation;

    /** @var string|null */
    protected $charset;

    public function __construct(array $column)
    {
        $this->name = $column['name'];
        $this->type = $column['type'];
        $this->nullable = $column['nullable'];
        $this->default = $this->defaultValue($column['default']);
        $this->auto_increment = $column['auto_increment'];
        $this->comment = $column['comment'];
        $this->collation = $column['collation'];
        $this->charset = !is_null($column['collation']) ? explode('_', $column['collation'])[0] : null;
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

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";
        $sql .= !is_null($this->charset) ? ' CHARSET ' . $this->charset : '';
        $sql .= !is_null($this->collation) ? ' COLLATE ' . $this->collation : '';
        $sql .= !$this->nullable ? ' NOT NULL' : ' NULL';
        $sql .= !is_null($this->default) ? ' DEFAULT ' . $this->default : '';
        $sql .= $this->nullable && is_null($this->default) ? ' DEFAULT NULL' : '';
        $sql .= !is_null($this->comment) ? " COMMENT '{$this->comment}'" : '';
        $sql .= $this->auto_increment ? ' AUTO_INCREMENT' : '';

        return $sql;
    }

    private function defaultValue(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $commonMySQLFuncs = [
            'CURRENT_TIMESTAMP',
            'NOW',
            'UUID',
            'CURRDATE',
            'RAND',
            'YEAR',
            'UNIX_TIMESTAMP'
        ];

        if (in_array($value, $commonMySQLFuncs) || str_ends_with($value, '()')) {
            return $value;
        } else {
            return "'$value'";
        }
    }
}
