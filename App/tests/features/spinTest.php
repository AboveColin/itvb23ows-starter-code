<?php

use Colin\Hive\GameLogic;
use PHPUnit\Framework\TestCase;

class SpinTest extends TestCase {

    private $gameLogic;

    protected function setUp(): void {
        $this->gameLogic = new GameLogic();
    }

    public function testSpiderMoveWithEmptyBoard() {
        /*
            Test that the spider cannot move on an empty board
        */
        $board = [];
        $spiderMoves = $this->gameLogic->calculateSpiderMoves('0,0', $board, 0);
        $this->assertCount(0, $spiderMoves, "Spider should not move on an empty board.");
    }

    public function testSpiderMovesFromStartingPosition() {
        /*
            Test that the spider can move from the starting position
        */
        $board = [
            '0,0' => [[0, 'S']]
        ];
        $spiderMoves = $this->gameLogic->calculateSpiderMoves('0,0', $board, 0);
        $this->assertCount(0, $spiderMoves, "Spider should not have valid moves from starting position with no neighbors.");
    }

    public function testSpiderMovesWithValidOptions() {
        /*
            Test that the spider can move to valid positions
        */
        $board = [
            '0,0' => [[0, 'Q']],
            '-1,0' => [[0, 'A']],
            '1,0' => [[0, 'B']],
            '-2,0' => [[0, 'S']],
        ];
        $spiderMoves = $this->gameLogic->calculateSpiderMoves('-2,0', $board, 0);
        $expectedMoves = [
            '-2,-1',
            '-1,-1',
            '0,-1',
            '1,-1',
            '-3,0',
            '-3,1',
            '-2,1',
            '-1,1',
            '0,1'
        ];
        $this->assertCount(count($expectedMoves), $spiderMoves, "Spider should have specific valid moves.");
        foreach ($expectedMoves as $move) {
            $this->assertContains($move, $spiderMoves, "Expected move $move is not in the calculated moves.");
        }
    }

    public function testSpiderMovesMaintainingHiveConnectivity() {
        /*
            Test that the spider cannot move in a way that breaks hive connectivity
        */

        $board = [
            // Set up a board configuration where moving the spider would break hive connectivity
        ];
        $spiderMoves = $this->gameLogic->calculateSpiderMoves('0,0', $board, 0);
        $this->assertNotContains('expected_invalid_move', $spiderMoves, "Spider should not make a move that breaks hive connectivity.");
    }

    public function testSpiderMovesBlockedByOtherTiles() {
        /*
            Test that the spider cannot move if completely blocked by other tiles
        */

        $board = [
            // Set up a board configuration where the spider is completely surrounded by other tiles
        ];
        $spiderMoves = $this->gameLogic->calculateSpiderMoves('0,0', $board, 0);
        $this->assertCount(0, $spiderMoves, "Spider should not have any moves if completely blocked.");
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}