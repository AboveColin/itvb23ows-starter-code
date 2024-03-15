<?php

use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;
use PHPUnit\Framework\TestCase;

class WinConditionTest extends TestCase {
    private $gameLogic;
    private $gameValidator;
    private $moveCalculator;

    protected function setUp(): void {
        $this->gameLogic = new BaseGameLogic();
        $this->gameValidator = new GameValidator();
        $this->moveCalculator = new MoveCalculator();
    }

    public function testIsNeighbour() {
        $this->assertTrue($this->gameLogic->isNeighbour('0,0', '0,1'));
        $this->assertFalse($this->gameLogic->isNeighbour('0,0', '2,2'));
    }

    public function testIsQueenSurrounded() {
        $board = [
            '0,0' => [[0, 'Q']],
            '0,1' => [[1, 'B']],
            '0,-1' => [[1, 'B']],
            '1,0' => [[1, 'B']],
            '-1,0' => [[1, 'B']],
            '1,-1' => [[1, 'B']],
            '-1,1' => [[1, 'B']],
        ];
        $this->assertTrue($this->gameValidator->isQueenSurrounded($board, 0));
    }

    public function testIsDraw() {
        $board = [
            '0,0' => [[0, 'Q']],
            '0,1' => [[1, 'Q']],
            '0,-1' => [[1, 'B']],
            '1,0' => [[1, 'B']],
            '-1,0' => [[1, 'B']],
            '1,-1' => [[1, 'B']],
            '-1,1' => [[1, 'B']],
            '1,1' => [[1, 'B']],
            '-1,2' => [[1, 'B']],
            '0,2' => [[1, 'B']],
            '-2,2' => [[1, 'B']],
        ];
        $this->assertTrue($this->gameValidator->isDraw($board));

        // Add a test case where it's not a draw
        $board['0,0'] = [[0, 'A']]; // Change the Queen to an Ant
        $this->assertFalse($this->gameValidator->isDraw($board));
    }
}
