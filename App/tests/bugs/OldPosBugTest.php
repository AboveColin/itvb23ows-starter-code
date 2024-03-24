<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\Database;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;

class OldPosBugTest extends TestCase {
    /*
        4. Als je een steen verplaatst, kan je daarna geen nieuwe steen spelen op het oude veld,
        ook als dat volgens de regels wel zou mogen.
    */
    private $game;
    private $db;
    private $gameLogic;
    private $gameValidator;
    private $moveCalculator;

    protected function setUp(): void {
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
    
    public function testPieceCanBePlayedOnOldPositionOfMovedPiece() {
        /*
            Test that a piece can be played on the old position of a moved piece
        */

        $_SESSION['board'] = [
            '0,0' => [[0, 'Q']],
        ];
        $_SESSION['hand'] = [
            0 => ["Q" => 0, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];
        $_SESSION['player'] = 0;
        $_SESSION['game_id'] = 1;
        $_SESSION['last_move'] = 1;
        $_SESSION['turn'] = 0;

        // Simulate moving the Queen bee
        $this->game->move('0,0', '1,0');
        $this->assertNull($_SESSION['error'],
            "No error expected when moving the Queen bee");

        // Attempt to play a new piece on the old position of the Queen bee
        $this->game->play('A', '0,0');

        // Assert that playing a new piece on the old position is successful
        $this->assertArrayHasKey('0,0', $_SESSION['board'],
            "Board should contain the new piece at 0,0");
        $this->assertNull($_SESSION['error'],
            "Expected to successfully place a new piece where the Queen bee was previously");
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
