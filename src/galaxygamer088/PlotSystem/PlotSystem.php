<?php

namespace galaxygamer088\PlotSystem;

use pocketmine\block\BlockTypeIds;

use galaxygamer088\PlotSystem\Generator\GeneratePlotWorld;
use galaxygamer088\PlotSystem\Task\ChangeCrossingRand;
use galaxygamer088\PlotSystem\Task\ChangeCrossingWall;
use galaxygamer088\PlotSystem\Task\ChangePlotRand;
use galaxygamer088\PlotSystem\Task\ChangePlotWall;
use galaxygamer088\PlotSystem\Task\ClearPlot;
use galaxygamer088\PlotSystem\Task\SetRoad;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use pocketmine\block\VanillaBlocks;

class PlotSystem extends PluginBase implements Listener{

public Config $messages, $permissions, $plot, $rand, $wall;
public int $roadWidth = Options::ROAD_WIDTH, $plotSize = Options::PLOT_SIZE, $groundHeight = Options::GROUND_HEIGHT, $totalSize = Options::TOTAL_SIZE;
public array $optionList, $playerList, $plotList, $randList, $wallList;
public mixed $plotMenu;
const NULL = -1;
const PLOT = 0;
const ROAD_1 = 1; //green
const ROAD_2 = 2; //yellow
const WALL = 3; //black
const CROSSING = 4; //blue

    public function onLoad() : void{
        GeneratorManager::getInstance()->addGenerator(GeneratePlotWorld::class, "suchtplot", fn() => null, true);
    }

