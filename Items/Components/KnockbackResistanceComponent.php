<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class KnockbackResistanceComponent implements ItemComponentBase {

	private float $protection;

	public function __construct(float $protection) {
		$this->protection = $protection;
	}

	public function getName(): string {
		return "minecraft:knockback_resistance";
	}

	public function getValue(): array {
		return [
			"protection" => $this->protection
		];
	}

	public function isProperty(): bool {
		return false;
	}
}