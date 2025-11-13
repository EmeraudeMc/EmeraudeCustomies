<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class FuelComponent implements ItemComponentBase {

	private float $duration;

	public function __construct(float $duration) {
		$this->duration = $duration;
	}

	public function getName(): string {
		return "minecraft:fuel";
	}

	public function getValue(): array {
		return [
			"duration" => $this->duration
		];
	}

	public function isProperty(): bool {
		return false;
	}
}