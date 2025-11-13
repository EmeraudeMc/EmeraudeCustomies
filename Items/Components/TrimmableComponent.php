<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class TrimmableComponent implements ItemComponentBase {

    public function getName(): string {
        return "minecraft:tags";
    }

    public function getValue(): array {
        return [
            "tags" =>
                ["minecraft:trimmable_armors"]
        ];
    }

    public function isProperty(): bool {
        return false;
    }
}