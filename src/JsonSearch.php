<?php
declare(strict_types=1);

namespace CarApiSdk;

class JsonSearch implements \JsonSerializable
{
    private array $items = [];

    public function addItem(JsonSearchItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $return = [];
        foreach ($this->items as $item) {
            $return[] = $item;
        }

        return $return;
    }
}