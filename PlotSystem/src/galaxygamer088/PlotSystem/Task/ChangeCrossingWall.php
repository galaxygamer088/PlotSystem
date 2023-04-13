<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class ChangeCrossingWall extends Task{

public Position $position;
public array $plotId, $side;
public Block $block;
public bool $replace, $corner;
const NORTH = 0;
const EAST = 1;
const SOUTH = 2;
const WEST = 3;

    public function __construct(Position $position, array $plotId, Block $block, array $side, bool $replace, bool $corner){
        $this->position = $position;
        $this->plotId = $plotId;
        $this->block = $block;
        $this->side = $side;
        $this->replace = $replace;
        $this->corner = $corner;
    }

    public function onRun() : void{
        if($this->corner){
            $c = 1;
        }else{
            $c = 0;
        }
        for($xr = 0; $xr <= Options::ROAD_WIDTH; $xr++){
            for($zr = 0; $zr <= Options::ROAD_WIDTH; $zr++){
                $x = $xr + Options::PLOT_SIZE;
                $z = $zr + Options::PLOT_SIZE;
                $X = $this->plotId[0] * Options::TOTAL_SIZE + $x;
                $Z = $this->plotId[1] * Options::TOTAL_SIZE + $z;
                if($z > Options::PLOT_SIZE - $c and $z < Options::TOTAL_SIZE - 1 + $c and $x == Options::TOTAL_SIZE - 1){
                    $this->setBlock($X, $Z, self::NORTH);
                }
                if($x > Options::PLOT_SIZE - $c and $x < Options::TOTAL_SIZE - 1 + $c and $z == Options::TOTAL_SIZE - 1){
                    $this->setBlock($X, $Z, self::EAST);
                }
                if($z > Options::PLOT_SIZE - $c  and $z < Options::TOTAL_SIZE - 1 + $c and $x == Options::PLOT_SIZE){
                    $this->setBlock($X, $Z, self::SOUTH);
                }
                if($x > Options::PLOT_SIZE - $c and $x < Options::TOTAL_SIZE - 1 + $c and $z == Options::PLOT_SIZE){
                    $this->setBlock($X, $Z, self::WEST);
                }
            }
        }
    }

    public function setBlock(int $x, int $z, int $side){
        for($y = 1; $y <= Options::GROUND_HEIGHT - 1; $y++){
            if($this->replace){
                $this->position->getWorld()->setBlockAt($x, $y, $z, $this->getBlockBySide($side));
            }else{
                if($this->side[$side]){
                    $this->position->getWorld()->setBlockAt($x, $y, $z, $this->block);
                }
            }
        }
    }

    public function getBlockBySide(int $side) : Block{
        if($this->side[$side]){
            return $this->block;
        }else{
            return BlockFactory::getInstance()->get(Options::ROAD_WALL_BLOCK_ID, Options::ROAD_WALL_BLOCK_META);
        }
    }
}