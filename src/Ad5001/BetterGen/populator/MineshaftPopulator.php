<?php

/*
 * MineshaftPopulator from BetterGen
 * Copyright (C) Ad5001 2017
 * Licensed under the BoxOfDevs Public General LICENSE which can be found in the file LICENSE in the root directory
 * @author ad5001
 */
namespace Ad5001\BetterGen\populator;

use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;
use Ad5001\BetterGen\utils\BuildingUtils;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use Ad5001\BetterGen\loot\LootTable;

class MineshaftPopulator extends AmountPopulator {
	protected $maxPath;
	protected $level;
	const DIR_XPLUS = 0;
	const DIR_XMIN = 1;
	const DIR_ZPLUS = 2;
	const DIR_ZMIN = 3;
	const TYPE_FORWARD = 0;
	const TYPE_CROSSPATH = 1;
	const TYPE_STAIRS = 2;
	
	/*
	 * Populate the chunk
	 * @param $level pocketmine\level\ChunkManager
	 * @param $chunkX int
	 * @param $chunkZ int
	 * @param $random pocketmine\utils\Random
	 */
	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random) {
		if ($this->getAmount ( $random ) < 100)
			return;
		$this->level = $level;
		$x = $random->nextRange ( $chunkX << 4, ($chunkX << 4) + 15 );
		$z = $random->nextRange ( $chunkZ << 4, ($chunkZ << 4) + 15 );
		$y = $random->nextRange ( 5, 50 );
		// First filling the large dirt place (center of the mineshaft)
		BuildingUtils::fill ( $level, new Vector3 ( $x - 6, $y, $x - 6 ), new Vector3 ( $x + 6, $y + 8, $z + 6 ), Block::get ( Block::AIR ) );
		BuildingUtils::fill ( $level, new Vector3 ( $x - 6, $y, $x - 6 ), new Vector3 ( $x + 6, $y, $z + 6 ), Block::get ( Block::DIRT ) );
		$startingPath = $random->nextBoundedInt ( 4 );
		$this->maxPath = $random->nextBoundedInt ( 100 ) + 50;
		foreach ( array_fill ( 0, $startingPath, 1 ) as $hey ) {
			$dir = $random->nextBoundedInt ( 4 );
			switch ($dir) {
				case self::DIR_XPLUS :
					$this->generateMineshaftPart ( $x + 6, $y + $random->nextBoundedInt ( 5 ), $z + $random->nextBoundedInt ( 12 ) - 6, $dir, $random );
					break;
				case self::DIR_XMIN :
					$this->generateMineshaftPart ( $x - 6, $y + $random->nextBoundedInt ( 5 ), $z + $random->nextBoundedInt ( 12 ) - 6, $dir, $random );
					break;
				case self::DIR_ZPLUS :
					$this->generateMineshaftPart ( $x + $random->nextBoundedInt ( 12 ) - 6, $y + $random->nextBoundedInt ( 8 ), $z + 6, $dir, $random );
					break;
				case self::DIR_ZMIN :
					$this->generateMineshaftPart ( $x + $random->nextBoundedInt ( 12 ) - 6, $y + $random->nextBoundedInt ( 8 ), $z - 6, $dir, $random );
					break;
			}
		}
	}
	
	/*
	 * Builds a mineshaft part and return applicable directions
	 * @param $x int
	 * @param $y int
	 * @param $z int
	 * @param $dir int
	 * @param $random pocketmine\utils\Random
	 * @param $deepneth int
	 */
	public function generateMineshaftPart(int $x, int $y, int $z, int $dir, Random $random) {
		if ($this->maxPath -- < 1)
			return;
		$type = $random->nextBoundedInt ( 3 );
		$level = $this->level;
		switch ($type) {
			case self::TYPE_FORWARD :
				switch ($dir) {
					case self::DIR_XPLUS :
						// First, filling everything blank.
						BuildingUtils::fill ( $this->level, new Vector3 ( $x, $y, $z - 1 ), new Vector3 ( $x + 4, $y + 2, $z + 1 ), Block::get ( Block::AIR ) );
						// Then, making sure the floor is solid.
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y - 1, $z - 1 ), new Vector3 ( $x + 4, $y - 1, $z + 1 ), function ($v3, $params) {
							$level = $params [0];
							if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
						}, $this->level );
						// Putting rails
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y, $z ), new Vector3 ( $x + 4, $y, $z ), function ($v3, $params) {
							$level = $params [0];
							$random = $params [1];
							if ($random->nextBoundedInt ( 3 ) !== 0) {
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::RAIL );
								$level->setBlockDataAt ( $v3->x, $v3->y, $v3->z, 1 );
							}
						}, $this->level, $random );
						// After this, building the floor maintener (the wood structure)
						$level->setBlockIdAt ( $x, $y, $z - 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y, $z + 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 1, $z - 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 1, $z + 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 2, $z - 1, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z + 1, Block::PLANK );
						$level->setBlockIdAt ( $x + 1, $y + 2, $z, Block::TORCH );
						$level->setBlockDataAt ( $x + 1, $y + 2, $z, 2 );
						// Generating chest
						if ($random->nextBoundedInt ( 30 ) == 0) {
							$direction = ( int ) $random->nextBoolean ();
							if ($direction == 0)
								$direction = - 1; // Choosing the part of the rail.
							$direction2 = ( int ) $random->nextBoolean ();
							if ($direction2 == 0)
								$direction2 = 2;
							if ($direction2 == 1)
								$direction2 = 4;
							LootTable::buildLootTable ( new Vector3 ( $x + $direction2, $y, $z + $direction ), LootTable::LOOT_MINESHAFT, $random );
						}
						if ($random->nextBoundedInt ( 30 ) !== 0)
							$this->generateMineshaftPart ( $x + 5, $y, $z, $dir, $random );
						break;
					case self::DIR_XMIN :
						// First, filling everything blank.
						BuildingUtils::fill ( $this->level, new Vector3 ( $x, $y, $z - 1 ), new Vector3 ( $x - 4, $y + 2, $z + 1 ) );
						// Then, making sure the floor is solid.
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y - 1, $z - 1 ), new Vector3 ( $x - 4, $y - 1, $z + 1 ), function ($v3, $params) {
							$level = $params [0];
							if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
						}, $this->level );
						// Putting rails
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y, $z ), new Vector3 ( $x - 4, $y, $z ), function ($v3, $params) {
							$level = $params [0];
							$random = $params [1];
							if ($random->nextBoundedInt ( 3 ) !== 0) {
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::RAIL );
								$level->setBlockDataAt ( $v3->x, $v3->y, $v3->z, 1 );
							}
						}, $this->level, $random );
						// After this, building the floor maintener (the wood structure)
						$level->setBlockIdAt ( $x, $y, $z - 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y, $z + 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 1, $z - 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 1, $z + 1, Block::FENCE );
						$level->setBlockIdAt ( $x, $y + 2, $z - 1, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z + 1, Block::PLANK );
						$level->setBlockIdAt ( $x - 1, $y + 2, $z, Block::TORCH );
						$level->setBlockDataAt ( $x - 1, $y + 2, $z, 1 );
						// Generating chest
						if ($random->nextBoundedInt ( 30 ) == 0) {
							$direction = ( int ) $random->nextBoolean ();
							if ($direction == 0)
								$direction = - 1; // Choosing the part of the rail.
							$direction2 = ( int ) $random->nextBoolean ();
							if ($direction2 == 0)
								$direction2 = 2;
							if ($direction2 == 1)
								$direction2 = 
							LootTable::buildLootTable ( new Vector3 ( $x - $direction2, $y, $z + $direction ), LootTable::LOOT_MINESHAFT, $random );
						}
						if ($random->nextBoundedInt ( 30 ) !== 0)
							$this->generateMineshaftPart ( $x - 5, $y, $z, $dir, $random );
						break;
					case self::DIR_ZPLUS :
						// First, filling everything blank.
						BuildingUtils::fill ( $this->level, new Vector3 ( $x - 1, $y, $z ), new Vector3 ( $x + 1, $y + 2, $z + 4 ) );
						// Then, making sure the floor is solid.
						BuildingUtils::fillCallback ( new Vector3 ( $x - 1, $y - 1, $z ), new Vector3 ( $x + 1, $y - 1, $z + 4 ), function ($v3, $params) {
							$level = $params [0];
							if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
						}, $this->level );
						// Putting rails
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y, $z ), new Vector3 ( $x, $y, $z + 4 ), function ($v3, $params) {
							$level = $params [0];
							$random = $params [1];
							if ($random->nextBoundedInt ( 3 ) !== 0) {
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::RAIL );
								$level->setBlockDataAt ( $v3->x, $v3->y, $v3->z, 0 );
							}
						}, $this->level, $random );
						// After this, building the floor maintener (the wood structure)
						$level->setBlockIdAt ( $x - 1, $y, $z, Block::FENCE );
						$level->setBlockIdAt ( $x + 1, $y, $z, Block::FENCE );
						$level->setBlockIdAt ( $x - 1, $y + 1, $z, Block::FENCE );
						$level->setBlockIdAt ( $x + 1, $y + 1, $z, Block::FENCE );
						$level->setBlockIdAt ( $x - 1, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x + 1, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z - 1, Block::TORCH );
						$level->setBlockDataAt ( $x, $y + 2, $z - 1, 4 );
						// Generating chest
						if ($random->nextBoundedInt ( 30 ) == 0) {
							$direction = ( int ) $random->nextBoolean ();
							if ($direction == 0)
								$direction = - 1; // Choosing the part of the rail.
							$direction2 = ( int ) $random->nextBoolean ();
							if ($direction2 == 0)
								$direction2 = 2;
							if ($direction2 == 1)
								$direction2 = 4;
							LootTable::buildLootTable ( new Vector3 ( $x + $direction, $y, $z + $direction2 ), LootTable::LOOT_MINESHAFT, $random );
						}
						if ($random->nextBoundedInt ( 30 ) !== 0)
							$this->generateMineshaftPart ( $x, $y, $z + 5, $dir, $random );
						break;
					case self::DIR_ZMIN :
						// First, filling everything blank.
						BuildingUtils::fill ( $this->level, new Vector3 ( $x - 1, $y, $z ), new Vector3 ( $x + 1, $y + 2, $z - 4 ) );
						// Then, making sure the floor is solid.
						BuildingUtils::fillCallback ( new Vector3 ( $x - 1, $y - 1, $z ), new Vector3 ( $x + 1, $y - 1, $z - 4 ), function ($v3, $params) {
							$level = $params [0];
							if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
						}, $this->level );
						// Putting rails
						BuildingUtils::fillCallback ( new Vector3 ( $x, $y, $z ), new Vector3 ( $x, $y, $z - 4 ), function ($v3, $params) {
							$level = $params [0];
							$random = $params [1];
							if ($random->nextBoundedInt ( 3 ) !== 0) {
								$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::RAIL );
								$level->setBlockDataAt ( $v3->x, $v3->y, $v3->z, 0 );
							}
						}, $this->level, $random );
						// After this, building the floor maintener (the wood structure)
						$level->setBlockIdAt ( $x - 1, $y, $z, Block::FENCE );
						$level->setBlockIdAt ( $x + 1, $y, $z, Block::FENCE );
						$level->setBlockIdAt ( $x - 1, $y + 1, $z, Block::FENCE );
						$level->setBlockIdAt ( $x + 1, $y + 1, $z, Block::FENCE );
						$level->setBlockIdAt ( $x - 1, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x + 1, $y + 2, $z, Block::PLANK );
						$level->setBlockIdAt ( $x, $y + 2, $z - 1, Block::TORCH );
						$level->setBlockDataAt ( $x, $y + 2, $z - 1, 3 );
						// Generating chest
						if ($random->nextBoundedInt ( 30 ) == 0) {
							$direction = ( int ) $random->nextBoolean ();
							if ($direction == 0)
								$direction = - 1; // Choosing the part of the rail.
							$direction2 = ( int ) $random->nextBoolean ();
							if ($direction2 == 0)
								$direction2 = 2;
							if ($direction2 == 1)
								$direction2 = 4;
							LootTable::buildLootTable ( new Vector3 ( $x + $direction, $y, $z - $direction2 ), LootTable::LOOT_MINESHAFT, $random );
						}
						if ($random->nextBoundedInt ( 30 ) !== 0)
							$this->generateMineshaftPart ( $x, $y, $z - 5, $dir, $random );
						break;
				}
				// Doing cobwebs
				$webNum = $random->nextBoundedInt ( 5 ) + 2;
				for($i = 0; $i < $webNum; $i ++) {
					$xx = $x + $random->nextBoundedInt ( 5 ) - 2;
					$yy = $y + $random->nextBoundedInt ( 3 );
					$zz = $z + $random->nextBoundedInt ( 5 ) - 2;
					if ($level->getBlockIdAt ( $xx, $yy, $zz ) == Block::AIR)
						$level->setBlockIdAt ( $xx, $yy, $zz, Block::COBWEB );
				}
				break;
			case self::TYPE_CROSSPATH :
				$possiblePathes = [ 
						self::DIR_XPLUS,
						self::DIR_XMIN,
						self::DIR_ZPLUS,
						self::DIR_ZMIN 
				];
				switch ($dir) {
					case self::DIR_XPLUS :
						$x ++;
						unset ( $possiblePathes [0] );
						break;
					case self::DIR_XMIN :
						$x --;
						unset ( $possiblePathes [1] );
						break;
					case self::DIR_ZPLUS :
						$z ++;
						unset ( $possiblePathes [2] );
						break;
					case self::DIR_ZMIN :
						$z --;
						unset ( $possiblePathes [3] );
						break;
				}
				
				// Then, making sure the floor is solid.
				BuildingUtils::fillCallback ( new Vector3 ( $x + 1, $y - 1, $z - 1 ), new Vector3 ( $x - 1, $y - 1, $z + 1 ), function ($v3, $params) {
					$level = $params [0];
					if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
						$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
				}, $this->level );
				// Putting rails
				
				BuildingUtils::fill ( $this->level, new Vector3 ( $x - 1, $y, $z - 1 ), new Vector3 ( $x + 1, $y + 6, $z + 1 ), Block::get ( Block::AIR ) );
				
				BuildingUtils::corners ( $this->level, new Vector3 ( $x - 1, $y, $z - 1 ), new Vector3 ( $x + 1, $y + 6, $z + 1 ), Block::get ( Block::PLANK ) );
				
				$newFloor = $random->nextBoolean ();
				$numberFloor = $random->nextBoundedInt ( 4 + ($newFloor ? 5 : 0) );
				$possiblePathes = [ 
						$possiblePathes,
						($newFloor ? [ 
								self::DIR_XPLUS,
								self::DIR_XMIN,
								self::DIR_ZPLUS,
								self::DIR_ZMIN 
						] : [ ]) 
				];
				for($i = 7; $i > $newfloor; $i --) {
					$chooseNew = ( int ) $newFloor && $random->nextBoolean ();
					$choose = $random->nextBoundedInt ( 4 );
					unset ( $possiblePathes [$chooseNew] [$choose] );
				}
				// Buidling pathes
				foreach ( $possiblePathes [0] as $path ) {
					switch ($path) {
						case self::DIR_XPLUS :
							$this->generateMineshaftPart ( $x + 2, $y, $z, self::DIR_XPLUS, $random );
							break;
						case self::DIR_XMIN :
							$this->generateMineshaftPart ( $x - 2, $y, $z, self::DIR_XMIN, $random );
							break;
						case self::DIR_ZPLUS :
							$this->generateMineshaftPart ( $x, $y, $z + 2, self::DIR_ZPLUS, $random );
							break;
						case self::DIR_ZMIN :
							$this->generateMineshaftPart ( $x, $y, $z - 2, self::DIR_ZMIN, $random );
							break;
					}
				}
				foreach ( $possiblePathes [1] as $path ) {
					switch ($path) {
						case self::DIR_XPLUS :
							$this->generateMineshaftPart ( $x + 2, $y + 4, $z, self::DIR_XPLUS, $random );
							break;
						case self::DIR_XMIN :
							$this->generateMineshaftPart ( $x - 2, $y + 4, $z, self::DIR_XMIN, $random );
							break;
						case self::DIR_ZPLUS :
							$this->generateMineshaftPart ( $x, $y + 4, $z + 2, self::DIR_ZPLUS, $random );
							break;
						case self::DIR_ZMIN :
							$this->generateMineshaftPart ( $x, $y + 4, $z - 2, self::DIR_ZMIN, $random );
							break;
					}
				}
				
				// Doing cobwebs
				$webNum = $random->nextBoundedInt ( 5 ) + 2;
				for($i = 0; $i < $webNum; $i ++) {
					$xx = $x + $random->nextBoundedInt ( 3 ) - 1;
					$yy = $y + $random->nextBoundedInt ( 6 );
					$zz = $z + $random->nextBoundedInt ( 3 ) - 1;
					if ($level->getBlockIdAt ( $xx, $yy, $zz ) == Block::AIR)
						$level->setBlockIdAt ( $xx, $yy, $zz, Block::COBWEB );
				}
				break;
			case self::TYPE_STAIRS :
				if($y <= 5) {
					$this->generateMineshaftPart ( $x, $y, $z, $dir, $random );
					return;
				}
				// Building stairs
				for($i = 0; $i < 4; $i ++) {
					switch ($path) {
						case self::DIR_XPLUS :
							BuildingUtils::fill ( $this->level, new Vector3 ( $x + $i, $y - $i - 1, $z - 2 ), new Vector3 ( $x + $i, $y - $i + 3, $z + 2 ), Block::get ( Block::AIR ) );
							BuildingUtils::fillCallback ( new Vector3 ( $x + $i, $y - $i - 2, $z - 2 ), new Vector3 ( $x + $i, $y - $i - 2, $z + 2 ), function ($v3, $params) {
								$level = $params [0];
								if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
									$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
							}, $this->level );
							break;
						case self::DIR_XMIN :
							BuildingUtils::fill ( $this->level, new Vector3 ( $x - $i, $y - $i - 1, $z - 2 ), new Vector3 ( $x - $i, $y - $i + 3, $z + 2 ), Block::get ( Block::AIR ) );
							BuildingUtils::fillCallback ( new Vector3 ( $x - $i, $y - $i - 2, $z - 2 ), new Vector3 ( $x - $i, $y - $i - 2, $z + 2 ), function ($v3, $params) {
								$level = $params [0];
								if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
									$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
							}, $this->level );
							break;
						case self::DIR_ZPLUS :
							BuildingUtils::fill ( $this->level, new Vector3 ( $x - 2, $y - $i - 1, $z + $i ), new Vector3 ( $x + 2, $y - $i + 3, $z + $i ), Block::get ( Block::AIR ) );
							BuildingUtils::fillCallback ( new Vector3 ( $x - 2, $y - $i - 2, $z + $i ), new Vector3 ( $x + 2, $y - $i - 2, $z + $i ), function ($v3, $params) {
								$level = $params [0];
								if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
									$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
							}, $this->level );
							break;
						case self::DIR_ZMIN :
							BuildingUtils::fill ( $this->level, new Vector3 ( $x - 2, $y - $i - 1, $z - $i ), new Vector3 ( $x + 2, $y - $i + 3, $z - $i ), Block::get ( Block::AIR ) );
							BuildingUtils::fillCallback ( new Vector3 ( $x - 2, $y - $i - 2, $z - $i ), new Vector3 ( $x + 2, $y - $i - 2, $z - $i ), function ($v3, $params) {
								$level = $params [0];
								if ($level->getBlockIdAt ( $v3->x, $v3->y, $v3->z ) == Block::AIR)
									$level->setBlockIdAt ( $v3->x, $v3->y, $v3->z, Block::PLANK );
							}, $this->level );
							break;
					}
				}
				
				// Next one
				switch ($path) {
					case self::DIR_XPLUS :
						$this->generateMineshaftPart ( $x + 4, $y - 4, $z, self::DIR_XPLUS, $random );
						break;
					case self::DIR_XMIN :
						$this->generateMineshaftPart ( $x - 4, $y - 4, $z, self::DIR_XMIN, $random );
						break;
					case self::DIR_ZPLUS :
						$this->generateMineshaftPart ( $x, $y - 4, $z + 4, self::DIR_ZPLUS, $random );
						break;
					case self::DIR_ZMIN :
						$this->generateMineshaftPart ( $x, $y - 4, $z - 4, self::DIR_ZMIN, $random );
						break;
				}
				break;
		}
	}
}
?>