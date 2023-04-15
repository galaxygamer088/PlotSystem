<?php

namespace galaxygamer088\PlotSystem\Generator;

use galaxygamer088\PlotSystem\Options;
use pocketmine\block\BlockFactory;
use pocketmine\data\bedrock\BiomeIds;
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
                $chunk->setBiomeId($x, $z, BiomeIds::PLAINS);
                $chunk->setFullBlock($x, 0, $z, BlockFactory::getInstance()->get(Options::PLOT_BOTTOM_BLOCK_ID, Options::PLOT_BOTTOM_BLOCK_META)->getFullId());

                $roadBlock = BlockFactory::getInstance()->get(Options::ROAD_ROAD_BLOCK_ID, Options::ROAD_ROAD_BLOCK_META)->getFullId();

                $type = $this->getShapeByPosition(($chunkX << 4) + $x, ($chunkZ << 4) + $z);
                if($type === self::PLOT){
                    $chunk->setFullBlock($x, $this->groundHeight, $z, BlockFactory::getInstance()->get(Options::PLOT_FLOOR_BLOCK_ID, Options::PLOT_FLOOR_BLOCK_META)->getFullId());
                }elseif($type === self::ROAD_1){
                    $chunk->setFullBlock($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::ROAD_2){
                    $chunk->setFullBlock($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::CROSSING){
                    $chunk->setFullBlock($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type == self::WALL){
                    $chunk->setFullBlock($x, $this->groundHeight + 1, $z, BlockFactory::getInstance()->get(Options::ROAD_RAND_BLOCK_ID, Options::ROAD_RAND_BLOCK_META)->getFullId());
                    $chunk->setFullBlock($x, $this->groundHeight, $z, BlockFactory::getInstance()->get(Options::ROAD_UNDER_RAND_BLOCK_ID, Options::ROAD_UNDER_RAND_BLOCK_META)->getFullId());
                }

                for($y = 1; $y <= $this->groundHeight; ++$y) {
                    if($y !== $this->groundHeight){
                        if($type == self::WALL){
                            $chunk->setFullBlock($x, $y, $z, BlockFactory::getInstance()->get(Options::ROAD_WALL_BLOCK_ID, Options::ROAD_WALL_BLOCK_META)->getFullId());
                        }else{
                            $chunk->setFullBlock($x, $y, $z, BlockFactory::getInstance()->get(Options::PLOT_FILL_BLOCK_ID, Options::PLOT_FILL_BLOCK_META)->getFullId());
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

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}