<?php
declare(strict_types=1);

namespace CarApiSdk;

/**
 * Holds JsonSearchItem instances
 *
 * @see JsonSearchItem
 */
class JsonSearch implements \JsonSerializable
{
    private array $items = [];

    /**
     * Add a JSON search parameter
     *
     * @param JsonSearchItem $item An instance of JsonSearchItem
     * 
     * @return $this
     */
    public function addItem(JsonSearchItem $item): self
    {
        $this->items[] = $item;

        return $this;
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
        $return = [];
        foreach ($this->items as $item) {
            $return[] = $item;
        }

        return $return;
    }
}