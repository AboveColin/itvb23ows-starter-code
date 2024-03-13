<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\GameLogic;

class TestSprinkhaan extends TestCase
{
    private $gameLogic;

    protected function setUp(): void
    {
        $this->gameLogic = new GameLogic();
    }

    public function testGrasshopperCanJumpOverOneTile()
    {
        /*
            Test that the grasshopper can jump over one tile
        */
        $board = ['0,0' => [[0, 'G']], '0,1' => [[1, 'Q']], '-1,0' => [[0, 'Q']], '1,1' => [[1, 'B']]];
        $isValid = $this->gameLogic->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertTrue($isValid);
    }

    public function testGrasshopperCannotJumpToSamePos()
    {
        /*
            Test that the grasshopper cannot jump to the same position
        */
        $board = ['0,0' => [[0, 'G']]];
        $isValid = $this->gameLogic->isValidGrasshopperMove('0,0', '0,0', $board);
        $this->assertFalse($isValid);
    }

    public function testGrasshopperMustJumpOverAtLeastATile()
    {
        /*
            Test that the grasshopper must jump over at least one tile
        */
        $board = ['0,0' => [[0, 'G']], '0,2' => []]; // No tile to jump over
        $isValid = $this->gameLogic->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertFalse($isValid);
    }

    public function testGrasshopperCannotLandOnOccupiedTile()
    {
        /*
            Test that the grasshopper cannot land on an occupied tile
        */
        $board = ['0,0' => [[0, 'G']], '0,1' => [[1, 'B']], '0,2' => [[1, 'A']]]; // Occupied tile at '0,2'
        $isValid = $this->gameLogic->isValidGrasshopperMove('0,0', '0,2', $board);
        $this->assertFalse($isValid);
    }

    public function testGrassHopperCannotJumpOverEmptyFields()
    {
        /*
            Test that the grasshopper cannot jump over empty fields
        */
        $board = ['0,0' => [[0, 'G']], '0,3' => []]; // Empty field at '0,1' and '0,2'
        $isValid = $this->gameLogic->isValidGrasshopperMove('0,0', '0,3', $board);
        $this->assertFalse($isValid);
    }
}
