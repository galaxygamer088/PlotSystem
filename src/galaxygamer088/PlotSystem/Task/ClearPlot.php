<?php

namespace galaxygamer088\PlotSystem\Task;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class ClearPlot extends Task{

public World $world;
public array $plotId;

    public function __construct(World $world, array $plotId){
        $this->world = $world;
        $this->plotId = $plotId;
    }

    public function onRun() : void{
        for($x = 0; $x <= Options::PLOT_SIZE - 1; $x++){
            for($z = 0; $z <= Options::PLOT_SIZE - 1; $z++){
                for($y = 0; $y <= 255; $y++){
                    $X = $this->plotId[0] * Options::TOTAL_SIZE + $x;
                    $Z = $this->plotId[1] * Options::TOTAL_SIZE + $z;

                    if($y == 0){
                        $bottomBlock = Options::getBlocks()["BOTTOM_BLOCK"];
                        if($this->world->getBlock(new Vector3($X, $y, $Z))->getTypeId() !== $bottomBlock->getTypeId()){
                            $this->world->setBlockAt($X, $y, $Z, $bottomBlock);
                        }
                    }elseif($y == Options::GROUND_HEIGHT){
                        $plotBlock = Options::getBlocks()["PLOT_BLOCK"];
                        if($this->world->getBlock(new Vector3($X, $y, $Z))->getTypeId() !== $plotBlock->getTypeId()){
                            $this->world->setBlockAt($X, $y, $Z, $plotBlock);
                        }
                    }elseif($y < Options::GROUND_HEIGHT){
                        $fillBlock = Options::getBlocks()["FILL_BLOCK"];
                        if($this->world->getBlock(new Vector3($X, $y, $Z))->getTypeId() !== $fillBlock->getTypeId()){
                            $this->world->setBlockAt($X, $y, $Z, $fillBlock);
                        }
                    }else{
                        $airBlock = VanillaBlocks::AIR();
                        if($this->world->getBlock(new Vector3($X, $y, $Z))->getTypeId() !== $airBlock->getTypeId()){
                            $this->world->setBlockAt($X, $y, $Z, $airBlock);
                        }
                    }
                }
            }
        }
    }
}