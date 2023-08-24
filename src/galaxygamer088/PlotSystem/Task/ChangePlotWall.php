<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\Block;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class ChangePlotWall extends Task{

public Position $position;
public array $plotId, $side;
public Block $block;
public bool $replace;
const NORTH = 0;
const EAST = 1;
const SOUTH = 2;
const WEST = 3;

    public function __construct(Position $position, array $plotId, Block $block, array $side, bool $replace){
        $this->position = $position;
        $this->plotId = $plotId;
        $this->block = $block;
        $this->side = $side;
        $this->replace = $replace;
    }

    public function onRun() : void{
        for($x = -1; $x <= Options::PLOT_SIZE; $x++){
            for($z = -1; $z <= Options::PLOT_SIZE; $z++){
                $X = $this->plotId[0] * Options::TOTAL_SIZE + $x;
                $Z = $this->plotId[1] * Options::TOTAL_SIZE + $z;
                if($x == Options::PLOT_SIZE){
                    $this->setBlock($X, $Z, self::NORTH);
                }
                if($z == Options::PLOT_SIZE){
                    $this->setBlock($X, $Z, self::EAST);
                }
                if($x == -1){
                    $this->setBlock($X, $Z, self::SOUTH);
                }
                if($z == -1){
                    $this->setBlock($X, $Z, self::WEST);
                }
            }
        }
    }

    public function setBlock(int $x, int $z, int $side){
        for($y = 1; $y <= Options::GROUND_HEIGHT - 1; $y++){
            if($this->replace == true){
                $this->position->getWorld()->setBlockAt($x, $y, $z, $this->getBlockBySide($side));
            }else{
                if($this->side[$side] == true){
                    $this->position->getWorld()->setBlockAt($x, $y, $z, $this->block);
                }
            }
        }
    }

    public function getBlockBySide(int $side) : Block{
        if($this->side[$side] == true){
            return $this->block;
        }else{
            return Options::getBlocks()["WALL_BLOCK"];
        }
    }
}