<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\InternalBlockFactory;
use galaxygamer088\PlotSystem\Options;
use pocketmine\block\Block;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class ChangePlotRand extends Task{

public Position $position;
public array $plotId, $side;
public Block $block1, $block2;
public bool $replace, $rand, $underRand;
const NORTH = 0;
const EAST = 1;
const SOUTH = 2;
const WEST = 3;

    public function __construct(Position $position, array $plotId, Block $block1, Block $block2, array $side, bool $replace, bool $rand, bool $underRand){
        $this->position = $position;
        $this->plotId = $plotId;
        $this->block1 = $block1;
        $this->block2 = $block2;
        $this->side = $side;
        $this->replace = $replace;
        $this->rand = $rand;
        $this->underRand = $underRand;
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
        if($this->replace){
            if($this->rand){
                $this->position->getWorld()->setBlockAt($x, Options::GROUND_HEIGHT + 1, $z, $this->getBlock1BySide($side));
            }
            if($this->underRand){
                $this->position->getWorld()->setBlockAt($x, Options::GROUND_HEIGHT, $z, $this->getBlock2BySide($side));
            }
        }else{
            if($this->side[$side]){
                if($this->rand){
                    $this->position->getWorld()->setBlockAt($x, Options::GROUND_HEIGHT + 1, $z, $this->block1);
                }
                if($this->underRand){
                    $this->position->getWorld()->setBlockAt($x, Options::GROUND_HEIGHT, $z, $this->block2);
                }
            }
        }
    }

    public function getBlock1BySide(int $side) : Block{
        if($this->side[$side]){
            return $this->block1;
        }else{
            return InternalBlockFactory::getBlock(0, 0);
        }
    }

    public function getBlock2BySide(int $side) : Block{
        if($this->side[$side]){
            return $this->block2;
        }else{
            return InternalBlockFactory::get(Options::PLOT_FLOOR_BLOCK);
        }
    }
}