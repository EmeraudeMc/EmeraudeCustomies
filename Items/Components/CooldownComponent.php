<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class CooldownComponent implements ItemComponentBase {

	private string $category;
	private float $duration;

	public function __construct(string $category, float $duration) {
		$this->category = $category;
		$this->duration = $duration;
	}

	public function getName(): string {
		return "minecraft:cooldown";
	}

	public function getValue(): array {
		return [
			"category" => $this->category,
			"duration" => $this->duration
		];
	}

	public function isProperty(): bool {
		return false;
	}
}