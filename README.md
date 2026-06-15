# SkillTree

A visual skill tree system for PocketMine-MP. Players earn skill points by performing actions and spend them to unlock customizable bonuses through a structured skill tree with multiple branches.

## Features

- **4 Skill Branches**: Combat, Mining, Farming, and Exploration — each with a unique progression path
- **Visual UI**: Full Form API-based interface for browsing branches, viewing node details, and unlocking skills
- **Node Dependencies**: Skills require prerequisite nodes to be unlocked first, creating meaningful progression paths
- **Customizable Effects**: Damage multipliers, critical strikes, life steal, double drops, bonus saturation, and more
- **Point System**: Earn skill points by killing mobs, mining ores, and defeating players
- **Multi-language Support**: English (default) and Russian included; easily add more languages
- **Fully Configurable**: All branches, nodes, effects, and point sources are defined in `config.yml`

## Installation

1. Download the latest release from [Poggit](https://poggit.pmmp.io/p/SkillTree) or compile from source
2. Place the `SkillTree.phar` file in your server's `plugins/` directory
3. Restart the server
4. Configure `plugins/SkillTree/config.yml` to your liking

## Commands

| Command | Description | Permission |
|---------|-------------|------------|
| `/skilltree` | Open the skill tree UI | `skilltree.command.skilltree` |
| `/skilltree [branch]` | Open a specific branch directly | `skilltree.command.skilltree` |
| `/skilltreeadmin reset <player>` | Reset a player's skill tree data | `skilltree.command.admin` |
| `/skilltreeadmin givepoints <player> [amount]` | Give skill points to a player | `skilltree.command.admin` |
| `/skilltreeadmin takepoints <player> [amount]` | Take skill points from a player | `skilltree.command.admin` |
| `/skilltreeadmin setpoints <player> <amount>` | Set a player's skill points | `skilltree.command.admin` |

## Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| `skilltree.command.skilltree` | Open the skill tree UI | true |
| `skilltree.command.admin` | Admin commands | op |
| `skilltree.branch.combat` | Access to Combat branch | true |
| `skilltree.branch.mining` | Access to Mining branch | true |
| `skilltree.branch.farming` | Access to Farming branch | true |
| `skilltree.branch.exploration` | Access to Exploration branch | true |

## Configuration

The default `config.yml` includes 4 branches with pre-configured nodes:

### Combat Branch
- **Damage Boost I** (+10% damage) → **Damage Boost II** (+20% damage) → **Critical Strike** (15% chance for 2x damage) → **Critical Master** (25% chance for 2.5x damage)
- **Damage Boost II** → **Life Steal** (10% of damage healed) → **Life Steal Master** (20% of damage healed)

### Mining Branch
- **Ore Sense I** (10% double drops) → **Ore Sense II** (25%) → **Ore Sense III** (40%) → **Mining Fortune** (15% triple drops)

### Farming Branch
- **Nourishment I** (+2 saturation) → **Nourishment II** (+5) → **Nourishment III** (+8) → **Feast** (+12)

### Exploration Branch
- **Double Ore I** (10% double drops) → **Double Ore II** (20%) → **Fortuna** (35%)

### Point Sources
| Action | Points |
|--------|--------|
| Kill any mob | 1 |
| Kill hostile mob (bonus) | +2 |
| Kill a player | 5 |
| Mine an ore block | 2 |

## Building from Source

To compile the plugin into a `.phar` file:

1. Install [DevTools](https://github.com/pmmp/DevTools) on your server
2. Place this folder in the `plugins/` directory
3. Use `/buildplugin SkillTree` in the server console

## API Version

Compatible with PocketMine-MP API **5.0.0** and above.

## License

This plugin is licensed under the [MIT License](LICENSE).
