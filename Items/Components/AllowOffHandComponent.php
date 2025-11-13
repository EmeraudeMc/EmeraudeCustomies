<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class AllowOffHandComponent implements ItemComponentBase {

	private bool $offHand;

	public function __construct(bool $offHand = true) {
		$this->offHand = $offHand;
	}

	public function getName(): string {
		return "allow_off_hand";
	}

	public function getValue(): bool {
		return $this->offHand;
	}

	public function isProperty(): bool {
		return true;
	}
}