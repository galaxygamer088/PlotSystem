<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\BlockFactory;
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
                $roadBlock = BlockFactory::getInstance()->get(Options::ROAD_ROAD_BLOCK_ID, Options::ROAD_ROAD_BLOCK_META);

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
                        $world->setBlockAt($X, Options::GROUND_HEIGHT + 1, $Z, BlockFactory::getInstance()->get(0, 0));
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, BlockFactory::getInstance()->get(Options::PLOT_FLOOR_BLOCK_ID, Options::PLOT_FLOOR_BLOCK_META));
                        for($y = 1; $y < Options::GROUND_HEIGHT; $y++){
                            $world->setBlockAt($X, $y, $Z, BlockFactory::getInstance()->get(Options::PLOT_FILL_BLOCK_ID, Options::PLOT_FILL_BLOCK_META));
                        }
                    }else{
                        $world->setBlockAt($X, Options::GROUND_HEIGHT + 1, $Z, BlockFactory::getInstance()->get(Options::ROAD_CLAIM_RAND_BLOCK_ID, Options::ROAD_CLAIM_RAND_BLOCK_META));
                        $world->setBlockAt($X, Options::GROUND_HEIGHT, $Z, BlockFactory::getInstance()->get(Options::ROAD_CLAIM_UNDER_RAND_BLOCK_ID, Options::ROAD_CLAIM_UNDER_RAND_BLOCK_META));
                        for($y = 1; $y < Options::GROUND_HEIGHT; $y++){
                            $world->setBlockAt($X, $y, $Z, BlockFactory::getInstance()->get(Options::ROAD_WALL_BLOCK_ID, Options::ROAD_WALL_BLOCK_META));
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
                $bottomBlock = BlockFactory::getInstance()->get(Options::PLOT_BOTTOM_BLOCK_ID, Options::PLOT_BOTTOM_BLOCK_META);
                if($world->getBlock(new Vector3($x, $y, $z))->getFullId() !== $bottomBlock->getFullId()){
                    $world->setBlockAt($x, $y, $z, $bottomBlock);
                }
            }elseif($y == Options::GROUND_HEIGHT){
                $floorBlock = BlockFactory::getInstance()->get(Options::PLOT_FLOOR_BLOCK_ID, Options::PLOT_FLOOR_BLOCK_META);
                if($world->getBlock(new Vector3($x, $y, $z))->getFullId() !== $floorBlock->getFullId()){
                    $world->setBlockAt($x, $y, $z, $floorBlock);
                }
            }elseif($y < Options::GROUND_HEIGHT){
                $fillBlock = BlockFactory::getInstance()->get(Options::PLOT_FILL_BLOCK_ID, Options::PLOT_FILL_BLOCK_META);
                if($world->getBlock(new Vector3($x, $y, $z))->getFullId() !== $fillBlock->getFullId()){
                    $world->setBlockAt($x, $y, $z, $fillBlock);
                }
            }else{
                $airBlock = BlockFactory::getInstance()->get(0, 0);
                if($world->getBlock(new Vector3($x, $y, $z))->getFullId() !== $airBlock->getFullId()){
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