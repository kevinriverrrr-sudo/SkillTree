<?php

declare(strict_types=1);

namespace SkillTree\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SkillTree\Main;
use SkillTree\ui\TreeForm;

class SkillTreeCommand extends Command implements PluginOwned{

	use PluginOwnedTrait;

	private Main $plugin;

	public function __construct(Main $plugin){
		parent::__construct("skilltree", "Open the skill tree UI", "/skilltree [branch]");
		$this->setPermission("skilltree.command.skilltree");
		$this->plugin = $plugin;
		$this->owningPlugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game.");
			return true;
		}

		if(!$this->testPermission($sender)){
			return true;
		}

		if(!$this->plugin->getPlayerDataManager()->isLoaded($sender)){
			$sender->sendMessage(TextFormat::RED . "Your data is still loading, please try again.");
			return true;
		}

		if(isset($args[0])){
			$branchId = strtolower($args[0]);
			$branch = $this->plugin->getSkillTreeData()->getBranch($branchId);
			if($branch === null){
				$sender->sendMessage(TextFormat::RED . $this->plugin->getMessage("branch.not_found", ["branch" => $args[0]]));
				return true;
			}
			TreeForm::sendBranchForm($sender, $this->plugin, $branch);
		}else{
			TreeForm::sendMainForm($sender, $this->plugin);
		}

		return true;
	}

	public function getOwningPlugin() : Main{
		return $this->plugin;
	}
}
