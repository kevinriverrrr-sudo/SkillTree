<?php

declare(strict_types=1);

namespace SkillTree\skill;

class SkillTree{

	/** @var SkillBranch[] */
	private array $branches = [];

	public function __construct(array $config){
		$this->parseConfig($config);
	}

	private function parseConfig(array $config) : void{
		$branchesConfig = $config["branches"] ?? [];
		foreach($branchesConfig as $branchId => $branchData){
			$branch = new SkillBranch(
				$branchId,
				$branchData["display_name"] ?? $branchId,
				$branchData["icon"] ?? "?"
			);

			foreach($branchData["nodes"] ?? [] as $nodeId => $nodeData){
				$node = new SkillNode(
					$nodeId,
					$nodeData["description"] ?? "No description",
					$nodeData["cost"] ?? 1,
					$nodeData["requires"] ?? [],
					$nodeData["effects"] ?? [],
					$branchId
				);
				$branch->addNode($node);
			}

			$this->branches[$branchId] = $branch;
		}
	}

	/**
	 * @return SkillBranch[]
	 */
	public function getBranches() : array{
		return $this->branches;
	}

	public function getBranch(string $id) : ?SkillBranch{
		return $this->branches[$id] ?? null;
	}

	public function getNode(string $branchId, string $nodeName) : ?SkillNode{
		$branch = $this->getBranch($branchId);
		if($branch === null){
			return null;
		}
		return $branch->getNode($nodeName);
	}

	/**
	 * Find which branch a node belongs to by searching all branches.
	 */
	public function findNodeBranch(string $nodeName) : ?string{
		foreach($this->branches as $branchId => $branch){
			if($branch->hasNode($nodeName)){
				return $branchId;
			}
		}
		return null;
	}
}
