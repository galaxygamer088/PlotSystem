<?php

namespace galaxygamer088\PlotSystem\Generator;

use galaxygamer088\PlotSystem\Options;
use galaxygamer088\PlotSystem\Options_test;
use galaxygamer088\PlotSystem\Generator\InternalBlockFactory;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;
use pocketmine\block\VanillaBlocks;

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

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void{
        $chunk = $world->getChunk($chunkX, $chunkZ);
        for($z = 0; $z < 16; ++$z) {
            for($x = 0; $x < 16; ++$x) {
                $chunk->setBiomeId($x, Options_test::Y_LEVEL_IN_GENERATOR_SETBIOME, $z, BiomeIds::PLAINS);
               // $chunk->setBlockStateId($x, 0, $z, BlockFactory::getInstance()->get(Options::PLOT_BOTTOM_BLOCK_ID, Options::PLOT_BOTTOM_BLOCK_META)->getStateId());
               // $chunk->setBlockStateId($x, 0, $z, InternalBlockFactory::get(Options_test::PLOT_BOTTOM_BLOCK)->getStateId());
                $chunk->setBlockStateId($x, 0, $z, VanillaBlocks::BEDROCK()->getStateId());

                //$roadBlock = BlockFactory::getInstance()->get(Options::ROAD_ROAD_BLOCK_ID, Options::ROAD_ROAD_BLOCK_META)->getStateId();
                $roadBlock = VanillaBlocks::SPRUCE_PLANKS()->getStateId();

                $type = $this->getShapeByPosition(($chunkX << 4) + $x, ($chunkZ << 4) + $z);
                if($type === self::PLOT){
                    //$chunk->setBlockStateId ($x, $this->groundHeight, $z, BlockFactory::getInstance()->get(Options::PLOT_FLOOR_BLOCK_ID, Options::PLOT_FLOOR_BLOCK_META)->getStateId());
                    $chunk->setBlockStateId ($x, $this->groundHeight, $z, VanillaBlocks::GRASS()->getStateId());
                }elseif($type === self::ROAD_1){
                    $chunk->setBlockStateId ($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::ROAD_2){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type === self::CROSSING){
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, $roadBlock);
                }elseif($type == self::WALL){
                    //$chunk->setBlockStateId($x, $this->groundHeight + 1, $z, BlockFactory::getInstance()->get(Options::ROAD_RAND_BLOCK_ID, Options::ROAD_RAND_BLOCK_META)->getStateId());
                    $chunk->setBlockStateId($x, $this->groundHeight + 1, $z, VanillaBlocks::STONE_BRICK_SLAB()->getStateId());
                    //$chunk->setBlockStateId($x, $this->groundHeight, $z, BlockFactory::getInstance()->get(Options::ROAD_UNDER_RAND_BLOCK_ID, Options::ROAD_UNDER_RAND_BLOCK_META)->getStateId());
                    $chunk->setBlockStateId($x, $this->groundHeight, $z, VanillaBlocks::STONE_BRICKS()->getStateId());
                }

                for($y = 1; $y <= $this->groundHeight; ++$y) {
                    if($y !== $this->groundHeight){
                        if($type == self::WALL){
                            //$chunk->setBlockStateId($x, $y, $z, BlockFactory::getInstance()->get(Options::ROAD_WALL_BLOCK_ID, Options::ROAD_WALL_BLOCK_META)->getStateId());
                            $chunk->setBlockStateId($x, $y, $z, VanillaBlocks::STONE_BRICKS()->getStateId());
                        }else{
                            $chunk->setBlockStateId($x, $y, $z, VanillaBlocks::DIRT()->getStateId());
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