<?php

use Colin\Hive\GameLogic;
use PHPUnit\Framework\TestCase;

class WinConditionTest extends TestCase {
    private $gameLogic;

    protected function setUp(): void {
        $this->gameLogic = new GameLogic();
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
        $this->assertTrue($this->gameLogic->isQueenSurrounded($board, 0));
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
        $this->assertTrue($this->gameLogic->isDraw($board));

        // Add a test case where it's not a draw
        $board['0,0'] = [[0, 'A']]; // Change the Queen to an Ant
        $this->assertFalse($this->gameLogic->isDraw($board));
    }
}
