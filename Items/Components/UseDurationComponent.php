<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class UseDurationComponent implements ItemComponentBase {

	private int $duration;

	public function __construct(int $duration) {
		$this->duration = $duration;
	}

	public function getName(): string {
		return "use_duration";
	}

	public function getValue(): int {
		return $this->duration;
	}

	public function isProperty(): bool {
		return true;
	}
}