<?php

namespace galaxygamer088\PlotSystem;

use pocketmine\block\VanillaBlocks;

final class Options{

    public const ROAD_WIDTH = 9;
    public const PLOT_SIZE = 40;
    public const GROUND_HEIGHT = 40;
    public const TOTAL_SIZE = Options::PLOT_SIZE + Options::ROAD_WIDTH;

    static function getBlocks() : array{
        return [
            "PLOT_BLOCK" => VanillaBlocks::GRASS(),
            "FILL_BLOCK" => VanillaBlocks::DIRT(),
            "BOTTOM_BLOCK" => VanillaBlocks::BEDROCK(),
            "ROAD_BLOCK" => VanillaBlocks::SPRUCE_PLANKS(),
            "RAND_BLOCK" => VanillaBlocks::STONE_BRICK_SLAB(),
            "UNDER_RAND_BLOCK" => VanillaBlocks::STONE_BRICKS(),
            "CLAIM_RAND_BLOCK" => VanillaBlocks::OAK_SLAB(),
            "CLAIM_UNDER_RAND_BLOCK" => VanillaBlocks::OAK_PLANKS(),
            "WALL_BLOCK" => VanillaBlocks::STONE_BRICKS()];
    }
}