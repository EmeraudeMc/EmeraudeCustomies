<?php

namespace CustomiesE\Items\Components;

final class DamageComponent implements ItemComponentBase
{
    private int $damage;
    public function __construct(int $damage) {
        $this->damage = $damage;
    }

    public function getName(): string {
        return "damage";
    }

    public function getValue(): int {
        return $this->damage;
    }

    public function isProperty(): bool {
        return true;
    }
}