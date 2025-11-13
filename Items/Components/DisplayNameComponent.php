<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class DisplayNameComponent implements ItemComponentBase {

	private string $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName(): string {
		return "minecraft:display_name";
	}

	public function getValue(): array {
		return [
			"value" => $this->name
		];
	}

	public function isProperty(): bool {
		return false;
	}
}