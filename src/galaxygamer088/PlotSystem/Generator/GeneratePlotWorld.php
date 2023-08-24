<?php

namespace galaxygamer088\PlotSystem\Generator;

use galaxygamer088\PlotSystem\Options;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class GeneratePlotWorld extends Generator{

public int $roadWidth = Options::ROAD_WIDTH, $plotSize = Options::PLOT_SIZE, $groundHeight = Options::GROUND_HEIGHT, $totalSize = Options::TOTAL_SIZE;
const NULL = -1;
const PLOT = 0;
const ROAD_1 = 1;
const ROAD_2 = 2;
const WALL = 3;
const CROSSING = 4;

    public function __construct(int $seed, string $preset){
        parent::__construct($seed, $preset);
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
        $chunk = $world->getChunk($chunkX, $chunkZ);

        for($z = 0; $z < 16; ++$z) {
            for($x = 0; $x < 16; ++$x) {
                //$chunk->setBiomeId($x, 0, $z, BiomeIds::PLAINS);
                $chunk->setBlockStateId($x, 0, $z, Options::getBlocks()["BOTTOM_BLOCK"]->getStateId());

                $roadBlock = Options::getBlocks()["ROAD_BLOCK"]->getStateId();

                $type = $this->getShapeByPosition(($chunkX << 4) + $x, ($chunkZ << 4) + $z);
                if($type === self::PLOT){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, Options::getBlocks()["PLOT_BLOCK"]->getStateId());
                }elseif($type === self::ROAD_1){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::ROAD_2){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::CROSSING){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type == self::WALL){
                    $chunk->setBlockStateId($x, $this->groundHeight + 1, $z, Options::getBlocks()["RAND_BLOCK"]->getStateId());
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, Options::getBlocks()["UNDER_RAND_BLOCK"]->getStateId());
                }

                for($y = 1; $y <= $this->groundHeight; ++$y) {
                    if($y !== $this->groundHeight){
                        if($type == self::WALL){
                            $chunk->setBlockStateId($x, $y, $z, Options::getBlocks()["WALL_BLOCK"]->getStateId());
                        }else{
                            $chunk->setBlockStateId($x, $y, $z, Options::getBlocks()["FILL_BLOCK"]->getStateId());
                        }
                    }
                }
            }
        }
    }

    public function getPlotPos(int $worldX) : int{
        if($worldX >= 0){
            $pos = ($worldX % $this->totalSize) + 1;
        }else{
            $pos = $this->totalSize - abs($worldX % $this->totalSize) + 1;
        }
        if($pos == $this->totalSize + 1){
            $pos = 1;
        }
        return $pos;
    }

    public function getShapeByPosition(int $worldX, int $worldZ) : int{
        $X = $this->getPlotPos($worldX);
        $Z = $this->getPlotPos($worldZ);

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
        if($X == $this->plotSize + 1 and $Z <= $this->plotSize + 1 or $Z == $this->plotSize + 1 and $X <= $this->plotSize + 1 or $X == $this->totalSize and $Z <= $this->plotSize + 1 or $Z == $this->totalSize and $X <= $this->plotSize + 1 or $X == $this->totalSize and $Z == $this->totalSize){
            $type = self::WALL; //black
        }
        return $type;
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {}
}