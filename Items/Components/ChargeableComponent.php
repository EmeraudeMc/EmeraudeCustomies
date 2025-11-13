<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class ChargeableComponent implements ItemComponentBase {

	private float $movementModifier;

	public function __construct(float $movementModifier) {
		$this->movementModifier = $movementModifier;
	}

	public function getName(): string {
		return "minecraft:chargeable";
	}

	public function getValue(): array {
		return [
			"movement_modifier" => $this->movementModifier
		];
	}

	public function isProperty(): bool {
		return false;
	}
}