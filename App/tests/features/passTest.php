<?php

use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameController;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;
use Colin\Hive\Database;
use PHPUnit\Framework\TestCase;

class PassTest extends TestCase
{
    private $db;
    private $gameLogic;
    private $game;
    private $gameValidator;
    private $moveCalculator;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class);
    
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $this->db->method('prepare')->willReturn($stmt);

        $this->gameLogic = $this->createMock(BaseGameLogic::class);
        $this->gameValidator = $this->createMock(GameValidator::class);
        $this->moveCalculator = new MoveCalculator();
        $this->game = new GameController($this->db, $this->gameLogic, $this->moveCalculator, $this->gameValidator);
    }

    public function testPassWithNoValidMoves()
    {
        // Simulate a scenario where there are no valid moves available
        $this->gameValidator->method('hasValidMoves')->willReturn(false);
        
        // Mocking session data
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 0;
        $_SESSION['player'] = 0;
        $_SESSION['turn'] = 0;

        $this->game->pass();

        // Assert that the player has been changed, indicating a successful pass
        $this->assertEquals(1, $_SESSION['player']);
    }

    public function testPassWithValidMoves()
    {
        // Simulate a scenario where there are valid moves available
        $this->gameValidator->method('hasValidMoves')->willReturn(true);
        
        // Mocking session data
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 0;
        $_SESSION['player'] = 0;
        $_SESSION['error'] = null;
        $_SESSION['turn'] = 0;

        $this->game->pass();

        // Assert that an error message was set, preventing the pass
        $this->assertEquals("Cannot pass, valid moves are available.", $_SESSION['error']);
        // Assert that the player has not been changed
        $this->assertEquals(0, $_SESSION['player']);
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
