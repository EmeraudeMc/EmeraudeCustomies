<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class FoilComponent implements ItemComponentBase {

	private bool $foil;

	public function __construct(bool $foil = true) {
		$this->foil = $foil;
	}

	public function getName(): string {
		return "foil";
	}

	public function getValue(): bool {
		return $this->foil;
	}

	public function isProperty(): bool {
		return true;
	}
}