<?php

declare(strict_types=1);

namespace SkillTree\skill;

class SkillBranch{

	private string $id;
	private string $displayName;
	private string $icon;
	/** @var SkillNode[] */
	private array $nodes = [];

	public function __construct(string $id, string $displayName, string $icon){
		$this->id = $id;
		$this->displayName = $displayName;
		$this->icon = $icon;
	}

	public function getId() : string{
		return $this->id;
	}

	public function getDisplayName() : string{
		return $this->displayName;
	}

	public function getIcon() : string{
		return $this->icon;
	}

	public function addNode(SkillNode $node) : void{
		$this->nodes[$node->getName()] = $node;
	}

	/**
	 * @return SkillNode[]
	 */
	public function getNodes() : array{
		return $this->nodes;
	}

	public function getNode(string $name) : ?SkillNode{
		return $this->nodes[$name] ?? null;
	}

	public function hasNode(string $name) : bool{
		return isset($this->nodes[$name]);
	}
}
