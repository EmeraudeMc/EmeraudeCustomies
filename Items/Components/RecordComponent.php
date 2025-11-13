<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class RecordComponent implements ItemComponentBase {

	private int $cs;

    private int $duration;

    private string $sound;

	public function __construct(int $cs, int $duration, string $sound) {
		$this->cs = $cs;
        $this->duration = $duration;
        $this->sound = $sound;
	}

	public function getName(): string {
		return "minecraft:record";
	}

	public function getValue(): array {
		return [
            "comparator_signal" => $this->cs,
            "duration" => $this->duration,
            "sound_event" => $this->sound
        ];
	}

	public function isProperty(): bool {
		return false;
	}
}