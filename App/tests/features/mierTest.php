<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;

class MierTest extends TestCase
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
    
    public function testSoldierAntCanMoveUnlimited()
    {
        /*
            Test that the Soldier Ant can move to any empty field
        */
        $board = [
            '0,0' => [[0, 'A']], // Soldier Ant at 0,0
            '0,1' => [[1, 'Q']], // Non-empty field next to Soldier Ant
            '0,2' => [], // Another empty field next to the previous non-empty field
            '1,0' => [],
            '-1,1' => [],
            '-1,2' => []
        ];

        $validPositions = $this->moveCalculator->calculateAntMoves('0,0', $board, 0);

        // Check if the Soldier Ant can move to a further locations
        $this->assertContains('2,0', $validPositions);
    }

    public function testSoldierAntCannotMoveToSamePosition()
    {
        /*
            Test that the Soldier Ant cannot move to the same position
        */
        $board = [
            '0,0' => [[0, 'A']], // Soldier Ant at 0,0
            '0,1' => [[1, 'Q']], // Non-empty field next to Soldier Ant
        ];

        $validPositions = $this->moveCalculator->calculateAntMoves('0,0', $board, 0);

        $this->assertNotContains('0,0', $validPositions);
    }
    public function testSoldierAntMovesOnlyToEmptyFields()
    {
        /*
            Test that the Soldier Ant can only move to empty fields
        */
        $board = [
            '0,0' => [[0, 'A']], // Soldier Ant at 0,0
            '0,1' => [[1, 'Q']], // Non-empty field
            '0,2' => [], // Another empty field next to the previous non-empty field
            '1,0' => [],
            '-1,1' => [],
            '-1,2' => []
        ];

        $validPositions = $this->moveCalculator->calculateAntMoves('0,0', $board, 0);

        // Can move to '2,0' but not '0,1'
        $this->assertContains('2,0', $validPositions);
        $this->assertNotContains('0,1', $validPositions);
    }
    
    protected function tearDown(): void
    {
        session_unset();
    }
}
