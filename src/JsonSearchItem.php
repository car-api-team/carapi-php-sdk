<?php
declare(strict_types=1);

namespace CarApiSdk;

class JsonSearchItem implements \JsonSerializable
{
    private string $field;
    private string $operator;
    private $value;

    /**
     * Construct
     *
     * @param string                $field    The name of the field
     * @param string                $operator The operator type
     * @param int|string|array|null $value    Default value is null. A null value is only acceptable when using
     *                                        the "not null" or "is null" operators.
     */
    public function __construct(string $field, string $operator, $value = null)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Serializes the instance into JSON.
     *
     * @see        \JsonSerializable
     * @inheritdoc
     * @return     array
     */
    public function jsonSerialize(): array
    {
        if ($this->value) {
            return [
                'field' => $this->field,
                'op' => $this->operator,
                'val' => $this->value,
            ];
        }

        return [
            'field' => $this->field,
            'op' => $this->operator,
        ];
    }
}