<?php

declare(strict_types=1);

namespace SkillTree\ui;

use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SkillTree\Main;
use SkillTree\skill\SkillBranch;
use SkillTree\skill\SkillNode;

class TreeForm{

	/**
	 * Send the main skill tree form showing all branches.
	 */
	public static function sendMainForm(Player $player, Main $plugin) : void{
		$branches = $plugin->getSkillTreeData()->getBranches();
		$points = $plugin->getPlayerDataManager()->getPoints($player);
		$playerName = $player->getName();

		$form = new class($plugin, $branches, $points, $playerName) implements Form{
			private Main $plugin;
			private array $branches;
			private int $points;
			private string $playerName;

			public function __construct(Main $plugin, array $branches, int $points, string $playerName){
				$this->plugin = $plugin;
				$this->branches = $branches;
				$this->points = $points;
				$this->playerName = $playerName;
			}

			public function jsonSerialize() : mixed{
				$buttons = [];
				$p = $this->plugin->getServer()->getPlayerByPrefix($this->playerName);

				foreach($this->branches as $branchId => $branch){
					$totalCount = count($branch->getNodes());
					$unlockedCount = 0;
					if($p !== null){
						$playerData = $this->plugin->getPlayerDataManager()->getUnlockedNodes($p);
						$unlockedList = $playerData[$branchId] ?? [];
						$unlockedCount = count($unlockedList);
					}

					$buttons[] = [
						"text" => $branch->getIcon() . " " . $branch->getDisplayName() . "\n" .
							TextFormat::GRAY . $unlockedCount . "/" . $totalCount . " nodes unlocked"
					];
				}

				return [
					"type" => "form",
					"title" => TextFormat::BOLD . TextFormat::GOLD . "Skill Tree" . TextFormat::RESET .
						TextFormat::YELLOW . " (" . $this->points . " points)",
					"content" => $this->plugin->getMessage("ui.main.description") . "\n\n" .
						TextFormat::YELLOW . "Skill Points: " . TextFormat::WHITE . $this->points,
					"buttons" => $buttons
				];
			}

			public function handleResponse(Player $player, mixed $data) : void{
				if($data === null){
					return;
				}
				$branchIds = array_keys($this->branches);
				if(isset($branchIds[$data])){
					$branchId = $branchIds[$data];
					$branch = $this->branches[$branchId];

					// Check permission
					if(!$player->hasPermission("skilltree.branch." . $branchId)){
						$player->sendMessage(TextFormat::RED . $this->plugin->getMessage("branch.no_permission", ["branch" => $branch->getDisplayName()]));
						return;
					}

					TreeForm::sendBranchForm($player, $this->plugin, $branch);
				}
			}
		};

		$player->sendForm($form);
	}

	/**
	 * Send a form showing all nodes in a specific branch.
	 */
	public static function sendBranchForm(Player $player, Main $plugin, SkillBranch $branch) : void{
		$points = $plugin->getPlayerDataManager()->getPoints($player);
		$playerName = $player->getName();

		$form = new class($plugin, $branch, $points, $playerName) implements Form{
			private Main $plugin;
			private SkillBranch $branch;
			private int $points;
			private string $playerName;

			public function __construct(Main $plugin, SkillBranch $branch, int $points, string $playerName){
				$this->plugin = $plugin;
				$this->branch = $branch;
				$this->points = $points;
				$this->playerName = $playerName;
			}

			public function jsonSerialize() : mixed{
				$buttons = [];
				$p = $this->plugin->getServer()->getPlayerByPrefix($this->playerName);

				if($p !== null){
					foreach($this->branch->getNodes() as $node){
						$isUnlocked = $this->plugin->getPlayerDataManager()->isNodeUnlocked($p, $this->branch->getId(), $node->getName());
						$canUnlock = $this->canUnlockNode($p, $node);

						if($isUnlocked){
							$status = TextFormat::GREEN . "[UNLOCKED]";
						}elseif($canUnlock){
							$status = TextFormat::YELLOW . "[" . $node->getCost() . " pts]";
						}else{
							$status = TextFormat::RED . "[LOCKED]";
						}

						$buttons[] = [
							"text" => $node->getName() . " " . $status . "\n" .
								TextFormat::GRAY . $node->getDescription()
						];
					}
				}

				// Add back button
				$buttons[] = [
					"text" => TextFormat::BOLD . TextFormat::RED . "<< Back"
				];

				return [
					"type" => "form",
					"title" => TextFormat::BOLD . $this->branch->getIcon() . " " . $this->branch->getDisplayName() .
						TextFormat::RESET . TextFormat::YELLOW . " (" . $this->points . " pts)",
					"content" => $this->plugin->getMessage("ui.branch.description", ["branch" => $this->branch->getDisplayName()]),
					"buttons" => $buttons
				];
			}

			private function canUnlockNode(Player $player, SkillNode $node) : bool{
				if($this->plugin->getPlayerDataManager()->isNodeUnlocked($player, $this->branch->getId(), $node->getName())){
					return false;
				}
				if($this->plugin->getPlayerDataManager()->getPoints($player) < $node->getCost()){
					return false;
				}
				foreach($node->getRequires() as $requiredNode){
					if(!$this->plugin->getPlayerDataManager()->isNodeUnlocked($player, $this->branch->getId(), $requiredNode)){
						return false;
					}
				}
				return true;
			}

			public function handleResponse(Player $player, mixed $data) : void{
				if($data === null){
					return;
				}

				$nodes = array_values($this->branch->getNodes());
				$nodeCount = count($nodes);

				// Back button
				if($data === $nodeCount){
					TreeForm::sendMainForm($player, $this->plugin);
					return;
				}

				if(!isset($nodes[$data])){
					return;
				}

				$node = $nodes[$data];
				TreeForm::sendNodeDetailForm($player, $this->plugin, $this->branch, $node);
			}
		};

		$player->sendForm($form);
	}

