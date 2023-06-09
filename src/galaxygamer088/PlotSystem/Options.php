<?php

namespace galaxygamer088\PlotSystem;

interface Options{


    public const ROAD_CLAIM_RAND_BLOCK = 1;
    public const ROAD_CLAIM_UNDER_RAND_BLOCK = 2;
    public const ROAD_RAND_BLOCK = 3;
    public const ROAD_UNDER_RAND_BLOCK = 4;
    public const ROAD_WALL_BLOCK = self::ROAD_UNDER_RAND_BLOCK;
    public const PLOT_BOTTOM_BLOCK = 5;
    public const ROAD_ROAD_BLOCK = 6;

    public const PLOT_FLOOR_BLOCK = 7;
    public const PLOT_FILL_BLOCK = 8;

    public const Y_LEVEL_IN_GENERATOR_SETBIOME = 0;

    public const ROAD_WIDTH = 9;
    public const PLOT_SIZE = 40;
    public const GROUND_HEIGHT = 40;
    public const TOTAL_SIZE = Options::PLOT_SIZE + Options::ROAD_WIDTH;


    public const PLOT_FLOOR_BLOCK_ID = 2;
    public const PLOT_FLOOR_BLOCK_META = 0;

    public const PLOT_FILL_BLOCK_ID = 3;
    public const PLOT_FILL_BLOCK_META = 0;

    public const PLOT_BOTTOM_BLOCK_ID = 7;
    public const PLOT_BOTTOM_BLOCK_META = 0;


    public const ROAD_ROAD_BLOCK_ID = 5;
    public const ROAD_ROAD_BLOCK_META = 1;

    public const ROAD_RAND_BLOCK_ID = 44; #
    public const ROAD_RAND_BLOCK_META = 5; #

    public const ROAD_UNDER_RAND_BLOCK_ID = 98; #
    public const ROAD_UNDER_RAND_BLOCK_META = 0; #

    public const ROAD_CLAIM_RAND_BLOCK_ID = 158; #
    public const ROAD_CLAIM_RAND_BLOCK_META = 0; #

    public const ROAD_CLAIM_UNDER_RAND_BLOCK_ID = 5; #
    public const ROAD_CLAIM_UNDER_RAND_BLOCK_META = 0; #

    public const ROAD_WALL_BLOCK_ID = 98; #
    public const ROAD_WALL_BLOCK_META = 0; #

}