<?php

use Colin\Hive\Database;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;
use PHPUnit\Framework\TestCase;

class QueenBugTest extends TestCase
{
    /*
        2. Als wit een bijenkoningin speelt op (0, 0), en zwart op (1, 0), dan zou het een legale zet
        moeten zijn dat wit zijn koningin verplaatst naar (0, 1), maar dat wordt niet toegestaan
    */
    protected $game;
    protected $db;
    protected $gameLogic;
    protected $gameValidator;
    protected $moveCalculator;

    protected function setUp(): void
    {
        /**
         * $this->db = $this->createMock(Database::class);
         *
         * // Create a mock for the statement object.
         * $stmt = $this->createMock(mysqli_stmt::class);
         * $stmt->method('bind_param')->willReturn(true);
         * $stmt->method('execute')->willReturn(true);
         * $this->db->method('prepare')->willReturn($stmt);
         *
         * // Continue with the rest of setUp method.
         * $this->gameLogic = new GameLogic();
         * $this->game = new Game($this->db, $this->gameLogic);
         *
         * $_SESSION['board'] = [
         *     '0,0' => [[0, 'Q']],
         *     '1,0' => [[1, 'A']],
         * ];
         * $_SESSION['player'] = 0;
         * $_SESSION['hand'] = [
         *     0 => ['Q' => 0],
         *     1 => ['A' => 2],
         * ];
         */
        $host = getenv('MYSQL_HOST');
        $user = getenv('MYSQL_USER');
        $password = getenv('MYSQL_PASSWORD');
        $database = getenv('MYSQL_DB');

        $this->db = new Database($host, $user, $password, $database);
        $this->gameLogic = new BaseGameLogic();
        $this->gameValidator = new GameValidator();
        $this->moveCalculator = new MoveCalculator();
        $this->game = new GameController($this->db, $this->gameLogic, $this->moveCalculator, $this->gameValidator);

        $_SESSION['board'] = [];

        $_SESSION['player'] = 0;

        $_SESSION['hand'] = [
            0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];

        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insertId();

        $_SESSION['last_move'] = 0;
    }
    
    public function testWhiteQueenTo0_1()
    {
        /*
            Test that the white queen can be moved from (0, 0) to (0, 1)
        */

        // Initial state
        $_SESSION['board'] = [
            '0,0' => [[0, 'Q']],
            '1,0' => [[1, 'A']],
        ];

        $_SESSION['player'] = 0;

        $_SESSION['hand'] = [
            0 => ['Q' => 0],
            1 => ['A' => 2],
        ];
        $_SESSION['turn'] = 0;
        
        $from = '0,0';
        $to = '0,1';

        $this->game->move($from, $to);

        // Assert the move was successful and no error was set
        $this->assertNull($_SESSION['error'], 'Move should not produce an error');
        $this->assertArrayHasKey($to, $_SESSION['board'], "Board should contain the moved piece at 0,1");
        $this->assertEquals([[0, 'Q']], $_SESSION['board'][$to], "Position 0,0 should now have the white queen");
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
