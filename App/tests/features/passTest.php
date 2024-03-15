<?php

use Colin\Hive\Game;
use Colin\Hive\GameLogic;
use Colin\Hive\Database;
use PHPUnit\Framework\TestCase;

class PassTest extends TestCase
{
    private $db;
    private $gameLogic;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class); 
    
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $this->db->method('prepare')->willReturn($stmt);

        $this->gameLogic = $this->createMock(GameLogic::class);
    }

    public function testPassWithNoValidMoves()
    {
        // Simulate a scenario where there are no valid moves available
        $this->gameLogic->method('hasValidMoves')->willReturn(false);

        $game = new Game($this->db, $this->gameLogic);
        
        // Mocking session data
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 0;
        $_SESSION['player'] = 0;
        $_SESSION['turn'] = 0;

        $game->pass();

        // Assert that the player has been changed, indicating a successful pass
        $this->assertEquals(1, $_SESSION['player']);
    }

    public function testPassWithValidMoves()
    {
        // Simulate a scenario where there are valid moves available
        $this->gameLogic->method('hasValidMoves')->willReturn(true);

        $game = new Game($this->db, $this->gameLogic);
        
        // Mocking session data
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 0;
        $_SESSION['player'] = 0;
        $_SESSION['error'] = null;
        $_SESSION['turn'] = 0;

        $game->pass();

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


?>