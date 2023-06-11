<?php

namespace galaxygamer088\PlotSystem;

use pocketmine\block\VanillaBlocks;
class InternalBlockFactory
{

    public static function get($number){
        switch($number){
            case 0:
                $block = VanillaBlocks::AIR();
                break;
            case 1:
                $block = VanillaBlocks::OAK_SLAB();
                break;
            case 2:
                $block = VanillaBlocks::OAK_PLANKS();
                break;

            case 3:
                $block = VanillaBlocks::STONE_BRICK_SLAB();
                break;

            case 4:
                $block = VanillaBlocks::STONE_BRICKS();
                break;

            case 5:
                $block = VanillaBlocks::BEDROCK();
                break;

            case 6:
                $block = VanillaBlocks::SPRUCE_PLANKS();
                break;
            case 7:
                $block = VanillaBlocks::GRASS();
                break;
            case 8:
                $block = VanillaBlocks::DIRT();
                break;
            default:
                $block = VanillaBlocks::AIR();
                break;
        }
        return $block;
    }
    public static function getBlock($id, $meta = null){
        switch($id){
            case 0:
                $block = VanillaBlocks::AIR();
                break;
            case 1:
                switch($meta){
                    default:
                        $block = VanillaBlocks::STONE();
                        break;
                    case 1:
                        $block = VanillaBlocks::GRANITE();
                        break;
                    case 2:
                        $block = VanillaBlocks::POLISHED_GRANITE();
                        break;
                    case 3:
                        $block = VanillaBlocks::DIORITE();
                        break;
                    case 4:
                        $block = VanillaBlocks::POLISHED_DIORITE();
                        break;
                    case 5:
                        $block = VanillaBlocks::ANDESITE();
                        break;
                    case 6:
                        $block = VanillaBlocks::POLISHED_ANDESITE();
                }
                break;
            case 2:
                $block = VanillaBlocks::GRASS();
                break;
            case 3:
                $block = VanillaBlocks::DIRT();
                break;
            case 4:
                $block = VanillaBlocks::COBBLESTONE();
                break;
            case 5:
                switch($meta){
                    default:
                        $block = VanillaBlocks::OAK_PLANKS();
                        break;
                    case 1:
                        $block = VanillaBlocks::SPRUCE_PLANKS();
                        break;
                    case 2:
                        $block = VanillaBlocks::BIRCH_PLANKS();
                        break;
                    case 3:
                        $block = VanillaBlocks::JUNGLE_PLANKS();
                        break;
                    case 4:
                        $block = VanillaBlocks::ACACIA_PLANKS();
                        break;
                    case 5:
                        $block = VanillaBlocks::DARK_OAK_PLANKS();
                        break;
                }
                break;
            case 6:
                switch($meta){
                    default:
                        $block = VanillaBlocks::OAK_SAPLING();
                        break;
                    case 1:
                        $block = VanillaBlocks::SPRUCE_SAPLING();
                        break;
                    case 2:
                        $block = VanillaBlocks::BIRCH_SAPLING();
                        break;
                    case 3:
                        $block = VanillaBlocks::JUNGLE_SAPLING();
                        break;
                    case 4:
                        $block = VanillaBlocks::ACACIA_SAPLING();
                        break;
                    case 5:
                        $block = VanillaBlocks::DARK_OAK_SAPLING();
                        break;
                }
                break;

            case 7:
                $block = VanillaBlocks::BEDROCK();
                break;

            case 8 or 9:
                $block = VanillaBlocks::WATER();
                break;
            case 10 or 11:
                $block = VanillaBlocks::LAVA();
                break;
            case 12:
                switch($meta){
                    case 0 or null:
                        $block = VanillaBlocks::SAND();
                        break;
                    case 1:
                        $block = VanillaBlocks::RED_SAND();
                        break;
                }
                break;
            case 98:
                switch($meta){
                    default:
                        $block = VanillaBlocks::STONE_BRICKS();
                        break;
                    case 1:
                        $block = VanillaBlocks::MOSSY_STONE_BRICKS();
                        break;
                    case 2:
                        $block = VanillaBlocks::CRACKED_STONE_BRICKS();
                        break;
                    case 3:
                        $block = VanillaBlocks::CHISELED_STONE_BRICKS();
                        break;
                }
                break;
            case 158:
                switch($meta){
                    default:
                        $block = VanillaBlocks::OAK_SLAB();
                        break;
                    case 1:
                        $block = VanillaBlocks::SPRUCE_SLAB();
                        break;
                    case 2:
                        $block = VanillaBlocks::BIRCH_SLAB();
                        break;
                    case 3:
                        $block = VanillaBlocks::JUNGLE_SLAB();
                        break;
                    case 4:
                        $block = VanillaBlocks::ACACIA_SLAB();
                        break;
                    case 5:
                        $block = VanillaBlocks::DARK_OAK_SLAB();
                        break;
                }
                break;
            default:
                switch(mt_rand(1, 20) % 2){
                    case 0:
                        $block = VanillaBlocks::INFO_UPDATE();
                        break;
                    case 1:
                        $block = VanillaBlocks::INFO_UPDATE2();
                        break;
                }
                break;
        }
        return $block;
    }

}