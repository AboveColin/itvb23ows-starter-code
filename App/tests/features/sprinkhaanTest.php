<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;

class SprinkhaanTest extends TestCase
{
    private $gameLogic;
    private $gameValidator;
    private $moveCalculator;

    protected function setUp(): void
    {
        $this->gameLogic = new BaseGameLogic();
        $this->gameValidator = new GameValidator();
        $this->moveCalculator = new MoveCalculator();
    }

    public function testGrasshopperCanJumpOverOneTile()
    {
        /*
            Test that the grasshopper can jump over one tile
        */
        $board = ['0,0' => [[0, 'G']], '0,1' => [[1, 'Q']], '-1,0' => [[0, 'Q']], '1,1' => [[1, 'B']]];
        $isValid = $this->moveCalculator->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertTrue($isValid);
    }

    public function testGrasshopperCannotJumpToSamePos()
    {
        /*
            Test that the grasshopper cannot jump to the same position
        */
        $board = ['0,0' => [[0, 'G']]];
        $isValid = $this->moveCalculator->isValidGrasshopperMove('0,0', '0,0', $board);
        $this->assertFalse($isValid);
    }

    public function testGrasshopperMustJumpOverAtLeastATile()
    {
        /*
            Test that the grasshopper must jump over at least one tile
        */
        $board = ['0,0' => [[0, 'G']], '0,2' => []]; // No tile to jump over
        $isValid = $this->moveCalculator->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertFalse($isValid);
    }

    public function testGrasshopperCannotLandOnOccupiedTile()
    {
        /*
            Test that the grasshopper cannot land on an occupied tile
        */
        $board = ['0,0' => [[0, 'G']], '0,1' => [[1, 'B']], '0,2' => [[1, 'A']]]; // Occupied tile at '0,2'
        $isValid = $this->moveCalculator->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertFalse($isValid);
    }

    public function testGrassHopperCannotJumpOverEmptyFields()
    {
        /*
            Test that the grasshopper cannot jump over empty fields
        */
        $board = ['0,0' => [[0, 'G']], '0,3' => []]; // Empty field at '0,1' and '0,2'
        $isValid = $this->moveCalculator->isValidGrasshopperMove('0,0', '0,3', $board);
        $this->assertFalse($isValid);
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
