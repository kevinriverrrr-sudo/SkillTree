<?php

declare(strict_types=1);

namespace SkillTree\listener;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use SkillTree\Main;

class EffectListener implements Listener{

	private Main $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * Apply combat effects: damage multiplier and critical strike.
	 * @priority HIGH
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$damager = $event->getDamager();
		if(!($damager instanceof Player)){
			return;
		}

		if(!$this->plugin->getPlayerDataManager()->isLoaded($damager)){
			return;
		}

		$effects = $this->plugin->getPlayerDataManager()->getCombinedEffects($damager);

		// Apply damage multiplier
		if(isset($effects["damage_multiplier"])){
			$multiplier = (float) $effects["damage_multiplier"];
			$event->setBaseDamage($event->getBaseDamage() * $multiplier);
		}

		// Apply critical strike
		if(isset($effects["critical_chance"]) && isset($effects["critical_multiplier"])){
			$critChance = (float) $effects["critical_chance"];
			$critMultiplier = (float) $effects["critical_multiplier"];
			if(mt_rand() / mt_getrandmax() < $critChance){
				$event->setBaseDamage($event->getBaseDamage() * $critMultiplier);
				$damager->sendMessage($this->plugin->getMessage("effect.critical_hit"));
			}
		}

		// Apply life steal
		if(isset($effects["life_steal"])){
			$stealPercent = (float) $effects["life_steal"];
			$healAmount = $event->getBaseDamage() * $stealPercent;
			$newHealth = min($damager->getHealth() + $healAmount, $damager->getMaxHealth());
			$damager->setHealth($newHealth);
		}
	}

	/**
	 * Apply mining effects: double drop chance.
	 * @priority HIGH
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		if(!$this->plugin->getPlayerDataManager()->isLoaded($player)){
			return;
		}

		$effects = $this->plugin->getPlayerDataManager()->getCombinedEffects($player);

		// Double drop chance
		if(isset($effects["double_drop_chance"])){
			$chance = (float) $effects["double_drop_chance"];
			if(mt_rand() / mt_getrandmax() < $chance){
				$drops = $event->getDrops();
				foreach($drops as $drop){
					$player->getWorld()->dropItem($event->getBlock()->getPosition(), $drop);
				}
				$player->sendMessage($this->plugin->getMessage("effect.double_drop"));
			}
		}
	}

	/**
	 * Apply farming effects: bonus saturation on food consumption.
	 * @priority HIGH
	 */
	public function onItemConsume(PlayerItemConsumeEvent $event) : void{
		$player = $event->getPlayer();
		if(!$this->plugin->getPlayerDataManager()->isLoaded($player)){
			return;
		}

		$effects = $this->plugin->getPlayerDataManager()->getCombinedEffects($player);

		// Bonus saturation
		if(isset($effects["bonus_saturation"])){
			$bonus = (float) $effects["bonus_saturation"];
			$currentFood = $player->getHungerManager()->getFood();
			$newFood = min(20, $currentFood + $bonus);
			$player->getHungerManager()->setFood((int) $newFood);
		}
	}
}