    public function onEnable() : void{
        $this->saveResource("Messages.yml");
        $this->saveResource("Permissions.yml");
        $this->saveResource("RandBlocks.yml");
        $this->saveResource("WallBlocks.yml");
        $this->messages = new Config($this->getDataFolder()."Messages.yml", Config::YAML);
        $this->permissions = new Config($this->getDataFolder()."Permissions.yml", Config::YAML);
        $this->rand = new Config($this->getDataFolder()."RandBlocks.yml", Config::YAML);
        $this->wall = new Config($this->getDataFolder()."WallBlocks.yml", Config::YAML);
        $this->plot = new Config($this->getDataFolder()."Plot.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function getLogo() : string{
        return $this->messages->getNested("Logo");
    }

    public function getMessage(string $message) : string{
        return $this->messages->getNested("Messages.".$message);
    }

    public function move(PlayerMoveEvent $ev){
        $p = $ev->getPlayer();
        if($this->isPlotWorld($p->getWorld())){
            $plotTo = $this->getPlotIdByPosition(round($ev->getTo()->getX(), 1), round($ev->getTo()->getZ(), 1));
            $plotFrom = $this->getPlotIdByPosition(round($ev->getFrom()->getX(), 1), round($ev->getFrom()->getZ(), 1));
            $world = $ev->getPlayer()->getWorld()->getFolderName();

            $x = 0;
            $z = 0;

            if($plotFrom[0] != $plotTo[0] or $plotFrom[1] != $plotTo[1]){
                if($plotFrom[2] == self::ROAD_1){
                    $x = -1;
                    $z = -$p->getDirectionVector()->getZ();
                }elseif($plotFrom[2] == self::ROAD_2){
                    $x = -$p->getDirectionVector()->getX();
                    $z = -1;
                }
            }else{
                if($plotFrom[2] == self::ROAD_1){
                    $x = 1;
                    $z = -$p->getDirectionVector()->getZ();
                }elseif($plotFrom[2] == self::ROAD_2){
                    $x = -$p->getDirectionVector()->getX();
                    $z = 1;
                }elseif($plotFrom[2] == self::CROSSING){
                    $x = 1;
                    $z = 1;
                }
            }
            if($plotFrom[2] == self::CROSSING){
                if($plotFrom[0] + 1 == $plotTo[0] and $plotFrom[1] == $plotTo[1]){
                    $x = -1;
                    $z = 1;
                }elseif($plotFrom[0] + 1 == $plotTo[0] and $plotFrom[1] + 1 == $plotTo[1]){
                    $x = -1;
                    $z = -1;
                }elseif($plotFrom[0] == $plotTo[0] and $plotFrom[1] + 1 == $plotTo[1]){
                    $x = 1;
                    $z = -1;
                }
            }

            if($plotTo[2] == self::ROAD_1 and $plotFrom[2] == self::CROSSING or $plotTo[2] == self::ROAD_2 and $plotFrom[2] == self::CROSSING){
                if($this->isPlotIdSet($world, $plotTo)){
                    if($this->isPlotIdSet($world, $plotFrom)){
                        if(!$this->is_in_array($plotFrom, $this->getAllMergePlots($world, $plotTo, true))){
                            if($this->getPlayerPermissions($world, $plotTo, $p->getName(), 2) or !$this->getPlayerPermissions($world, $plotTo, "Trusted", 0)){
                                if(!$this->getServer()->isOp($p->getName()) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeTrusted") == "true" and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotTo))){
                                    $p->knockBack($x, $z, 1, 0.8);
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("1"));
                                }else{
                                    $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                                }
                            }else{
                                $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                            }
                        }
                    }else{
                        if($this->getPlayerPermissions($world, $plotTo, $p->getName(), 2) or !$this->getPlayerPermissions($world, $plotTo, "Trusted", 0)){
                            if(!$this->getServer()->isOp($p->getName()) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeTrusted") == "true" and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotTo))){
                                $p->knockBack($x, $z, 1, 0.8);
                                $p->sendMessage($this->getLogo()." ".$this->getMessage("1"));
                            }else{
                                $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                            }
                        }else{
                            $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                        }
                    }
                }
            }

            if($plotTo[2] == self::PLOT and $plotFrom[2] == self::CROSSING or $plotTo[2] == self::PLOT and $plotFrom[2] == self::ROAD_1 or $plotTo[2] == self::PLOT and $plotFrom[2] == self::ROAD_2){
                if(!$this->isPlotIdSet($world, $plotTo)){
                    $ev->getPlayer()->sendActionBarMessage($this->getMessage("PlotMessage.4.1").$plotTo[0].";".$plotTo[1].$this->getMessage("PlotMessage.4.2")."\n".$this->getMessage("PlotMessage.4.3"));
                }else{
                    if($this->isPlotIdSet($world, $plotFrom)){
                        if(!$this->is_in_array($plotFrom, $this->getAllMergePlots($world, $plotTo, true))){
                            if($this->getPlayerPermissions($world, $plotTo, $p->getName(), 2) or !$this->getPlayerPermissions($world, $plotTo, "Trusted", 0)){
                                if(!$this->getServer()->isOp($p->getName()) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeTrusted") == "true" and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotTo))){
                                    $p->knockBack($x, $z, 1, 0.8);
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("1"));
                                }else{
                                    $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                                }
                            }else{
                                $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                            }
                        }
                    }else{
                        if($this->getPlayerPermissions($world, $plotTo, $p->getName(), 2) or !$this->getPlayerPermissions($world, $plotTo, "Trusted", 0)){
                            if(!$this->getServer()->isOp($p->getName()) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeTrusted") == "true" and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotTo))){
                                $p->knockBack($x, $z, 1, 0.8);
                                $p->sendMessage($this->getLogo()." ".$this->getMessage("1"));
                            }else{
                                $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                            }
                        }else{
                            $ev->getPlayer()->sendActionBarMessage($this->getPlotMessage($world, $plotTo));
                        }
                    }
                }
            }
        }
    }

    public function interact(PlayerInteractEvent $ev){
        $p = $ev->getPlayer();
        $block = $ev->getBlock();
        $item = $block->asItem();
        $pos = $block->getPosition();
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($pos->getX(), $pos->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                //if($this->is_in_array($item->getId(), [389, 324, 427, 428, 429, 430, 431, 330, 96, -149, -146, -148, -145, -147, 167, 107, 183, 184, 185, 187, 186, 143, -144, -141, -143, -140, -142, 77, 69])){
                if($this->is_in_array($item->getTypeId(), [389, 324, 427, 428, 429, 430, 431, 330, 96, -149, -146, -148, -145, -147, 167, 107, 183, 184, 185, 187, 186, 143, -144, -141, -143, -140, -142, 77, 69])){
                    if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 5) and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                        $ev->cancel();
                    }
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function openInventory(InventoryOpenEvent $ev){
        $p = $ev->getPlayer();
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($p->getEyePos()->getX(), $p->getEyePos()->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 6) and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function blockPlace(BlockPlaceEvent $ev){
        $p = $ev->getPlayer();
        //since pm removed get block $block = $ev->getBlock()->getPosition();
        //but
        $BlockAgainst = $ev->getBlockAgainst();
        $blockInSight = $p->getLineOfSight(30, 0, [BlockTypeIds::AIR,BlockTypeIds::WATER]);
        $blockAmountInSight = count($blockInSight);
        foreach($blockInSight as $b){
            if($b !== $BlockAgainst) {
                $block = array_shift($blockInSight);
            }
        }
       /** $x = $block->getPosition()->getX();
        $y = $block->getPosition()->getY();
        $z = $block->getPosition()->getZ();
        $x2 = $BlockAgainst->getPosition()->getX();
        $y2 = $BlockAgainst->getPosition()->getY();
        $z2 = $BlockAgainst->getPosition()->getZ();
        $p->sendMessage("Clicked block: [" .$x2. "/".$y2. "/". $z2."]");
        $p->sendMessage("placed block: [" .$x. "/".$y."/". $z."]");
        return; **/
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($block->getPosition()->getX(), $block->getPosition()->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 3) and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function blockBreak(BlockBreakEvent $ev){
        $p = $ev->getPlayer();
        $block = $ev->getBlock()->getPosition();
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($block->getX(), $block->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 4) and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }else{
                    if($this->is_in_array($ev->getBlock()->getTypeId(), [54, 146, 205, 218]) and !$this->getPlayerPermissions($world, $plotId, $p->getName(), 6) and !key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                        $ev->cancel();
                    }
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function bucketFill(PlayerBucketFillEvent $ev){
        $p = $ev->getPlayer();
        $block = $ev->getBucket()->getBlock()->getPosition();
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($block->getX(), $block->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 3) and $this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Owner") !== $p->getName() and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function bucketEmpty(PlayerBucketEmptyEvent $ev){
        $p = $ev->getPlayer();
        $block = $ev->getBucket()->getBlock()->getPosition();
        $world = $p->getWorld()->getFolderName();
        $plotId = $this->getPlotIdByPosition($block->getX(), $block->getZ());

        if($this->isPlotWorld($p->getWorld())){
            if($this->isPlotIdSet($world, $plotId)){
                if(!$this->getPlayerPermissions($world, $plotId, $p->getName(), 4) and $this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Owner") !== $p->getName() and $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MustBeHelper") == "true" and !$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }else{
                if(!$this->getServer()->isOp($p->getName())){
                    $ev->cancel();
                }
            }
        }
    }

    public function onExplosion(EntityExplodeEvent $ev){
        if($this->isPlotWorld($ev->getPosition()->getWorld())){
            $ev->cancel();
        }
    }

    public function getPlotMessage(string $world, array $plotTo) : string{
        $message = "";
        $stringId = $plotTo[0].";".$plotTo[1];
        if(count($this->getAllMergePlots($world, $plotTo, false)) !== 1){
            $owner = $this->getAllMergeOwner($world, $plotTo);
            $keys = array_keys($owner);
            if(count($owner) == 1){
                $message = $this->getMessage("PlotMessage.1.1")."\n".$this->getMessage("PlotMessage.1.2").$keys[0];
            }
            if(count($owner) > 1){
                $message = $this->getMessage("PlotMessage.2.1")."\n".$this->getMessage("PlotMessage.2.2");
            }
        }else{
            $message = $this->getMessage("PlotMessage.3.1").$stringId.$this->getMessage("PlotMessage.3.2")."\n".$this->getMessage("PlotMessage.3.3").$this->plot->getNested($world.".".$stringId.";0.Owner");
        }
        return $message;
    }

    public function isPlotWorld(World $world) : bool{
        if($world->getProvider()->getWorldData()->getGenerator() == "suchtplot"){
            return true;
        }
        return false;
    }

    public function getAllPlayer() : array{
        $playerList = [];
        $count = 1;
        if($this->permissions->getNested("Options.ConnectToPlugin.SupporterPlugin") == "true"){
            $playerListConfig = new Config($this->getServer()->getPluginManager()->getPlugin("SupporterPlugin")->getDataFolder()."PlayerList.yml", Config::YAML);
            foreach($playerListConfig->getAll()["PlayerList"] as $id => $player){
                if($id !== "Count"){
                    $playerList[$player] = $count;
                    $count++;
                }
            }
        }else{
            foreach($this->getServer()->getOnlinePlayers() as $player){
                $playerList[$player->getName()] = $count;
                $count++;
            }
        }

        return $playerList;
    }

    public function onCommand(CommandSender $p, Command $cmd, string $label, array $args) : bool{
        $cmdname = $cmd->getName();

        if($cmdname == "p"){
            if($p instanceof Player){

                if(isset($args[0]) and $args[0] == "create"){
                    if($this->getServer()->isOp($p->getName())){
                        if(isset($args[1])){
                            $this->plot->setNested($args[1].".0;0;0.Owner", "Spawn");
                            $this->plot->setNested($args[1].".0;0;0.Spawn", (Options::PLOT_SIZE / 2).",".(Options::GROUND_HEIGHT + 2).",".(Options::TOTAL_SIZE - 1));
                            $this->plot->setNested($args[1].".0;0;0.Player.Helper", "0111000100");
                            $this->plot->setNested($args[1].".0;0;0.Player.Trusted", "1000000000");
                            $this->plot->save();
                            $this->getServer()->getWorldManager()->generateWorld(
                                name: $args[1],
                                options: WorldCreationOptions::create()
                                    ->setSeed(mt_rand())
                                    ->setGeneratorClass(GeneratorManager::getInstance()->getGenerator("suchtplot")->getGeneratorClass())
                                    ->setSpawnPosition(new Vector3((Options::PLOT_SIZE / 2), Options::GROUND_HEIGHT + 1, (Options::PLOT_SIZE / 2)))
                            );
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("28.1").$args[1].$this->getMessage("28.2"));
                        }else{
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("29"));
                        }
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                    }
                }

                elseif($this->isPlotWorld($p->getWorld())){
                    $name = $p->getName();
                    $world = $p->getWorld()->getFolderName();
                    $posX = $p->getPosition()->getX();
                    $posZ = $p->getPosition()->getZ();
                    $plotId = $this->getPlotIdByPosition($posX, $posZ);
                    $x = $plotId[0];
                    $z = $plotId[1];
                    $id = $plotId[2];
                    $stringId = $x.";".$z.";".$id;

                    if(!isset($args[0])){

                        if($this->isPlotIdSet($world, $plotId)){
                            $this->PlotMenu($p, $world, $plotId);
                        }else{
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("2"));
                        }

                    }else{

                        if($args[0] == "tp" or $args[0] == "warp"){
                            if(!isset($args[1])){
                                $p->sendMessage("/p <tp|warp> <plotId>");
                            }else{
                                $plot = explode(";", $args[1]);
                                if(count($plot) == 2 and is_numeric($plot[0]) and is_numeric($plot[1])){
                                    $pos = new Position(($plot[0] * Options::TOTAL_SIZE + (Options::PLOT_SIZE / 2)), (Options::GROUND_HEIGHT + 2), ($plot[1] * Options::TOTAL_SIZE - 1), $p->getWorld());
                                    $p->teleport($pos);
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("3.1").$plot[0].";".$plot[1].$this->getMessage("3.2"));
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("4"));
                                }
                            }
                        }

                        elseif($args[0] == "list"){
                            if(isset($args[1])){
                                if(isset($this->getAllPlayer()[$args[1]])){
                                    if(count($this->getClaimedPlots($args[1], $world)) > 0){
                                        $this->PlayerPlotListMenu($p, $world, $args[1]);
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("5.1").$args[1].$this->getMessage("5.2"));
                                    }
                                }else{
                                    $this->PlayerListMenu($p, $world, $plotId, "list");
                                }
                            }else{
                                $this->PlayerPlotListMenu($p, $world, $name);
                            }
                        }

                        elseif($args[0] == "a" or $args[0] == "auto"){
                            $plotCount = 1;
                            $radius = 1;
                            $autoPlot = [0, 0, self::PLOT];
                            $this->isAutoPlot($p, $world, $autoPlot);

                            for($counter = 1; $counter != 1000000; $counter++){
                                $autoPlot = [$radius, 0, self::PLOT];
                                if($plotCount == 1){
                                    if($this->isAutoPlot($p, $world, $autoPlot)){
                                        return true;
                                    }
                                }
                                $autoPlot = [$autoPlot[0], $autoPlot[1] + $plotCount, self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }
                                $autoPlot = [$autoPlot[0], $autoPlot[1] - ($plotCount * 2), self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }

                                $autoPlot = [0, $radius, self::PLOT];
                                if($plotCount == 1){
                                    if($this->isAutoPlot($p, $world, $autoPlot)){
                                        return true;
                                    }
                                }
                                $autoPlot = [$autoPlot[0] + $plotCount, $autoPlot[1], self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }
                                $autoPlot = [$autoPlot[0] - ($plotCount * 2), $autoPlot[1], self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }

                                $autoPlot = [-$radius, 0, self::PLOT];
                                if($plotCount == 1){
                                    if($this->isAutoPlot($p, $world, $autoPlot)){
                                        return true;
                                    }
                                }
                                $autoPlot = [$autoPlot[0], $autoPlot[1] - $plotCount, self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }
                                $autoPlot = [$autoPlot[0], $autoPlot[1] + ($plotCount * 2), self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }

                                $autoPlot = [0, -$radius,  self::PLOT];
                                if($plotCount == 1){
                                    if($this->isAutoPlot($p, $world, $autoPlot)){
                                        return true;
                                    }
                                }
                                $autoPlot = [$autoPlot[0] - $plotCount, $autoPlot[1], self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }
                                $autoPlot = [$autoPlot[0] + ($plotCount * 2), $autoPlot[1], self::PLOT];
                                if($this->isAutoPlot($p, $world, $autoPlot)){
                                    return true;
                                }

                                if($radius == $plotCount){
                                    $radius++;
                                    $plotCount = 1;
                                }else{
                                    $plotCount++;
                                }
                            }
                        }

                        elseif($args[0] == "h" or $args[0] == "home"){
                            if(isset($args[1])){
                                if(is_numeric($args[1]) and $args[1] > 0){
                                    if(count($this->getClaimedPlots($p->getName(), $world)) >= $args[1]){
                                        $homePlot = $this->getClaimedPlots($p->getName(), $world)[$args[1]];
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("6.1").$args[1].$this->getMessage("6.2"));
                                    }
                                }elseif(isset($this->getAllPlayer()[$args[1]])){
                                    if(isset($args[2]) and is_numeric($args[2]) and $args[2] > 0){
                                        if(count($this->getClaimedPlots($args[1], $world)) >= $args[2]){
                                            $homePlot = $this->getClaimedPlots($args[1], $world)[$args[2]];
                                        }else{
                                            $p->sendMessage($this->getLogo()." ".$this->getMessage("7.1").$args[1].$this->getMessage("7.2").$args[2].$this->getMessage("7.3"));
                                        }
                                    }else{
                                        if(count($this->getClaimedPlots($args[1], $world)) > 0){
                                            $homePlot = $this->getClaimedPlots($args[1], $world)[1];
                                        }else{
                                            $p->sendMessage($this->getLogo()." ".$this->getMessage("5.1").$args[1].$this->getMessage("5.2"));
                                        }
                                    }
                                }else{
                                    if(!isset($args[2])){$args[2] = 1;}
                                    $this->PlayerListMenu($p, $world, $plotId, "home", $args[2]);
                                }
                            }else{
                                if(count($this->getClaimedPlots($p->getName(), $world)) >= 1){
                                    $homePlot = $this->getClaimedPlots($p->getName(), $world)[1];
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("8"));
                                }
                            }
                            if(isset($homePlot)){
                                $this->teleportPlayerToPlot($p, $world, $homePlot);
                                $p->sendMessage($this->getLogo()." ".$this->getMessage("9"));
                            }
                        }

                        elseif(!$this->isPlotIdSet($world, $plotId)){

                            if($args[0] == "claim"){
                                if($id == self::PLOT){
                                    $maxPlot = $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".MaxPlotCount");
                                    if($this->getServer()->isOp($name) or $maxPlot == "*" or count($this->getClaimedPlots($p, $world)) <= $maxPlot){
                                        $this->plot->setNested($world.".".$stringId.".Owner", $name);
                                        $this->plot->setNested($world.".".$stringId.".Spawn", ($x * Options::TOTAL_SIZE + (Options::PLOT_SIZE / 2)).",".(Options::GROUND_HEIGHT + 2).",".$z * Options::TOTAL_SIZE - 1);
                                        $this->plot->setNested($world.".".$stringId.".Player.Helper", "0111000100");
                                        $this->plot->setNested($world.".".$stringId.".Player.Trusted", "1000000000");
                                        $this->plot->save();
                                        //$block1 = BlockFactory::getInstance()->get(Options::ROAD_CLAIM_RAND_BLOCK_ID, Options::ROAD_CLAIM_RAND_BLOCK_META);
                                        //$block2 = BlockFactory::getInstance()->get(Options::ROAD_CLAIM_UNDER_RAND_BLOCK_ID, Options::ROAD_CLAIM_UNDER_RAND_BLOCK_META);
                                        $block1 = InternalBlockFactory::get(Options_test::ROAD_CLAIM_RAND_BLOCK);
                                        $block2 = InternalBlockFactory::get(Options_test::ROAD_CLAIM_UNDER_RAND_BLOCK);
                                        $this->getScheduler()->scheduleTask(new ChangePlotRand($p->getPosition(), $plotId, $block1, $block2, [true, true, true, true], true, true, true));
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("10"));
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("11"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("12"));
                                }
                            }

                            elseif($args[0] == "merge"){
                                if($this->permissions->getNested("Ranks.".$this->getConfigRank($p).".CanMerge") == "true" or $this->getServer()->isOp($name)){
                                    if($id == self::ROAD_1 or $id == self::ROAD_2){
                                        $this->plot->setNested($world.".".$stringId.".Player.Helper", "0111000100");
                                        $this->plot->setNested($world.".".$stringId.".Player.Trusted", "1000000000");
                                        $this->plot->save();
                                        $this->registerMerge($p, $plotId, true);
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("13"));
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("14"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                        }elseif($args[0] == "claim" or $args[0] == "merge"){

                            $p->sendMessage($this->getLogo()." ".$this->getMessage("16"));

                        }elseif($this->isPlotIdSet($world, $plotId)){

                            if($args[0] == "i" or $args[0] == "info"){
                                $this->PlotInfoMenu($p, $world, $plotId);
                            }

                            elseif($args[0] == "menü" or $args[0] == "ui"){
                                $this->PlotMenu($p, $world, $plotId);
                            }

                            elseif($args[0] == "setspawn"){
                                if($this->getPlayerPermissions($world, $plotId, $p->getName(), 9) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".SetSpawn") == "true" or $this->getServer()->isOp($name)){
                                    $spawn = $p->getPosition();
                                    $x = round($spawn->getX(), 1);
                                    $y = round($spawn->getY(), 1);
                                    $z = round($spawn->getZ(), 1);
                                    $merge = $this->getAllMergePlots($world, $plotId, false);
                                    for($s = 0; $s <= count($merge) - 1; $s++){
                                        $this->plot->setNested($world.".".$merge[$s][0].";".$merge[$s][1].";".$merge[$s][2].".Spawn", $x.",".$y.",".$z);
                                        $this->plot->save();
                                    }
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("17").$x.", ".$y.", ".$z);
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "setowner"){
                                if($id == self::PLOT){
                                    if($this->plot->getNested($world.".".$x.";".$z.";".$id.".Owner") == $p->getName() or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".SetOwner") == "true" or $this->getServer()->isOp($p->getName())) {
                                        if(isset($args[1])){
                                            if(!isset($this->getAllPlayer()[$args[1]])){
                                                if(!$this->getServer()->isOp($name)){
                                                    $this->PlayerListMenu($p, $world, $plotId, "setowner");
                                                }else{
                                                    $this->plot->setNested($world.".".$x.";".$z.";".$id.".Owner", $args[1]);
                                                    $this->plot->save();
                                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("18.1").$args[1].$this->getMessage("18.2"));
                                                }
                                            }else{
                                                $this->plot->setNested($world.".".$x.";".$z.";".$id.".Owner", $args[1]);
                                                $this->plot->save();
                                                $p->sendMessage($this->getLogo()." ".$this->getMessage("18.1").$args[1].$this->getMessage("18.2"));
                                            }
                                        }else{
                                            $this->PlayerListMenu($p, $world, $plotId, "setowner");
                                        }
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("12"));
                                }
                            }

                            elseif($args[0] == "addhelper" or $args[0] == "removehelper" or $args[0] == "helper" or $args[0] == "helperlist" or $args[0] == "helfer" or $args[0] == "helferlist"){
                                if($this->getPlayerPermissions($world, $plotId, $p->getName(), 7) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Helper") == "true" or $this->getServer()->isOp($name)){
                                    $this->HelperMenu($p, $world, $plotId);
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "deny" or $args[0] == "undeny" or $args[0] == "trust" or $args[0] == "untrust" or $args[0] == "trusted" or $args[0] == "trustedlist"){
                                if($this->getPlayerPermissions($world, $plotId, $p->getName(), 8) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Trusted") == "true" or $this->getServer()->isOp($name)){
                                    $this->TrustedMenu($p, $world, $plotId);
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "kick"){
                                $playerInPlot = $this->getAllPlayerInPlot($world, $plotId);
                                if(!isset($args[1]) or $args[1] == "all"){
                                    foreach($playerInPlot as $count => $player){
                                        if(!isset($this->getAllMergeOwner($world, $plotId)[$player])){
                                            $player = $this->getServer()->getPlayerByPrefix($player);
                                            $player->teleport($this->getServer()->getWorldManager()->getWorldByName($world)->getSpawnLocation());
                                            $player->sendMessage($this->getLogo()." ".$this->getMessage("20"));
                                        }
                                    }
                                }
                                $p->sendMessage($this->getLogo()." ".$this->getMessage("21"));
                            }

                            elseif($args[0] == "clear"){
                                if($this->plot->getNested($world.".".$x.";".$z.";".$id.".Owner") == $p->getName() or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Clear") == "true" or $this->getServer()->isOp($name)) {
                                    if(!$this->isPlotIdSet($world, [$x, $z, self::ROAD_1]) and !$this->isPlotIdSet($world, [$x, $z, self::ROAD_2]) and !$this->isPlotIdSet($world, [($x - 1), $z, self::ROAD_1]) and !$this->isPlotIdSet($world, [$x, ($z - 1), self::ROAD_2])){
                                        $this->removePlotId($world, $plotId);
                                        //$rand1 = BlockFactory::getInstance()->get(Options::ROAD_RAND_BLOCK_ID, Options::ROAD_RAND_BLOCK_META);
                                        $rand1 = InternalBlockFactory::get(Options_test::ROAD_RAND_BLOCK);
                                        //$rand2 = BlockFactory::getInstance()->get(Options::ROAD_UNDER_RAND_BLOCK_ID, Options::ROAD_UNDER_RAND_BLOCK_META);
                                        $rand2 = InternalBlockFactory::get(Options_test::ROAD_UNDER_RAND_BLOCK);
                                        //$wall = BlockFactory::getInstance()->get(Options::ROAD_WALL_BLOCK_ID, Options::ROAD_WALL_BLOCK_META);
                                        $wall = InternalBlockFactory::get(Options_test::ROAD_WALL_BLOCK);
                                        $this->getScheduler()->scheduleTask(new ChangePlotRand($p->getPosition(), $plotId, $rand1, $rand2, [true, true, true, true], true, true, true));
                                        $this->getScheduler()->scheduleTask(new ChangePlotWall($p->getPosition(), $plotId, $wall, [true, true, true, true], true));
                                        $this->getScheduler()->scheduleTask(new ClearPlot($p->getWorld(), $plotId));
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("22"));
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("23"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "reset"){
                                if($this->plot->getNested($world.".".$x.";".$z.";".$id.".Owner") == $p->getName() or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Reset") == "true" or $this->getServer()->isOp($name)){
                                    if($plotId[2] == self::PLOT){
                                        $this->getScheduler()->scheduleTask(new ClearPlot($p->getWorld(), $plotId));
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("24"));
                                    }else{
                                        $this->getScheduler()->scheduleTask(new SetRoad($p->getPosition(), $plotId, true));
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("25"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "rand"){
                                if($this->getPlayerPermissions($world, $plotId, $p->getName(), 11) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Rand") == "true" or $this->getServer()->isOp($p->getName())){
                                    $this->ChangePlotRandMenu($p, $world, $plotId);
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "wall" or $args[0] == "wand"){
                                if($this->getPlayerPermissions($world, $plotId, $p->getName(), 10) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Wall") == "true" or $this->getServer()->isOp($p->getName())){
                                    $this->ChangePlotWallMenu($p, $world, $plotId);
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }

                            elseif($args[0] == "unmerge"){
                                if($this->permissions->getNested("Ranks.".$this->getConfigRank($p).".CanMerge") == "true" or $this->getServer()->isOp($name)){
                                    if($id == self::ROAD_1 or $id == self::ROAD_2){
                                        $this->removePlotId($world, $plotId);
                                        $this->registerMerge($p, $plotId, false);
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("26"));
                                    }else{
                                        $p->sendMessage($this->getLogo()." ".$this->getMessage("14"));
                                    }
                                }else{
                                    $p->sendMessage($this->getLogo()." ".$this->getMessage("15"));
                                }
                            }else{
                                $this->PlotMenu($p, $world, $plotId);
                            }

                        }else{
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("2"));
                        }
                    }
                }else{
                    $p->sendMessage($this->getLogo()." ".$this->getMessage("19"));
                }
            }
        }
        return true;
    }

    public function isAutoPlot(Player $p, string $world, array $plotId) : bool{
        if(!$this->isPlotIdSet($world, $plotId)){
            $pos = new Position($plotId[0] * Options::TOTAL_SIZE + (Options::PLOT_SIZE / 2), (Options::GROUND_HEIGHT + 2), $plotId[1] * Options::TOTAL_SIZE - 1, $this->getServer()->getWorldManager()->getWorldByName($world));
            $p->teleport($pos);
            $p->sendMessage($this->getLogo()." ".$this->getMessage("27.1").$plotId[0].$this->getMessage("27.2").$plotId[1].$this->getMessage("27.3"));
            return true;
        }
        return false;
    }

    public function PlotInfoMenu(Player $p, string $world, array $plotId){
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }

            return true;
        });
        $plots = $this->getAllMergePlots($world, $plotId, false);
        if(count($plots) == 1){
            $form->setTitle($this->getMessage("PlotInfoMenu.1.1").$plotId[0].";".$plotId[1].$this->getMessage("PlotInfoMenu.1.2"));
        }else{
            $form->setTitle($this->getMessage("PlotInfoMenu.2"));
        }

        $infoText = $this->getMessage("PlotInfoMenu.3");

        if(count($plots) != 1){
            $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.4");
            foreach($plots as $count => $plot){
                $infoText .= "\n> ".$plot[0].";".$plot[1];
            }
        }

        $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.5");
        foreach($this->getAllMergeOwner($world, $plotId) as $owner => $count){
            $infoText .= "\n> ".$owner;
        }

        $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.6").$this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Spawn");

        $allPlayer = $this->plot->getAll()[$world][$plotId[0].";".$plotId[1].";".$plotId[2]]["Player"];

        $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.7");
        foreach($allPlayer as $player => $permission){
            if($player != "Helper" and $player != "Trusted"){
                if(str_split($permission)[0] == 1){
                    $infoText .= "\n> ".$player;
                }
            }
        }

        $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.8");
        foreach($allPlayer as $player => $permission){
            if($player != "Helper" and $player != "Trusted"){
                if(str_split($permission)[1] == 1){
                    $infoText .= "\n> ".$player;
                }
            }
        }

        $infoText .= "\n\n".$this->getMessage("PlotInfoMenu.9");
        foreach($allPlayer as $player => $permission){
            if($player != "Helper" and $player != "Trusted"){
                if(str_split($permission)[2] == 1){
                    $infoText .= "\n> ".$player;
                }
            }
        }

        $form->setContent($infoText);
        $form->addButton($this->getMessage("PlotInfoMenu.10"));

        $form->sendToPlayer($p);
        return $form;
    }

    public function ChangePlotRandMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result == 1){
                $this->ChangePlotUnderRandMenu($p, $world, $plotId);
            }

            if($result !== 0 and $result !== 1){
                $plots = $this->getAllMergePlots($world, $plotId, true);
                //$block1 = InternalBlockFactory::getInstance()->get($this->rand->getNested("RandBlocks.".$this->randList[$result].".Id"), $this->rand->getNested("RandBlocks.".$this->randList[$result].".Meta"));
                $block1 = InternalBlockFactory::getBlock($this->rand->getNested("RandBlocks.".$this->randList[$result].".Id"), $this->rand->getNested("RandBlocks.".$this->randList[$result].".Meta"));
                $block2 = VanillaBlocks::AIR();
                for($i = 0; $i <= count($plots) - 1; $i++){
                    if($plots[$i][2] == self::PLOT){
                        $shape = $this->getPlotShape($world, $plots[$i]);
                        $this->getScheduler()->scheduleTask(new ChangePlotRand($p->getPosition(), $plots[$i], $block1, $block2, [$shape[0] ^= 1, $shape[1] ^= 1, $shape[2] ^= 1, $shape[3] ^= 1], false, true, false));
                    }else{
                        if($plots[$i][2] == self::ROAD_1){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING], $block1, $block2, [false, true, false, false], false, true, false, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block1, $block2, [false, false, false, true], false, true, false, true));
                            }
                        }
                        if($plots[$i][2] == self::ROAD_2){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block1, $block2, [false, false, true, false], false, true, false, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING], $block1, $block2, [true, false, false, false], false, true, false, true));
                            }
                        }
                    }
                }
                $p->sendMessage($this->getLogo()." ".$this->getMessage("ChangePlotRandMenu.1.1").$this->randList[$result].$this->getMessage("ChangePlotRandMenu.1.2"));
            }

            return true;
        });
        $form->setTitle($this->getMessage("ChangePlotRandMenu.2"));
        $form->setContent($this->getMessage("ChangePlotRandMenu.3"));
        $form->addButton($this->getMessage("ChangePlotRandMenu.4"));
        $form->addButton($this->getMessage("ChangePlotRandMenu.5"));

        $keys = array_keys($this->rand->get("RandBlocks"));
        $count = 2;
        for($r = 0; $r <= count($keys) - 1; $r++){
            if($this->rand->getNested("Permissions.".$this->getConfigRank($p)) >= $this->rand->getNested("RandBlocks.".$keys[$r].".Permission") or $this->getServer()->isOp($p->getName())){
                $form->addButton($keys[$r]."\nId: ".$this->rand->getNested("RandBlocks.".$keys[$r].".Id")." | Meta: ".$this->rand->getNested("RandBlocks.".$keys[$r].".Meta"));
                $this->randList[$count] = $keys[$r];
                $count++;
            }
        }

        $form->sendToPlayer($p);
        return $form;
    }

    public function ChangePlotUnderRandMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result !== 0){
                $plots = $this->getAllMergePlots($world, $plotId, true);
                $block1 = InternalBlockFactory::getBlock(0, 0);
                $block2 = InternalBlockFactory::getBlock($this->rand->getNested("UnderRandBlocks.".$this->randList[$result].".Id"), $this->rand->getNested("UnderRandBlocks.".$this->randList[$result].".Meta"));
                for($i = 0; $i <= count($plots) - 1; $i++){
                    if($plots[$i][2] == self::PLOT){
                        $shape = $this->getPlotShape($world, $plots[$i]);
                        $this->getScheduler()->scheduleTask(new ChangePlotRand($p->getPosition(), $plots[$i], $block1, $block2, [$shape[0] ^= 1, $shape[1] ^= 1, $shape[2] ^= 1, $shape[3] ^= 1], false, false, true));
                    }else{
                        if($plots[$i][2] == self::ROAD_1){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING], $block1, $block2, [false, true, false, false], false, false, true, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block1, $block2, [false, false, false, true], false, false, true, true));
                            }
                        }
                        if($plots[$i][2] == self::ROAD_2){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block1, $block2, [false, false, true, false], false, false, true, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING], $block1, $block2, [true, false, false, false], false, false, true, true));
                            }
                        }
                    }
                }
                $p->sendMessage($this->getLogo()." ".$this->getMessage("ChangePlotUnderRandMenu.1.1").$this->randList[$result].$this->getMessage("ChangePlotUnderRandMenu.1.2"));
            }

            return true;
        });
        $form->setTitle($this->getMessage("ChangePlotUnderRandMenu.2"));
        $form->setContent($this->getMessage("ChangePlotUnderRandMenu.3"));
        $form->addButton($this->getMessage("ChangePlotUnderRandMenu.4"));

        $keys = array_keys($this->rand->get("UnderRandBlocks"));
        $count = 1;
        for($r = 0; $r <= count($keys) - 1; $r++){
            if($this->rand->getNested("Permissions.".$this->getConfigRank($p)) >= $this->rand->getNested("UnderRandBlocks.".$keys[$r].".Permission") or $this->getServer()->isOp($p->getName())){
                $form->addButton($keys[$r]."\nId: ".$this->rand->getNested("UnderRandBlocks.".$keys[$r].".Id")." | Meta: ".$this->rand->getNested("UnderRandBlocks.".$keys[$r].".Meta"));
                $this->randList[$count] = $keys[$r];
                $count++;
            }
        }

        $form->sendToPlayer($p);
        return $form;
    }

    public function ChangePlotWallMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result !== 0){
                $plots = $this->getAllMergePlots($world, $plotId, true);
                $block = InternalBlockFactory::getBlock($this->wall->getNested("WallBlocks.".$this->wallList[$result].".Id"), $this->wall->getNested("WallBlocks.".$this->wallList[$result].".Meta"));
                for($i = 0; $i <= count($plots) - 1; $i++){
                    if($plots[$i][2] == self::PLOT){
                        $shape = $this->getPlotShape($world, $plots[$i]);
                        $this->getScheduler()->scheduleTask(new ChangePlotWall($p->getPosition(), $plots[$i], $block, [$shape[0] ^= 1, $shape[1] ^= 1, $shape[2] ^= 1, $shape[3] ^= 1], false));
                    }else{
                        if($plots[$i][2] == self::ROAD_1){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plots[$i][0], $plots[$i][1] - 1, self::CROSSING], $block, [false, true, false, false], false, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block, [false, false, false, true], false, true));
                            }
                        }
                        if($plots[$i][2] == self::ROAD_2){
                            if(!$this->isPlotIdSet($world, [$plots[$i][0], $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plots[$i][0], $plots[$i][1], self::CROSSING], $block, [false, false, true, false], false, true));
                            }
                            if(!$this->isPlotIdSet($world, [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING])){
                                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plots[$i][0] - 1, $plots[$i][1], self::CROSSING], $block, [true, false, false, false], false, true));
                            }
                        }
                    }
                }
                $p->sendMessage($this->getLogo()." ".$this->getMessage("ChangePlotWallMenu.1.1").$this->wallList[$result].$this->getMessage("ChangePlotWallMenu.1.2"));
            }

            return true;
        });
        $form->setTitle($this->getMessage("ChangePlotWallMenu.2"));
        $form->setContent($this->getMessage("ChangePlotWallMenu.3"));
        $form->addButton($this->getMessage("ChangePlotWallMenu.4"));

        $keys = array_keys($this->wall->get("WallBlocks"));
        $count = 1;
        for($r = 0; $r <= count($keys) - 1; $r++){
            if($this->wall->getNested("Permissions.".$this->getConfigRank($p)) >= $this->wall->getNested("WallBlocks.".$keys[$r].".Permission") or $this->getServer()->isOp($p->getName())){
                $form->addButton($keys[$r]."\nId: ".$this->wall->getNested("WallBlocks.".$keys[$r].".Id")." | Meta: ".$this->wall->getNested("WallBlocks.".$keys[$r].".Meta"));
                $this->wallList[$count] = $keys[$r];
                $count++;
            }
        }

        $form->sendToPlayer($p);
        return $form;
    }

    public function teleportPlayerToPlot(Player $p, string $world, array $plotId){
        $spawn = explode(",", $this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Spawn"));
        $x = (int) $spawn[0];
        $y = (int) $spawn[1];
        $z = (int) $spawn[2];
        $pos = new Position($x, $y, $z, $this->getServer()->getWorldManager()->getWorldByName($world));
        $p->teleport($pos);
    }

    public function getAllPlayerInPlot(string $world, array $plotId) : array{
        $playerInPlot = [];
        $counter = 1;
        $onlinePlayers = $this->getServer()->getOnlinePlayers();
        foreach($onlinePlayers as $player){
            if($player->getWorld()->getFolderName() == $world){
                $plots = $this->getAllMergePlots($world, $plotId, true);
                $playerPlot = $this->getPlotIdByPosition($player->getPosition()->getX(), $player->getPosition()->getZ());
                foreach($plots as $count => $plot){
                    if($plot == $playerPlot){
                        $playerInPlot[$counter] = $player->getName();
                        $counter++;
                    }
                }
            }
        }
        return $playerInPlot;
    }

    public function PlayerPlotListMenu(Player $p, string $world, string $player){
        $this->optionList = [$world];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];

            if($result !== 0){
                $this->teleportPlayerToPlot($p, $world, $this->plotList[$result]);
            }

            return true;
        });
        if($p->getName() == $player){
            $form->setTitle($this->getMessage("PlayerPlotListMenu.1"));
        }else{
            $form->setTitle($this->getMessage("PlayerPlotListMenu.2").$player);
        }
        $form->setContent($this->getMessage("PlayerPlotListMenu.3"));
        $form->addButton($this->getMessage("PlayerPlotListMenu.4"));

        foreach($this->getClaimedPlots($player, $world) as $count => $plot){
            $form->addButton($count.". Plot (".$plot[0].";".$plot[1].")");
            $this->plotList[$count] = $plot;
        }

        $form->sendToPlayer($p);
        return $form;
    }

    public function PlotMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result == 0){

                $this->PlotInfoMenu($p, $world, $plotId);

            }else{

                switch($this->plotMenu[$result]){
                    case "ChangeSpawn";
                        $spawn = $p->getPosition();
                        $x = round($spawn->getX(), 1);
                        $y = round($spawn->getY(), 1);
                        $z = round($spawn->getZ(), 1);
                        $merge = $this->getAllMergePlots($world, $plotId, false);
                        for($s = 0; $s <= count($merge) - 1; $s++){
                            $this->plot->setNested($world.".".$merge[$s][0].";".$merge[$s][1].";".$merge[$s][2].".Spawn", $x.",".$y.",".$z);
                            $this->plot->save();
                        }
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("17").$x.", ".$y.", ".$z);
                    break;

                    case "ChangeRand";
                        $this->ChangePlotRandMenu($p, $world, $plotId);
                    break;

                    case "ChangeWall";
                        $this->ChangePlotWallMenu($p, $world, $plotId);
                    break;

                    case "HelperMenu";
                        $this->HelperMenu($p, $world, $plotId);
                    break;

                    case "TrustedMenu";
                        $this->TrustedMenu($p, $world, $plotId);
                    break;

                    case "SetOwner";
                        $this->PlayerListMenu($p, $world, $plotId, "setowner");
                    break;
                }
            }

            return true;
        });
        $form->setTitle($this->getMessage("PlotMenu.1.1").$plotId[0].";".$plotId[1].$this->getMessage("PlotMenu.1.2"));
        $form->setContent($this->getMessage("PlotMenu.2"));

        $count = 1;

        $form->addButton($this->getMessage("PlotMenu.3"));

        if($this->getPlayerPermissions($world, $plotId, $p->getName(), 9) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".SetSpawn") == "true" or $this->getServer()->isOp($p->getName())) {
            $form->addButton($this->getMessage("PlotMenu.4"));
            $this->plotMenu[$count] = "ChangeSpawn";
            $count++;
        }

        //$form->addButton("Rechte Einstellen");

        if($this->getPlayerPermissions($world, $plotId, $p->getName(), 11) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Rand") == "true" or $this->getServer()->isOp($p->getName())){
            $form->addButton($this->getMessage("PlotMenu.5"));
            $this->plotMenu[$count] = "ChangeRand";
            $count++;
        }

        if($this->getPlayerPermissions($world, $plotId, $p->getName(), 10) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Wall") == "true" or $this->getServer()->isOp($p->getName())){
            $form->addButton($this->getMessage("PlotMenu.6"));
            $this->plotMenu[$count] = "ChangeWall";
            $count++;
        }

        if($this->getPlayerPermissions($world, $plotId, $p->getName(), 7) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Helper") == "true" or $this->getServer()->isOp($p->getName())){
            $form->addButton($this->getMessage("PlotMenu.7"));
            $this->plotMenu[$count] = "HelperMenu";
            $count++;
        }

        if($this->getPlayerPermissions($world, $plotId, $p->getName(), 8) or key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->permissions->getNested("Ranks.".$this->getConfigRank($p).".Trusted") == "true" or $this->getServer()->isOp($p->getName())){
            $form->addButton($this->getMessage("PlotMenu.8"));
            $this->plotMenu[$count] = "TrustedMenu";
            $count++;
        }

        if(key_exists($p->getName(), $this->getAllMergeOwner($world, $plotId)) or $this->getServer()->isOp($p->getName())){
            $form->addButton($this->getMessage("PlotMenu.9"));
            $this->plotMenu[$count] = "SetOwner";
        }

        $form->sendToPlayer($p);
        return $form;
    }

    public function HelperMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result == 0){
                $this->PlotMenu($p, $world, $plotId);
            }

            $split = str_split($this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Player.Helper"));
            if($result == 1){
                if($split[0] == 1){
                    $split[0] = 0;
                }else{
                    $split[0] = 1;
                }
                foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                    $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.Helper", implode("", $split));
                }
                $this->plot->save();
                $this->HelperMenu($p, $world, $plotId);
            }

            if($result == 2){
                $this->PlayerListMenu($p, $world, $plotId, "addhelper");
            }

            if($result !== 0 and $result !== 1 and $result !== 2){
                $this->RemovePlayerMenu($p, $world, $plotId, "Helfer", $this->playerList[$result - 2]);
            }

            return true;
        });
        $x = $plotId[0];
        $z = $plotId[1];
        $id = $plotId[2];

        $form->setTitle($this->getMessage("HelperMenu.1.1").$x.";".$z.$this->getMessage("HelperMenu.1.2"));
        $form->setContent($this->getMessage("HelperMenu.2"));
        $form->addButton($this->getMessage("HelperMenu.3"));
        if(str_split($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.Helper"))[0] == 1){
            $form->addButton($this->getMessage("HelperMenu.4")."\n".$this->getMessage("HelperMenu.5"));
        }else{
            $form->addButton($this->getMessage("HelperMenu.4")."\n".$this->getMessage("HelperMenu.6"));
        }
        $form->addButton($this->getMessage("HelperMenu.7"));

        $count = 1;
        foreach($this->plot->getAll()[$world][$x.";".$z.";".$id]["Player"] as $player => $permissions){
            if($player !== $p->getName() and $player !== "Helper" and $player !== "Trusted" and $player !== "Banned"){
                if(str_split($permissions)[0] == 1){
                    $form->addButton("[".$count."] ".$player);
                    $this->playerList[$count] = $player;
                    $count++;
                }
            }
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function TrustedMenu(Player $p, string $world, array $plotId){
        $this->optionList = [$world, $plotId];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];

            if($result == 0){
                $this->PlotMenu($p, $world, $plotId);
            }

            $split = str_split($this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Player.Trusted"));
            if($result == 1){
                if($split[0] == 1){
                    $split[0] = 0;
                    $playerInPlot = $this->getAllPlayerInPlot($world, $plotId);
                    foreach($playerInPlot as $count => $player){
                        if(!isset($this->getAllMergeOwner($world, $plotId)[$player])){
                            $player = $this->getServer()->getPlayerByPrefix($player);
                            $player->teleport($this->getServer()->getWorldManager()->getWorldByName($world)->getSpawnLocation());
                            $player->sendMessage($this->getLogo()." ".$this->getMessage("TrustedMenu.1"));
                        }
                    }
                }else{
                    $split[0] = 1;
                }
                foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                    $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.Trusted", implode("", $split));
                }
                $this->plot->save();
                $this->TrustedMenu($p, $world, $plotId);
            }

            if($result == 2){
                $this->PlayerListMenu($p, $world, $plotId, "trust");
            }

            if($result == 3){
                $this->PlayerListMenu($p, $world, $plotId, "ban");
            }

            if($result !== 0 and $result !== 1 and $result !== 2 and $result !== 3){
                $this->RemovePlayerMenu($p, $world, $plotId, "Trusted", $this->playerList[$result]);
            }

            return true;
        });
        $x = $plotId[0];
        $z = $plotId[1];
        $id = $plotId[2];

        $form->setTitle($this->getMessage("TrustedMenu.2.1").$plotId[0].";".$plotId[1].$this->getMessage("TrustedMenu.2.2"));
        $form->setContent($this->getMessage("TrustedMenu.3"));
        $form->addButton($this->getMessage("TrustedMenu.4"));
        if(str_split($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.Trusted"))[0] == 1){
            $form->addButton($this->getMessage("TrustedMenu.5")."\n".$this->getMessage("TrustedMenu.6"));
        }else{
            $form->addButton($this->getMessage("TrustedMenu.5")."\n".$this->getMessage("TrustedMenu.7"));
        }
        $form->addButton($this->getMessage("TrustedMenu.8"));
        $form->addButton($this->getMessage("TrustedMenu.9"));

        $count = 1;
        foreach($this->plot->getAll()[$world][$x.";".$z.";".$id]["Player"] as $player => $permissions){
            if($player !== "Helper" and $player !== "Trusted"){
                if(str_split($permissions)[1] == 1){
                    $form->addButton("[".$count."] ".$player."\n".$this->getMessage("TrustedMenu.10"));
                    $this->playerList[$count + 3] = $player;
                    $count++;
                }
                if(str_split($permissions)[2] == 1){
                    $form->addButton("[".$count."] ".$player."\n".$this->getMessage("TrustedMenu.11"));
                    $this->playerList[$count + 3] = $player;
                    $count++;
                }
            }
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function RemovePlayerMenu(Player $p, string $world, array $plotId, string $option, string $player){
        $this->optionList = [$world, $plotId, $option, $player];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];
            $option = $this->optionList[2];
            $player = $this->optionList[3];

            if($result == 0){
                foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                    $all = $this->plot->getAll()[$world][$plot[0].";".$plot[1].";".$plot[2]]["Player"];
                    unset($all[$player]);
                    $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player", $all);
                    $this->plot->save();
                }
                $p->sendMessage($this->getLogo()." ".$this->getMessage("RemovePlayerMenu.1.1").$player.$this->getMessage("RemovePlayerMenu.1.2"));
            }

            if($result == 1 and $option == "Helfer"){
                $this->EditHelperPermissionsMenu($p, $world, $plotId, $player);
            }

            if($result == 1 and $option == "Trusted"){
                $this->TrustedMenu($p, $world, $plotId);
            }

            if($result == 2 and $option == "Helfer"){
                $this->HelperMenu($p, $world, $plotId);
            }

            return true;
        });
        $form->setTitle($this->getMessage("RemovePlayerMenu.2.1").$plotId[0].";".$plotId[1].$this->getMessage("RemovePlayerMenu.2.2"));
        $form->setContent($this->getMessage("RemovePlayerMenu.3.1").$player.$this->getMessage("RemovePlayerMenu.3.2"));
        $form->addButton($this->getMessage("RemovePlayerMenu.4"));
        if($option == "Helfer"){
            $form->addButton($this->getMessage("RemovePlayerMenu.5"));
        }
        $form->addButton($this->getMessage("RemovePlayerMenu.6"));
        $form->sendToPlayer($p);
        return $form;
    }

    public function EditHelperPermissionsMenu(Player $p, string $world, array $plotId, string $player){
        $this->playerList = [];
        $this->optionList = [$world, $plotId, $player];
        $form = new CustomForm(function (Player $p, array $data = null){
            if($data === null){
                return true;
            }

            $world = $this->optionList[0];
            $plotId = $this->optionList[1];
            $x = $plotId[0];
            $z = $plotId[1];
            $id = $plotId[2];
            $player = $this->optionList[2];
            $split = str_split($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.".$player));

            for($i = 1; $i <= count($data) - 1; $i++){
                if($data[$i] == 1){
                    $split[$i + 2] = 1;
                }else{
                    $split[$i + 2] = 0;
                }
            }

            foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.".$player, implode("", $split));
                $this->plot->save();
            }

            return true;
        });
        $form->setTitle($this->getMessage("EditHelperPermissionsMenu.1.1").$plotId[0].";".$plotId[1].$this->getMessage("EditHelperPermissionsMenu.1.2"));
        $form->addLabel($this->getMessage("EditHelperPermissionsMenu.2.1").$player.$this->getMessage("EditHelperPermissionsMenu.2.2"));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.3"), $this->getPlayerPermissions($world, $plotId, $player, 3));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.4"), $this->getPlayerPermissions($world, $plotId, $player, 4));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.5"), $this->getPlayerPermissions($world, $plotId, $player, 5));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.6"), $this->getPlayerPermissions($world, $plotId, $player, 6));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.7"), $this->getPlayerPermissions($world, $plotId, $player, 7));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.8"), $this->getPlayerPermissions($world, $plotId, $player, 8));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.9"), $this->getPlayerPermissions($world, $plotId, $player, 9));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.10"), $this->getPlayerPermissions($world, $plotId, $player, 10));
        $form->addToggle($this->getMessage("EditHelperPermissionsMenu.11"), $this->getPlayerPermissions($world, $plotId, $player, 11));

        $form->sendToPlayer($p);
        return $form;
    }

    public function getPlayerPermissions(string $world, array $plotId, string $player, int $permission) : bool{
        if(key_exists($player, $this->plot->getAll()[$world][$plotId[0].";".$plotId[1].";".$plotId[2]]["Player"])){
            if(str_split($this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Player.".$player))[$permission] == 1){
                return true;
            }
        }
        return false;
    }

    public function PlayerListMenu(Player $p, string $world, array $plotId, string $option, mixed $zusatz = 0){
        $this->playerList = [];
        $this->optionList = [$world, $plotId, $option, $zusatz];
        $form = new SimpleForm(function (Player $p, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            $world = $this->optionList[0];
            $plotId = $this->optionList[1];
            $x = $plotId[0];
            $z = $plotId[1];
            $id = $plotId[2];
            $option = $this->optionList[2];
            $zusatz = $this->optionList[3];

            if($result == 0){
                if($option == "addhelper"){
                    $this->HelperMenu($p, $world, $plotId);
                }

                if($option == "trust" or $option == "ban"){
                    $this->TrustedMenu($p, $world, $plotId);
                }
            }

            if($result !== 0){
                if($option == "list"){
                    if(count($this->getClaimedPlots($this->playerList[$result], $world)) > 0){
                        $this->PlayerPlotListMenu($p, $world, $this->playerList[$result]);
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("5.1").$this->playerList[$result].$this->getMessage("5.2"));
                    }
                }

                if($option == "home"){
                    $homePlot = $this->getClaimedPlots($this->playerList[$result], $world);
                    if(count($homePlot) > 0){
                        if(count($homePlot) >= $zusatz){
                            $this->teleportPlayerToPlot($p, $world, $homePlot[$zusatz]);
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.1.1").$zusatz.$this->getMessage("PlayerListMenu.1.2").$this->playerList[$result].$this->getMessage("PlayerListMenu.1.3"));
                        }else{
                            $p->sendMessage($this->getLogo()." ".$this->getMessage("7.1").$this->playerList[$result].$this->getMessage("7.2").$zusatz.$this->getMessage("7.3"));
                        }
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("5.1").$this->playerList[$result].$this->getMessage("5.2"));
                    }
                }

                if($option == "setowner"){
                    $this->plot->setNested($world.".".$x.";".$z.";".$id.".Owner", $this->playerList[$result]);
                    $this->plot->save();
                }

                if($option == "addhelper"){
                    if(!is_string($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.".$this->playerList[$result])) or !$this->getPlayerPermissions($world, $plotId, $this->playerList[$result], 0)){
                        foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                            $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.".$this->playerList[$result], "100111000100");
                            $this->plot->save();
                        }
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.2.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.2.2"));
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.3.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.3.2"));
                    }
                }

                if($option == "trust"){
                    if(!is_string($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.".$this->playerList[$result])) or !$this->getPlayerPermissions($world, $plotId, $this->playerList[$result], 1)){
                        foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                            $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.".$this->playerList[$result], "010000000000");
                            $this->plot->save();
                        }
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.4.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.4.2"));
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.5.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.5.2"));
                    }
                }

                if($option == "ban"){
                    if(!is_string($this->plot->getNested($world.".".$x.";".$z.";".$id.".Player.".$this->playerList[$result])) or !$this->getPlayerPermissions($world, $plotId, $this->playerList[$result], 2)){
                        foreach($this->getAllMergePlots($world, $plotId, true) as $count => $plot){
                            $this->plot->setNested($world.".".$plot[0].";".$plot[1].";".$plot[2].".Player.".$this->playerList[$result], "001000000000");
                            $this->plot->save();
                        }
                        $player = $this->getServer()->getPlayerByPrefix($this->playerList[$result]);
                        if($player instanceof Player){
                            $player->teleport($this->getServer()->getWorldManager()->getWorldByName($world)->getSpawnLocation());
                            $player->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.6"));
                        }
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.7.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.7.2"));
                    }else{
                        $p->sendMessage($this->getLogo()." ".$this->getMessage("PlayerListMenu.8.1").$this->playerList[$result].$this->getMessage("PlayerListMenu.8.2"));
                    }
                }
            }

        return true;
        });
        $form->setTitle("§aSpieler Liste");
        if($option == "list"){
            $form->setContent($this->getMessage("PlayerListMenu.9"));
        }
        if($option == "home"){
            $form->setContent($this->getMessage("PlayerListMenu.10.1").$zusatz.$this->getMessage("PlayerListMenu.10.2"));
        }
        if($option == "setowner"){
            $form->setContent($this->getMessage("PlayerListMenu.11"));
        }
        if($option == "addhelper"){
            $form->setContent($this->getMessage("PlayerListMenu.12"));
        }
        if($option == "trust"){
            $form->setContent($this->getMessage("PlayerListMenu.13"));
        }
        if($option == "ban"){
            $form->setContent($this->getMessage("PlayerListMenu.14"));
        }
        $form->addButton("§cZurück");
        $counter = 1;
        foreach($this->getAllPlayer() as $player => $count){
            if($p->getName() !== $player){
                if($option == "addhelper" or $option == "trust" or $option == "ban"){
                    if(!isset($this->getAllMergeOwner($world, $plotId)[$player])){
                        $form->addButton("[".$counter."] ".$player);
                        $this->playerList[$counter] = $player;
                        $counter++;
                    }
                }else{
                    $form->addButton("[".$counter."] ".$player);
                    $this->playerList[$counter] = $player;
                    $counter++;
                }
            }
        }
        $form->sendToPlayer($p);
        return $form;
    }

    public function getAllMergeOwner(string $world, array $plotId) : array{
        $player = [];
        foreach($this->getAllMergePlots($world, $plotId, false) as $count => $merge){
            $key = $this->plot->getNested($world.".".$merge[0].";".$merge[1].";".$merge[2].".Owner");
            if(!isset($player[$key])){
                $player[$key] = $count;
            }
        }
        return $player;
    }

    public function registerMerge(Player $p, array $plotId, bool $remove){
        $blockClaim1 = InternalBlockFactory::get(Options::ROAD_CLAIM_RAND_BLOCK);
        $blockClaim2 = InternalBlockFactory::get(Options::ROAD_CLAIM_UNDER_RAND_BLOCK);

        $blockAir = VanillaBlocks::AIR();
        $blockRoad = InternalBlockFactory::get(Options::ROAD_ROAD_BLOCK);

        $blockWall = InternalBlockFactory::get(Options::ROAD_WALL_BLOCK);
        $blockFill = InternalBlockFactory::get(Options::PLOT_FILL_BLOCK);

        $world = $p->getWorld()->getFolderName();

        $this->getScheduler()->scheduleTask(new SetRoad($p->getPosition(), $plotId, $remove));
        if($plotId[2] == self::ROAD_1){
            if($remove){
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), $plotId, $blockClaim1, $blockClaim2, [false, false, false, true], false, true, true, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plotId[0], $plotId[1] - 1, $plotId[2]], $blockClaim1, $blockClaim2, [false, true, false, false], false, true, true, false));

                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), $plotId, $blockWall, [false, false, false, true], false, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plotId[0], $plotId[1] - 1, $plotId[2]], $blockWall, [false, true, false, false], false, false));

                $this->setMerge($world, [$plotId[0], $plotId[1], self::PLOT], [$plotId[0] + 1, $plotId[1], self::PLOT]);
            }else{
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), $plotId, $blockAir, $blockRoad, [false, false, false, true], false, true, true, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plotId[0], $plotId[1] - 1, $plotId[2]], $blockAir, $blockRoad, [false, true, false, false], false, true, true, false));

                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), $plotId, $blockFill, [false, false, false, true], false, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plotId[0], $plotId[1] - 1, $plotId[2]], $blockFill, [false, true, false, false], false, false));
            }
        }
        if($plotId[2] == self::ROAD_2){
            if($remove){
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), $plotId, $blockClaim1, $blockClaim2, [false, false, true, false], false, true, true, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plotId[0] - 1, $plotId[1], $plotId[2]], $blockClaim1, $blockClaim2, [true, false, false, false], false, true, true, false));

                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), $plotId, $blockWall, [false, false, true, false], false, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plotId[0] - 1, $plotId[1], $plotId[2]], $blockWall, [true, false, false, false], false, false));

                $this->setMerge($world, [$plotId[0], $plotId[1], self::PLOT], [$plotId[0], $plotId[1] + 1, self::PLOT]);
            }else{
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), $plotId, $blockAir, $blockRoad, [false, false, true, false], false, true, true, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingRand($p->getPosition(), [$plotId[0] - 1, $plotId[1], $plotId[2]], $blockAir, $blockRoad, [true, false, false, false], false, true, true, false));

                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), $plotId, $blockFill, [false, false, true, false], false, false));
                $this->getScheduler()->scheduleTask(new ChangeCrossingWall($p->getPosition(), [$plotId[0] - 1, $plotId[1], $plotId[2]], $blockFill, [true, false, false, false], false, false));
            }
        }
        $crossingId = $this->getCrossingMerge($world, $plotId);
        for($c = 1; $c <= count($crossingId); $c++){
            $x = $crossingId[$c][0];
            $z = $crossingId[$c][1];
            $stringId = $x.";".$z.";".$crossingId[$c][2];
            foreach($this->plot->getAll()[$world][$x.";".$z.";0"]["Player"] as $player => $options){
                $this->plot->setNested($world.".".$stringId.".Player.".$player, $options);
                $this->plot->save();
            }
            $this->plot->save();
            $this->getScheduler()->scheduleDelayedTask(new SetRoad($p->getPosition(), $crossingId[$c], $remove), 2 * 20);
            if(!$remove){
                $side = $this->getPlotShape($world, $crossingId[$c]);
                $this->getScheduler()->scheduleDelayedTask(new ChangeCrossingRand($p->getPosition(), $crossingId[$c], $blockClaim1, $blockClaim2, [$side[0], $side[1], $side[2], $side[3]], false, true, true, true), 4 * 20);
                $this->getScheduler()->scheduleDelayedTask(new ChangeCrossingWall($p->getPosition(), $crossingId[$c], $blockWall, [$side[0], $side[1], $side[2], $side[3]], true, true), 4 * 20);
            }
        }
    }

    public function getCrossingMerge(string $world, array $plotId) : array{
        $x = $plotId[0];
        $z = $plotId[1];
        $merge = [];
        $count = 1;

        if($plotId[2] == self::ROAD_1){
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_2]) and $this->isPlotIdSet($world, [$x, ($z + 1), self::ROAD_1]) and $this->isPlotIdSet($world, [($x + 1), $z, self::ROAD_2])){
                $merge[$count] = [$x, $z, self::CROSSING];
                $count++;
            }
            if($this->isPlotIdSet($world, [$x, ($z - 1), self::ROAD_2]) and $this->isPlotIdSet($world, [$x, ($z - 1), self::ROAD_1]) and $this->isPlotIdSet($world, [($x + 1), ($z - 1), self::ROAD_2])){
                $merge[$count] = [$x, ($z - 1), self::CROSSING];
                $count++;
            }
        }

        if($plotId[2] == self::ROAD_2){
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_1]) and $this->isPlotIdSet($world, [($x + 1), $z, self::ROAD_2]) and $this->isPlotIdSet($world, [$x, ($z + 1), self::ROAD_1])){
                $merge[$count] = [$x, $z, self::CROSSING];
                $count++;
            }
            if($this->isPlotIdSet($world, [($x - 1), $z, self::ROAD_1]) and $this->isPlotIdSet($world, [($x - 1), $z, self::ROAD_2]) and $this->isPlotIdSet($world, [($x - 1), ($z + 1), self::ROAD_1])){
                $merge[$count] = [($x - 1), $z, self::CROSSING];
            }
        }
        return $merge;
    }

    public function isPlotIdSet(string $world, array $plotId) : bool{
        return is_string($this->plot->getNested($world.".".$plotId[0].";".$plotId[1].";".$plotId[2].".Player.Helper"));
    }

    public function setMerge(string $world, array $plotId1, array $plotId2){
        foreach($this->getAllMergePlots($world, $plotId1, true) as $count => $plots1){
            foreach($this->plot->getAll()[$world][$plotId1[0].";".$plotId1[1].";".$plotId1[2]]["Player"] as $player => $options){
                $this->plot->setNested($world.".".$plots1[0].";".$plots1[1].";".$plots1[2].".Player.".$player, $options);
            }
            foreach($this->plot->getAll()[$world][$plotId2[0].";".$plotId2[1].";".$plotId2[2]]["Player"] as $player => $options){
                $this->plot->setNested($world.".".$plots1[0].";".$plots1[1].";".$plots1[2].".Player.".$player, $options);
            }
            $this->plot->save();
        }
    }

    public function is_in_array(mixed $key, array $array) : bool{
        $keys = array_keys($array);
        for($i = 0; $i <= count($keys) - 1; $i++){
            if($array[$keys[$i]] == $key){
                return true;
            }
        }
        return false;
    }

    public function getAllMergePlots(string $world, array $plotId, bool $allPlots) : array{
        $count = 2;
        $plot = [];
        $plots = [];
        $plot[1] = [$plotId[0], $plotId[1], self::PLOT];

        for($i = 1; count($plot) !== 0; $i++){
            $shape = $this->getPlotShape($world, $plot[$i]);
            if($shape[0]){
                if(!$this->is_in_array([$plot[$i][0] + 1, $plot[$i][1], self::PLOT], $plots)){
                    $plot[$count] = [$plot[$i][0] + 1, $plot[$i][1], self::PLOT];
                    $count++;
                    if($allPlots and !$this->is_in_array([$plot[$i][0] + 1, $plot[$i][1], self::ROAD_1], $plots)){
                        $plots[] = [$plot[$i][0], $plot[$i][1], self::ROAD_1];
                    }
                }
            }
            if($shape[1]){
                if(!$this->is_in_array([$plot[$i][0], $plot[$i][1] + 1, self::PLOT], $plots)){
                    $plot[$count] = [$plot[$i][0], $plot[$i][1] + 1, self::PLOT];
                    $count++;
                    if($allPlots and !$this->is_in_array([$plot[$i][0], $plot[$i][1] + 1, self::ROAD_2], $plots)){
                        $plots[] = [$plot[$i][0], $plot[$i][1], self::ROAD_2];
                    }
                }
            }
            if($shape[2]){
                if(!$this->is_in_array([$plot[$i][0] - 1, $plot[$i][1], self::PLOT], $plots)){
                    $plot[$count] = [$plot[$i][0] - 1, $plot[$i][1], self::PLOT];
                    $count++;
                    if($allPlots and !$this->is_in_array([$plot[$i][0] - 1, $plot[$i][1], self::ROAD_1], $plots)){
                        $plots[] = [$plot[$i][0] - 1, $plot[$i][1], self::ROAD_1];
                    }
                }
            }
            if($shape[3]){
                if(!$this->is_in_array([$plot[$i][0], $plot[$i][1] - 1, self::PLOT], $plots)){
                    $plot[$count] = [$plot[$i][0], $plot[$i][1] - 1, self::PLOT];
                    $count++;
                    if($allPlots and !$this->is_in_array([$plot[$i][0], $plot[$i][1] - 1, self::ROAD_2], $plots)){
                        $plots[] = [$plot[$i][0], $plot[$i][1] - 1, self::ROAD_2];
                    }
                }
            }
            if(!$this->is_in_array($plot[$i], $plots)){
                $plots[] = $plot[$i];
            }
            unset($plot[$i]);
        }

        if($allPlots){
            for($c = 0; $c <= count($plots) - 1; $c++){
                if($plots[$c][2] == self::ROAD_1 or $plots[$c][2] == self::ROAD_2){
                    foreach($this->getCrossingMerge($world, $plots[$c]) as $count => $crossingId){
                        if(!$this->is_in_array($crossingId, $plots)){
                            $plots[] = $crossingId;
                        }
                    }
                }
            }
        }

        return $plots;
    }

    public function getPlotShape(string $world, array $plotId) : array{
        $shape = [0 => false, 1 => false, 2 => false, 3 => false];
        $x = $plotId[0];
        $z = $plotId[1];
        $id = $plotId[2];

        if($id == self::PLOT){
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_1])){
                $shape[0] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_2])){
                $shape[1] = true;
            }
            if($this->isPlotIdSet($world, [$x - 1, $z, self::ROAD_1])){
                $shape[2] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z - 1, self::ROAD_2])){
                $shape[3] = true;
            }
        }

        if($id == self::ROAD_1){
            if($this->isPlotIdSet($world, [$x + 1, $z, self::PLOT]) == true){
                $shape[0] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::CROSSING]) == true){
                $shape[1] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::PLOT]) == true){
                $shape[2] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z - 1, self::CROSSING]) == true){
                $shape[3] = true;
            }
        }

        if($id == self::ROAD_2){
            if($this->isPlotIdSet($world, [$x, $z, self::CROSSING]) == true){
                $shape[0] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z + 1, self::PLOT]) == true){
                $shape[1] = true;
            }
            if($this->isPlotIdSet($world, [$x - 1, $z, self::CROSSING]) == true){
                $shape[2] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::PLOT]) == true){
                $shape[3] = true;
            }
        }

        if($id == self::CROSSING){
            if($this->isPlotIdSet($world, [$x + 1, $z, self::ROAD_2]) == true){
                $shape[0] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z + 1, self::ROAD_1]) == true){
                $shape[1] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_2]) == true){
                $shape[2] = true;
            }
            if($this->isPlotIdSet($world, [$x, $z, self::ROAD_1]) == true){
                $shape[3] = true;
            }
        }

        return $shape;
    }

    public function removePlotId(string $world, array $plotId){
        $all = $this->plot->get($world);
        unset($all[$plotId[0].";".$plotId[1].";".$plotId[2]]);
        $this->plot->set($world, $all);
        $this->plot->save();
    }

    public function getConfigRank(Player $p) : string{
        if($this->permissions->getNested("Options.ConnectToPlugin.PurePerms") == "true"){
            $pp = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
            $rang = $pp->getUserDataMgr()->getGroup($p)->getName();
            if(key_exists($rang, $this->permissions->getAll("Rangs"))){
                return $rang;
            }
        }
        return "Default";
    }

    public function getClaimedPlots(string $playerName, string $world) : array{
        $count = 1;
        $plots = [];
        $keys = array_keys($this->plot->get($world));
        for($i = 0; $i <= count($keys) - 1; $i++){
            if($playerName == $this->plot->getNested($world.".".$keys[$i].".Owner")){
                $ex = explode(";", $keys[$i]);
                $plots[$count] = [$ex[0], $ex[1], $ex[2]];
                $count++;
            }
        }
        return $plots;
    }

    public function getPlot(float $world) : int{
        return (int) floor($world / $this->totalSize);
    }

    public function getPlotPos(int $world) : int{
        if($world >= 0){
            $pos = ($world % $this->totalSize) + 1;
        }else{
            $pos = $this->totalSize - (int) abs($world % $this->totalSize);
        }
        if($pos == $this->totalSize + 1){
            $pos = 1;
        }
        return $pos;
    }

    public function getShapeByPosition(float $worldX, float $worldZ) : int{
        $X = $this->getPlotPos((int) $worldX);
        $Z = $this->getPlotPos((int) $worldZ);

        if($X <= $this->plotSize and $Z <= $this->plotSize){
            $type = self::PLOT;
        }elseif($X > $this->plotSize and $Z <= $this->plotSize and $X <= $this->totalSize){
            $type = self::ROAD_1; //green
        }elseif($X <= $this->plotSize and $Z > $this->plotSize and $Z <= $this->totalSize){
            $type = self::ROAD_2; //yellow
        }elseif($X > $this->plotSize and $Z > $this->plotSize){
            $type = self::CROSSING; //blue
        }else{
            $type = self::NULL;
        }
        return $type;
    }

    public function getPlotIdByPosition(float $worldX, float $worldZ) : array{
        return [$this->getPlot($worldX), $this->getPlot($worldZ), $this->getShapeByPosition($worldX, $worldZ)];
    }
}