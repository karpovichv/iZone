<?php

namespace iZone;

use pocketMine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketMine\command\CommandSender;
use pocketmine\level\Position;
use pocketMine\plugin\PluginBase;
use pocketmine\Player;


/**
 * Class MainClass
 * @package iZone
 */
class MainClass extends PluginBase implements CommandExecutor
{
    /**
     * @var Zone[]
     */
    private $zones;

    private $positions1;
    private $positions2;


    public function __construct()
    {
        $this->zones = [];
        $this->positions1 = [];
        $this->positions2 = [];
    }

    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();
		$this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);
	}

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
		if($command->getName() != "izone" || !($sender instanceof Player))
            return false;

        switch(array_shift($args))
        {
            case "pos1":
                $this->positions1[$sender->getName()] = Position::fromObject($sender, $sender->getLevel());
                break;

            case "pos2":
                $this->positions2[$sender->getName()] = Position::fromObject($sender, $sender->getLevel());
                break;

            case "create":
                    if(count($args) == 1)
                    {
                        $name = array_shift($args);
                        if(!empty($name))
                        {
                            if(isset($this->positions1[$sender->getName()]) && isset($this->positions2[$sender->getName()]))
                            {
                                $this->zones[$name] = new Zone($this, $name, $sender, $this->positions1[$sender->getName()], $this->positions2[$sender->getName()]);
                                $sender->sendMessage("[iZone] The private area have been created.");
                                unset($this->positions1[$sender->getName()]);
                                unset($this->positions2[$sender->getName()]);
                                return true;
                            }

                            $radius = $this->getConfig()->get("default-size");
                            $pos1 =  new Position($sender->x - $radius, $sender->y - $radius, $sender->z - $radius, $sender->getLevel());
                            $pos2 =  new Position($sender->x + $radius, $sender->y + $radius, $sender->z + $radius, $sender->getLevel());

                            $this->zones[$name] = new Zone($this, $name, $sender, $pos1, $pos2);
                            $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                            return true;
                        }
                    }
                    elseif(count($args) == 4)
                    {
                        $name = array_shift($args);
                        if(!empty($name))
                        {
                            $x = intval(array_shift($args));
                            $y = intval(array_shift($args));
                            $z = intval(array_shift($args));

                            $pos2 =  new Position($x, $y, $z, $sender->getLevel());

                            $this->zones[$name] = new Zone($this, $name, $sender, $sender, $pos2);
                            $sender->sendMessage($this->getConfig()->get('[iZone] The private area have been created.'));
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage("For Help Type: /izone help");
                            return true;
                        }
                    }
                    elseif(count($args) == 7)
                    {
                        $name = array_shift($args);

                        $x = intval(array_shift($args));
                        $y = intval(array_shift($args));
                        $z = intval(array_shift($args));

                        $x2 = intval(array_shift($args));
                        $y2 = intval(array_shift($args));
                        $z2 = intval(array_shift($args));

                        $pos1 =  new Position($x, $y, $z, $sender->getLevel());
                        $pos2 =  new Position($x2, $y2, $z2, $sender->getLevel());

                        $this->zones[$name] = new Zone($this, $name, $sender, $pos1, $pos2);
                        $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                        return true;
                    }
                break;

            case 'delete':
                $ps = count($args);
                if($ps == 1)
                {
                    $name = array_shift($args);
                    if(array_key_exists($name, $this->zones))
                    {
                        if($this->zones[$name]->getPermission($sender->getName()) == OWNER_PERM || $sender->isOp())
                        {
                            $owner = $this->zones[$name]->getOwner();
                            unset($this->zones[$name]);

                            $owner->sendMessage($this->getConfig()->get("[iZone] The private area {$name} have been removed."));
                            if($owner->getName() !== $sender->getName())
                                $sender->sendMessage("[iZone] The private area have been removed.");
                            return true;
                        }
                    }
                    else
                    {
                        $sender->sendMessage("[iZone] For Help Type: /izone help");
                        return true;
                    }
                }
                break;

            case "addg":

                $name = array_shift($args);
                $user = array_shift($args);
                $perm = array_shift($args);
                $time = array_shift($args);

                if(empty($name) || $name == null || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The private area don't exist.");
                    return true;
                }

                if(empty($user) || $user == null)
                {
                    $sender->sendMessage("[iZone] The player name cannot be empty");
                    return true;
                }

                $user = $this->getServer()->getPlayer($user);
                if(!$user instanceof Player && $user->getName() == $sender->getName())
                {
                    $sender->sendMessage("The user don't exist or not is online!");
                    return false;
                }

                if($this->zones[$name]->getPermission($sender->getName()) >= MOD_PERM || $sender->isOp())
                {
                    $this->zones[$name]->addGuest($user, $perm === NULL ? "" : $perm, $time === NULL ? 0 : $time);
                    $sender->sendMessage("[iZone] The player has been added!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have permission to do that.");
                return true;
                break;

            case 'deleteg':
                $name = array_shift($args);
                $user = array_shift($args);

                if(empty($name) || $name == null || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The private area don't exist.");
                    return true;
                }

                if(empty($user) || $user == null)
                {
                    $sender->sendMessage("[iZone] The player name cannot be empty");
                    return true;
                }

                $user = $this->getServer()->getPlayer($user);
                if(!$user instanceof Player)
                {
                    $sender->sendMessage("The user don't exist or not is online!");
                    return true;
                }

                if($this->zones[$name]->getPermission($sender->getName()) >= MOD_PERM || $sender->isOp())
                {
                    $this->zones[$name]->deleteGuest($user);
                    $sender->sendMessage("[iZone] The player has been removed!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have permission to do that.");
                return true;

                break;

            case 'permg':
                $name = array_shift($args);
                $user = array_shift($args);
                $perm = array_shift($args);
                $time = array_shift($args);

                if(empty($name) || $name == null || !isset($this->zones[$name]))
                {
                    $sender->sendMessage("[iZone] The private area don't exist.");
                    return true;
                }

                if(empty($user) || $user == null)
                {
                    $sender->sendMessage("[iZone] The player name cannot be empty");
                    return true;
                }


                $user = $this->getServer()->getPlayer($user);
                if(!$user instanceof Player)
                {
                    $sender->sendMessage("The user don't exist or not is online!");
                    return false;
                }

                if($this->zones[$name]->getPermission($sender->getName()) >= MOD_PERM || $sender->isOp())
                {
                    if($user->getName() === $sender->getName())
                    {
                        $sender->sendMessage("[iZone] You can't override your rank!");
                        return false;
                    }

                    $perm = $this->zones[$name]->getPermCode($perm);
                    $this->zones[$name]->setPermission($user, $perm, $time === null ? 0 : $time);
                    $sender->sendMessage("[iZone] The permission of the player has been updated!");
                    return true;
                }

                $sender->sendMessage("[iZone] You don't have permission to do that.");
                return true;

                break;

            case 'coord':
                    $sender->sendMessage("[iZone] Coord: X: {$sender->x} Y: {$sender->y} Z: {$sender->z}");
                break;

            case 'help':
                $sender->sendMessage("Usage: /izone <command> [parameters...]");
                $sender->sendMessage("Usage: /izone create [int] or /izc [int]");
                $sender->sendMessage("Usage: /izone create [player] or /izc [player]");
                $sender->sendMessage("Usage: /izone create [player1] [player2] or /izc [player1] [player2]");
                $sender->sendMessage("Usage: /izone create [x] [y] [z] or /izc [x] [y] [z]");
                $sender->sendMessage("Usage: /izone create [player] [x] [y] [z] or /izc [player] [x] [y] [z]");
                $sender->sendMessage("Usage: /izone create [x1] [y1] [z1] [x2] [y2] [z2] or /izc [x1] [y1] [z1] [x2] [y2] [z2]");
                $sender->sendMessage("Usage: /izone delete [owner] or /izd [owner]");
                $sender->sendMessage("Usage: /izone delete [x] [y] [z] or /izd [x] [y] [z]");
                $sender->sendMessage("Usage: /izone delete [x1] [y1] [z1] [x2] [y2] [z2] or /izd [x1] [y1] [z1] [x2] [y2] [z2]");
                $sender->sendMessage("Usage: /izone addg [player] or /izag [player]");
                $sender->sendMessage("Usage: /izone addg [player] [rank] or /izag [player] [rank]");
                $sender->sendMessage("Usage: /izone addg [player] [rank] [time] or /izag [player] [rank] [time]");
                $sender->sendMessage("Usage: /izone deleteg [player] or /izdg [player]");
                $sender->sendMessage("Usage: /izone permg [player] [rank] or /izpg [player] [rank]");
                $sender->sendMessage("Usage: /izone permg [player] [rank] [time] or /izpg [player] [rank] [time]");
                $sender->sendMessage("Usage: /izone coord or /izco or /coord");
                break;
        }

        return false;
	}

    /**
     * @param Player $owner
     *
     * @return Zone|bool
     */
    public function &getZone(Player $owner)
    {
        return isset($this->zones[$owner->getName()]) == true ? $this->zones[$owner->getName()] : false;
    }

    /**
     * @return Zone[]
     */
    public function &getAllZones()
    {
        return $this->zones;
    }

    /**
     * @param Player $owner
     *
     * @return bool
     */
    public function removeZone(Player $owner)
    {
        if(isset($this->zones[$owner->getName()]))
        {
            unset($this->zones[$owner->getName()]);
            return true;
        }
        else
            return false;
    }

}