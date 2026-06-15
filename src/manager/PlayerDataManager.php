<?php

declare(strict_types=1);

namespace SkillTree\manager;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SkillTree\Main;

class PlayerDataManager{

	private Main $plugin;
	/** @var array<string, array{points: int, unlocked: array<string, string[]>}> */
	private array $cache = [];

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function loadPlayer(Player $player) : void{
		$name = strtolower($player->getName());
		$config = new Config($this->plugin->getDataFolder() . "players/" . $name . ".json", Config::JSON);
		$this->cache[$name] = [
			"points" => $config->get("points", 0),
			"unlocked" => $config->get("unlocked", [])
		];
	}

	public function unloadPlayer(Player $player) : void{
		$name = strtolower($player->getName());
		if(isset($this->cache[$name])){
			$this->savePlayer($name);
			unset($this->cache[$name]);
		}
	}

	public function savePlayer(string $name) : void{
		if(!isset($this->cache[$name])){
			return;
		}
		$config = new Config($this->plugin->getDataFolder() . "players/" . $name . ".json", Config::JSON);
		$config->set("points", $this->cache[$name]["points"]);
		$config->set("unlocked", $this->cache[$name]["unlocked"]);
		$config->save();
	}

	public function saveAll() : void{
		foreach(array_keys($this->cache) as $name){
			$this->savePlayer($name);
		}
	}

	public function isLoaded(Player $player) : bool{
		return isset($this->cache[strtolower($player->getName())]);
	}

	public function getPoints(Player $player) : int{
		$name = strtolower($player->getName());
		return $this->cache[$name]["points"] ?? 0;
	}

	public function setPoints(Player $player, int $points) : void{
		$name = strtolower($player->getName());
		if(!isset($this->cache[$name])){
			return;
		}
		$this->cache[$name]["points"] = max(0, $points);
	}

	public function addPoints(Player $player, int $points) : void{
		$this->setPoints($player, $this->getPoints($player) + $points);
	}

	public function takePoints(Player $player, int $points) : void{
		$this->setPoints($player, $this->getPoints($player) - $points);
	}

	/**
	 * @return array<string, string[]>
	 */
	public function getUnlockedNodes(Player $player) : array{
		$name = strtolower($player->getName());
		return $this->cache[$name]["unlocked"] ?? [];
	}

	public function isNodeUnlocked(Player $player, string $branchId, string $nodeName) : bool{
		$name = strtolower($player->getName());
		return in_array($nodeName, $this->cache[$name]["unlocked"][$branchId] ?? [], true);
	}

	public function unlockNode(Player $player, string $branchId, string $nodeName) : void{
		$name = strtolower($player->getName());
		if(!isset($this->cache[$name])){
			return;
		}
		if(!isset($this->cache[$name]["unlocked"][$branchId])){
			$this->cache[$name]["unlocked"][$branchId] = [];
		}
		if(!in_array($nodeName, $this->cache[$name]["unlocked"][$branchId], true)){
			$this->cache[$name]["unlocked"][$branchId][] = $nodeName;
		}
	}

	public function resetPlayer(Player $player) : void{
		$name = strtolower($player->getName());
		if(!isset($this->cache[$name])){
			return;
		}
		$this->cache[$name]["points"] = 0;
		$this->cache[$name]["unlocked"] = [];
	}

	/**
	 * Get the combined effects from all unlocked nodes for a player.
	 * @return array<string, mixed>
	 */
	public function getCombinedEffects(Player $player) : array{
		$unlocked = $this->getUnlockedNodes($player);
		$combined = [];
		$skillTree = $this->plugin->getSkillTreeData();

		foreach($unlocked as $branchId => $nodeNames){
			foreach($nodeNames as $nodeName){
				$node = $skillTree->getNode($branchId, $nodeName);
				if($node !== null){
					foreach($node->getEffects() as $effectKey => $effectValue){
						if(!isset($combined[$effectKey])){
							$combined[$effectKey] = $effectValue;
						}else{
							// For multiplier effects, multiply them together
							if(is_float($effectValue) && is_float($combined[$effectKey])){
								$combined[$effectKey] *= $effectValue;
							}elseif(is_int($effectValue) && is_int($combined[$effectKey])){
								$combined[$effectKey] += $effectValue;
							}else{
								$combined[$effectKey] = $effectValue;
							}
						}
					}
				}
			}
		}

		return $combined;
	}
}
