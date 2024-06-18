<?php

declare(strict_types=1);

namespace Daniel\Vote\Dto;

class Record
{
    /**
     * @var array<mixed>
     */
    protected array $fields;

    /**
     * @param array<mixed> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param array<mixed> $fields
     */
    public function reset(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * Sets new value to $field and returns old value.
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function set(string $field, mixed $value): mixed
    {
        if (!array_key_exists($field, $this->fields)) {
            throw new \Exception("Field does no exist: $field");
        }
        $old = $this->fields[$field];
        $this->fields[$field] = $value;
        return $old;
    }

    /**
     * Returns $field value.
     *
     * @param string $field
     * @return mixed
     */
    public function get(string $field): mixed
    {
        if (!array_key_exists($field, $this->fields)) {
            throw new \Exception("Field does no exist: $field");
        }
        return $this->fields[$field];
    }
}
