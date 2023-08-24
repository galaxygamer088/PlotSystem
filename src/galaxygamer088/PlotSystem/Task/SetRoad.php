<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class SetRoad extends Task{

public Position $position;
public array $plotId;
public bool $remove;
public int $block;
const NULL = -1;
const ROAD_1 = 1; //green
const ROAD_2 = 2; //yellow
const WALL = 3;
const CROSSING = 4;

    public function __construct(Position $position, array $plotId, bool $remove){
        $this->position = $position;
        $this->plotId = $plotId;
        $this->remove = $remove;
        $this->block = 0;
    }

    public function onRun() : void{
        for($x = 0; $x <= Options::TOTAL_SIZE - 1; $x++){
            for($z = 0; $z <= Options::TOTAL_SIZE - 1; $z++){
                $X = $this->plotId[0] * Options::TOTAL_SIZE + $x;
                $Z = $this->plotId[1] * Options::TOTAL_SIZE + $z;

                $world = $this->position->getWorld();
                $type = $this->getRoadShapeByPosition($X, $Z);
                $roadBlock = Options::getBlocks()["ROAD_BLOCK"];

                if($this->plotId[2] == self::ROAD_1 and $type == self::ROAD_1){
                    $this->setBasicBlock($X, $Z);
                     if(!$this->remove){
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, $roadBlock);
                    }
                }elseif($this->plotId[2] == self::ROAD_2 and $type == self::ROAD_2){
                    $this->setBasicBlock($X, $Z);
                    if(!$this->remove){
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, $roadBlock);
                    }
                }elseif($this->plotId[2] == self::CROSSING and $type == self::CROSSING){
                    $this->setBasicBlock($X, $Z);
                    if(!$this->remove){
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, $roadBlock);
                    }
                }elseif($type == self::WALL){
                    if($this->remove){
                        $world->setBlockAt($X, Options::GROUND_HEIGHT + 1, $Z, VanillaBlocks::AIR());
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, Options::getBlocks()["PLOT_BLOCK"]);
                        for($y = 1; $y < Options::GROUND_HEIGHT; $y++){
                            $world->setBlockAt($X, $y, $Z, Options::getBlocks()["FILL_BLOCK"]);
                        }
                    }else{
                        $world->setBlockAt($X, Options::GROUND_HEIGHT + 1, $Z, Options::getBlocks()["CLAIM_RAND_BLOCK"]);
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, Options::getBlocks()["CLAIM_UNDER_RAND_BLOCK"]);
                        for($y = 1; $y < Options::GROUND_HEIGHT; $y++){
                            $world->setBlockAt($X, $y, $Z, Options::getBlocks()["WALL_BLOCK"]);
                        }
                    }
                }
            }
        }
    }

    public function setBasicBlock(int $x, int $z){
        $world = $this->position->getWorld();
        for($y = 0; $y <= 255; $y++){
            if($y == 0){
                $bottomBlock = Options::getBlocks()["BOTTOM_BLOCK"];;
                if($world->getBlock(new Vector3($x, $y, $z))->getTypeId() !== $bottomBlock->getTypeId()){
                    $world->setBlockAt($x, $y, $z, $bottomBlock);
                }
            }elseif($y == Options::GROUND_HEIGHT){
                $floorBlock = Options::getBlocks()["PLOT_BLOCK"];;
                if($world->getBlock(new Vector3($x, $y, $z))->getTypeId() !== $floorBlock->getTypeId()){
                    $world->setBlockAt($x, $y, $z, $floorBlock);
                }
            }elseif($y < Options::GROUND_HEIGHT){
                $fillBlock = Options::getBlocks()["FILL_BLOCK"];;
                if($world->getBlock(new Vector3($x, $y, $z))->getTypeId() !== $fillBlock->getTypeId()){
                    $world->setBlockAt($x, $y, $z, $fillBlock);
                }
            }else{
                $airBlock = VanillaBlocks::AIR();
                if($world->getBlock(new Vector3($x, $y, $z))->getTypeId() !== $airBlock->getTypeId()){
                    $world->setBlockAt($x, $y, $z, $airBlock);
                }
            }
        }
    }

    public function getPlotPos(int $worldX) : int{
        if($worldX >= 0){
            $pos = ($worldX % Options::TOTAL_SIZE) + 1;
        }else{
            $pos = Options::TOTAL_SIZE - abs($worldX % Options::TOTAL_SIZE) + 1;
        }
        if($pos == Options::TOTAL_SIZE + 1){
            $pos = 1;
        }
        return $pos;
    }

    public function getRoadShapeByPosition(int $worldX, int $worldZ) : int{
        $X = $this->getPlotPos($worldX);
        $Z = $this->getPlotPos($worldZ);

        if($X > Options::PLOT_SIZE and $Z <= Options::PLOT_SIZE and $X <= Options::TOTAL_SIZE){
            $type = self::ROAD_1; //green
        }elseif($X <= Options::PLOT_SIZE and $Z > Options::PLOT_SIZE and $Z <= Options::TOTAL_SIZE){
            $type = self::ROAD_2; //yellow
        }elseif($X > Options::PLOT_SIZE and $Z > Options::PLOT_SIZE){
            $type = self::CROSSING; //blue
        }else{
            $type = self::NULL;
        }
        if(!$this->remove){
            if($this->plotId[2] == self::ROAD_1){
                if($X == Options::PLOT_SIZE + 1 and $Z <= Options::PLOT_SIZE or $X == Options::TOTAL_SIZE and $Z <= Options::PLOT_SIZE){
                    $type = self::WALL;
                }
            }
            if($this->plotId[2] == self::ROAD_2){
                if($Z == Options::PLOT_SIZE + 1 and $X <= Options::PLOT_SIZE or $Z == Options::TOTAL_SIZE and $X <= Options::PLOT_SIZE){
                    $type = self::WALL;
                }
            }
        }
        return $type;
    }
}