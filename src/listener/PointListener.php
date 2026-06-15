<?php

declare(strict_types=1);

namespace SkillTree\listener;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use SkillTree\Main;

class PointListener implements Listener{

	private Main $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->plugin->getPlayerDataManager()->loadPlayer($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$this->plugin->getPlayerDataManager()->unloadPlayer($event->getPlayer());
	}

	/**
	 * Award skill points for killing mobs.
	 * @priority MONITOR
	 */
	public function onEntityDeath(EntityDeathEvent $event) : void{
		$entity = $event->getEntity();
		$cause = $entity->getLastDamageCause();

		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player && $this->plugin->getPlayerDataManager()->isLoaded($damager)){
				$points = $this->getMobKillPoints($entity);
				if($points > 0){
					$this->plugin->getPlayerDataManager()->addPoints($damager, $points);
					$damager->sendMessage($this->plugin->getMessage("points.earned", [
						"amount" => $points,
						"action" => "mob kill",
						"total" => $this->plugin->getPlayerDataManager()->getPoints($damager)
					]));
				}
			}
		}
	}

	/**
	 * Award skill points for mining specific blocks.
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$block = $event->getBlock();

		if(!$this->plugin->getPlayerDataManager()->isLoaded($player)){
			return;
		}

		$points = $this->getBlockMinePoints($block);
		if($points > 0){
			$this->plugin->getPlayerDataManager()->addPoints($player, $points);
			$player->sendMessage($this->plugin->getMessage("points.earned", [
				"amount" => $points,
				"action" => "mining",
				"total" => $this->plugin->getPlayerDataManager()->getPoints($player)
			]));
		}
	}

	/**
	 * Award skill points for killing players.
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();

		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player && $this->plugin->getPlayerDataManager()->isLoaded($damager)){
				$points = (int) $this->plugin->getConfig()->get("point_sources", [])["player_kill"] ?? 5;
				if($points > 0){
					$this->plugin->getPlayerDataManager()->addPoints($damager, $points);
					$damager->sendMessage($this->plugin->getMessage("points.earned", [
						"amount" => $points,
						"action" => "player kill",
						"total" => $this->plugin->getPlayerDataManager()->getPoints($damager)
					]));
				}
			}
		}
	}

	private function getMobKillPoints(object $entity) : int{
		$sources = $this->plugin->getConfig()->get("point_sources", []);
		$mobKillPoints = (int) ($sources["mob_kill"] ?? 1);

		// Bonus for hostile mobs
		$hostileBonus = (int) ($sources["hostile_mob_kill_bonus"] ?? 0);
		$entityClass = get_class($entity);

		$hostileMobs = [
			"pocketmine\entity\mob\Zombie",
			"pocketmine\entity\mob\Skeleton",
			"pocketmine\entity\mob\Creeper",
			"pocketmine\entity\mob\Spider",
			"pocketmine\entity\mob\Enderman",
			"pocketmine\entity\mob\Blaze",
			"pocketmine\entity\mob\Witch",
			"pocketmine\entity\mob\Slime",
		];

		if(in_array($entityClass, $hostileMobs, true)){
			return $mobKillPoints + $hostileBonus;
		}

		return $mobKillPoints;
	}

	private function getBlockMinePoints(object $block) : int{
		$sources = $this->plugin->getConfig()->get("point_sources", []);
		$oreBlocks = $sources["ore_mine"]["blocks"] ?? ["diamond_ore", "emerald_ore", "gold_ore", "iron_ore", "lapis_ore", "redstone_ore"];
		$orePoints = (int) ($sources["ore_mine"]["points"] ?? 2);

		$blockName = strtolower($block->getName());
		$blockIdName = $block->getTypeId();

		// Check if the block matches any configured ore block names
		foreach($oreBlocks as $oreName){
			if(stripos($blockName, str_replace("_", " ", $oreName)) !== false || stripos($blockName, $oreName) !== false){
				return $orePoints;
			}
		}

		return 0;
	}
}
