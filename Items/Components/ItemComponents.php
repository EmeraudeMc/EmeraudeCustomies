<?php
declare(strict_types=1);


namespace CustomiesE\Items\Components;

use pocketmine\nbt\tag\CompoundTag;

interface ItemComponents {

    public function addComponent(ItemComponentBase $component): void;

    public function hasComponent(string $name): bool;

    public function getComponents(): CompoundTag;
}
