<?php
declare(strict_types=1);

namespace CustomiesE\Items\Components;

final class ProjectileComponent implements ItemComponentBase {

	private string $projectileEntity;

	public function __construct(string $projectileEntity) {
		$this->projectileEntity = $projectileEntity;
	}

	public function getName(): string {
		return "minecraft:projectile";
	}

	public function getValue(): array {
		return [
			"projectile_entity" => $this->projectileEntity
		];
	}

	public function isProperty(): bool {
		return false;
	}
}