<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\Database;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;

class FourthMoveBugTest extends TestCase {
    /*
        3. Als wit drie stenen plaatst die geen bijenkoningin zijn, mag hij als vierde zet helemaal
        geen steen spelen. Het spel loopt dan dus vast.
    */
    private $game;
    private $db;
    private $gameLogic;
    private $gameValidator;
    private $moveCalculator;

    protected function setUp(): void {
        $this->db = $this->createMock(Database::class);
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
        /**
         * $host = 'localhost';
         * $user = 'root';
         * $password = '123456';
         * $database = 'hive';
         *
         * $this->db = new Database($host, $user, $password, $database);
         * $this->gameLogic = new GameLogic();
         * $this->game = new Game($this->db, $this->gameLogic);
         *
         * $_SESSION['board'] = [];
         *
         * $_SESSION['player'] = 0;
         *
         * $_SESSION['hand'] = [
         *   0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
         *   1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
         * ];
         *
         * $this->db->prepare('INSERT INTO games VALUES ()')->execute();
         * $_SESSION['game_id'] = $this->db->insertId();
         * 
         * $_SESSION['last_move'] = 0;
         */
        
    }

    public function testQueenBeemustBePlayedByTheFourthMove() {
        /*
            Test that the Queen Bee must be played by the fourth move
        */

        // Simulate game state just before the 4th move
        $_SESSION['board'] = [
            '0,0' => [[0, 'B']],
            '0,1' => [[1, 'A']],
            '-1,0' => [[0, 'B']],
            '1,1' => [[1, 'B']],
            '0,-1' => [[0, 'A']],
            '0,2' => [[1, 'B']],
        ];
        $_SESSION['hand'] = [
            0 => ["Q" => 1, "B" => 0, "S" => 2, "A" => 2, "G" => 3],
            1 => ["Q" => 1, "B" => 0, "S" => 2, "A" => 2, "G" => 3]
        ];
        $_SESSION['player'] = 0;
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 3;
    
        // Attempt to play a piece that is not the Queen Bee on the fourth move
        $this->game->play('A', '-1,-1');

        $this->assertEquals('Must play queen bee by the fourth move', $_SESSION['error']);
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