	/**
	 * Send a form showing node details with option to unlock.
	 */
	public static function sendNodeDetailForm(Player $player, Main $plugin, SkillBranch $branch, SkillNode $node) : void{
		$manager = $plugin->getPlayerDataManager();
		$isUnlocked = $manager->isNodeUnlocked($player, $branch->getId(), $node->getName());
		$canUnlock = !$isUnlocked && $manager->getPoints($player) >= $node->getCost();
		$requirementsMet = true;

		foreach($node->getRequires() as $requiredNode){
			if(!$manager->isNodeUnlocked($player, $branch->getId(), $requiredNode)){
				$requirementsMet = false;
				break;
			}
		}

		$content = TextFormat::GOLD . $node->getName() . "\n\n";
		$content .= TextFormat::WHITE . $node->getDescription() . "\n\n";

		if($isUnlocked){
			$content .= TextFormat::GREEN . "Status: UNLOCKED\n\n";
		}else{
			$content .= TextFormat::YELLOW . "Cost: " . $node->getCost() . " skill points\n";
			$content .= TextFormat::WHITE . "Your points: " . $manager->getPoints($player) . "\n\n";

			if(!empty($node->getRequires())){
				$content .= TextFormat::AQUA . "Requires:\n";
				foreach($node->getRequires() as $req){
					$reqUnlocked = $manager->isNodeUnlocked($player, $branch->getId(), $req);
					$content .= ($reqUnlocked ? TextFormat::GREEN : TextFormat::RED) . "  - " . $req . "\n";
				}
				$content .= "\n";
			}
		}

		if(!empty($node->getEffects())){
			$content .= TextFormat::LIGHT_PURPLE . "Effects:\n";
			foreach($node->getEffects() as $key => $value){
				$content .= TextFormat::WHITE . "  - " . self::formatEffect($key, $value) . "\n";
			}
		}

		$form = new class($plugin, $branch, $node, $isUnlocked, $canUnlock, $requirementsMet, $content, $player->getName()) implements Form{
			private Main $plugin;
			private SkillBranch $branch;
			private SkillNode $node;
			private bool $isUnlocked;
			private bool $canUnlock;
			private bool $requirementsMet;
			private string $content;
			private string $playerName;

			public function __construct(Main $plugin, SkillBranch $branch, SkillNode $node, bool $isUnlocked, bool $canUnlock, bool $requirementsMet, string $content, string $playerName){
				$this->plugin = $plugin;
				$this->branch = $branch;
				$this->node = $node;
				$this->isUnlocked = $isUnlocked;
				$this->canUnlock = $canUnlock;
				$this->requirementsMet = $requirementsMet;
				$this->content = $content;
				$this->playerName = $playerName;
			}

			public function jsonSerialize() : mixed{
				$buttons = [];

				if($this->canUnlock && $this->requirementsMet){
					$buttons[] = ["text" => TextFormat::GREEN . "Unlock for " . $this->node->getCost() . " points"];
				}

				$buttons[] = ["text" => TextFormat::RED . "<< Back to " . $this->branch->getDisplayName()];

				return [
					"type" => "form",
					"title" => $this->branch->getIcon() . " " . $this->node->getName(),
					"content" => $this->content,
					"buttons" => $buttons
				];
			}

			public function handleResponse(Player $player, mixed $data) : void{
				if($data === null){
					return;
				}

				$hasUnlockButton = $this->canUnlock && $this->requirementsMet;

				if($hasUnlockButton && $data === 0){
					// Unlock the node
					$manager = $this->plugin->getPlayerDataManager();
					$manager->takePoints($player, $this->node->getCost());
					$manager->unlockNode($player, $this->branch->getId(), $this->node->getName());

					$player->sendMessage(TextFormat::GREEN . $this->plugin->getMessage("node.unlocked", [
						"node" => $this->node->getName(),
						"branch" => $this->branch->getDisplayName()
					]));

					// Send updated branch form
					TreeForm::sendBranchForm($player, $this->plugin, $this->branch);
					return;
				}

				// Back button
				TreeForm::sendBranchForm($player, $this->plugin, $this->branch);
			}
		};

		$player->sendForm($form);
	}

	private static function formatEffect(string $key, mixed $value) : string{
		return match($key){
			"damage_multiplier" => "Damage x" . $value,
			"critical_chance" => "Critical hit chance: " . round((float) $value * 100) . "%",
			"critical_multiplier" => "Critical damage: x" . $value,
			"life_steal" => "Life steal: " . round((float) $value * 100) . "% of damage",
			"double_drop_chance" => "Double drop chance: " . round((float) $value * 100) . "%",
			"mining_speed_multiplier" => "Mining speed: x" . $value,
			"bonus_saturation" => "Bonus food saturation: +" . $value,
			"speed_multiplier" => "Movement speed: x" . $value,
			"fall_damage_reduction" => "Fall damage reduction: " . round((float) $value * 100) . "%",
			default => $key . ": " . $value
		};
	}
}
