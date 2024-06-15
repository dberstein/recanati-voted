<?php declare(strict_types=1);

namespace Daniel\Vote\Dto;


class Record
{
    /** @var array<mixed> */
    protected array $fields;
    /**
     * @param ?array<mixed> $fields
     */
    public function __construct(?array $fields = null) {
        $this->fields = (array) $fields;
    }

    /**
     * @param array<mixed> $fields
     */
    public function reset(array $fields): void {
        $this->fields = $fields;
    }

    public function set(string $field, mixed $value): mixed {
        if (!array_key_exists($field, $this->fields)) {
            throw new \Exception("Field does no exist: $field");
        }
        $old = $this->fields[$field];
        $this->fields[$field] = $value;
        return $old;
    }

    public function get(string $field): mixed {
        if (!array_key_exists($field, $this->fields)) {
            throw new \Exception("Field does no exist: $field");
        }
        return $this->fields[$field];
    }
}

// $q = new Question(
//     ['id'=>'AAAA', 'text' => 'why?', 'created_by' => 'daniel@basegeo.com']
// );
// var_dump($q->get('created_by'));