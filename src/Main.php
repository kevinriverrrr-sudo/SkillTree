<?php

declare(strict_types=1);

namespace SkillTree;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SkillTree\command\SkillTreeCommand;
use SkillTree\command\SkillTreeAdminCommand;
use SkillTree\listener\PointListener;
use SkillTree\listener\EffectListener;
use SkillTree\manager\PlayerDataManager;
use SkillTree\skill\SkillTree as SkillTreeData;

class Main extends PluginBase{

	private static Main $instance;
	private PlayerDataManager $playerDataManager;
	private SkillTreeData $skillTreeData;
	private Config $langConfig;

	public function onLoad() : void{
		self::$instance = $this;
	}

	public function onEnable() : void{
		$this->saveDefaultConfig();
		$this->saveResource("lang/eng.yml", false);
		$this->saveResource("lang/rus.yml", false);

		$this->langConfig = new Config($this->getDataFolder() . "lang/" . $this->getConfig()->get("language", "eng") . ".yml", Config::YAML);

		$this->skillTreeData = new SkillTreeData($this->getConfig()->getAll());
		$this->playerDataManager = new PlayerDataManager($this);

		$this->getServer()->getPluginManager()->registerEvents(new PointListener($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new EffectListener($this), $this);

		$this->getServer()->getCommandMap()->register("skilltree", new SkillTreeCommand($this));
		$this->getServer()->getCommandMap()->register("skilltreeadmin", new SkillTreeAdminCommand($this));
	}

	public function onDisable() : void{
		$this->playerDataManager->saveAll();
	}

	public static function getInstance() : Main{
		return self::$instance;
	}

	public function getPlayerDataManager() : PlayerDataManager{
		return $this->playerDataManager;
	}

	public function getSkillTreeData() : SkillTreeData{
		return $this->skillTreeData;
	}

	public function getMessage(string $key, array $replacements = []) : string{
		$message = $this->langConfig->get($key, $key);
		foreach($replacements as $placeholder => $value){
			$message = str_replace("{" . $placeholder . "}", (string) $value, $message);
		}
		return $message;
	}
}
