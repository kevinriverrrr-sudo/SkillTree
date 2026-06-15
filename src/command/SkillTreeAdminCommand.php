<?php

declare(strict_types=1);

namespace SkillTree\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use SkillTree\Main;

class SkillTreeAdminCommand extends Command implements PluginOwned{

	use PluginOwnedTrait;

	private Main $plugin;

	public function __construct(Main $plugin){
		parent::__construct("skilltreeadmin", "Admin commands for SkillTree", "/skilltreeadmin <reset|givepoints|takepoints|setpoints> <player> [amount]");
		$this->setPermission("skilltree.command.admin");
		$this->plugin = $plugin;
		$this->owningPlugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			$sender->sendMessage(TextFormat::RED . "Usage: /skilltreeadmin <reset|givepoints|takepoints|setpoints> <player> [amount]");
			return true;
		}

		$action = strtolower($args[0]);
		$targetName = $args[1];
		$target = Server::getInstance()->getPlayerByPrefix($targetName);

		if($target === null){
			$sender->sendMessage(TextFormat::RED . "Player not found: " . $targetName);
			return true;
		}

		if(!$this->plugin->getPlayerDataManager()->isLoaded($target)){
			$sender->sendMessage(TextFormat::RED . "Player data not loaded for: " . $targetName);
			return true;
		}

		$manager = $this->plugin->getPlayerDataManager();

		switch($action){
			case "reset":
				$manager->resetPlayer($target);
				$sender->sendMessage(TextFormat::GREEN . "Reset skill tree data for " . $target->getName());
				$target->sendMessage(TextFormat::YELLOW . "Your skill tree data has been reset by an admin.");
				break;

			case "givepoints":
				$amount = isset($args[2]) ? (int) $args[2] : 1;
				if($amount <= 0){
					$sender->sendMessage(TextFormat::RED . "Amount must be a positive number.");
					break;
				}
				$manager->addPoints($target, $amount);
				$sender->sendMessage(TextFormat::GREEN . "Gave " . $amount . " skill points to " . $target->getName());
				$target->sendMessage(TextFormat::GREEN . $this->plugin->getMessage("admin.given_points", ["amount" => $amount]));
				break;

			case "takepoints":
				$amount = isset($args[2]) ? (int) $args[2] : 1;
				if($amount <= 0){
					$sender->sendMessage(TextFormat::RED . "Amount must be a positive number.");
					break;
				}
				$manager->takePoints($target, $amount);
				$sender->sendMessage(TextFormat::GREEN . "Took " . $amount . " skill points from " . $target->getName());
				$target->sendMessage(TextFormat::YELLOW . $this->plugin->getMessage("admin.taken_points", ["amount" => $amount]));
				break;

			case "setpoints":
				if(!isset($args[2])){
					$sender->sendMessage(TextFormat::RED . "Usage: /skilltreeadmin setpoints <player> <amount>");
					break;
				}
				$amount = (int) $args[2];
				if($amount < 0){
					$sender->sendMessage(TextFormat::RED . "Amount must be 0 or greater.");
					break;
				}
				$manager->setPoints($target, $amount);
				$sender->sendMessage(TextFormat::GREEN . "Set skill points to " . $amount . " for " . $target->getName());
				$target->sendMessage(TextFormat::GREEN . $this->plugin->getMessage("admin.set_points", ["amount" => $amount]));
				break;

			default:
				$sender->sendMessage(TextFormat::RED . "Unknown action: " . $action);
				$sender->sendMessage(TextFormat::RED . "Usage: /skilltreeadmin <reset|givepoints|takepoints|setpoints> <player> [amount]");
				break;
		}

		return true;
	}

	public function getOwningPlugin() : Main{
		return $this->plugin;
	}
}
