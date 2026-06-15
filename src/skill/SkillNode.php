<?php

declare(strict_types=1);

namespace SkillTree\skill;

class SkillNode{

	private string $name;
	private string $description;
	private int $cost;
	private array $requires;
	private array $effects;
	private string $branch;

	public function __construct(string $name, string $description, int $cost, array $requires, array $effects, string $branch){
		$this->name = $name;
		$this->description = $description;
		$this->cost = $cost;
		$this->requires = $requires;
		$this->effects = $effects;
		$this->branch = $branch;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function getCost() : int{
		return $this->cost;
	}

	public function getRequires() : array{
		return $this->requires;
	}

	public function getEffects() : array{
		return $this->effects;
	}

	public function getBranch() : string{
		return $this->branch;
	}

	public function hasRequirements() : bool{
		return count($this->requires) > 0;
	}
}
