<?php

namespace CustomiesE\Items\Components;

interface ItemComponentBase {

    public function getName(): string;

    public function getValue(): mixed;

    public function isProperty(): bool;
}