<?php

use PHPUnit\Framework\TestCase;
use Colin\Hive\Database;
use Colin\Hive\Game;
use Colin\Hive\GameLogic;

class Testbug4 extends TestCase {
    /*
        4. Als je een steen verplaatst, kan je daarna geen nieuwe steen spelen op het oude veld,
        ook als dat volgens de regels wel zou mogen.
    */
    private $game;
    private $db;
    private $gameLogic;

    protected function setUp(): void {
        // $this->db = $this->createMock(Database::class); 
    
        // $stmt = $this->createMock(mysqli_stmt::class);
        // $stmt->method('bind_param')->willReturn(true);
        // $stmt->method('execute')->willReturn(true);
        // $this->db->method('prepare')->willReturn($stmt);
    
        // $this->gameLogic = new GameLogic();
        // $this->game = new Game($this->db, $this->gameLogic);
    
        // $_SESSION['board'] = [];
        // $_SESSION['player'] = 0;
        // $_SESSION['hand'] = [
        //     0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
        //     1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        // ];
        $host = 'localhost';
        $user = 'root';
        $password = '123456';
        $database = 'hive';

        $this->db = new Database($host, $user, $password, $database);
        $this->gameLogic = new GameLogic();
        $this->game = new Game($this->db, $this->gameLogic);

        $_SESSION['board'] = [];

        $_SESSION['player'] = 0;

        $_SESSION['hand'] = [
            0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];

        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insert_id();

        $_SESSION['last_move'] = 0;
    }
    

    public function test1() {
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

        // Simulate moving the Queen bee
        $this->game->move('0,0', '1,0');
        $this->assertNull($_SESSION['error'], "No error expected when moving the Queen bee");

        // Attempt to play a new piece on the old position of the Queen bee
        $this->game->play('A', '0,0');

        // Assert that playing a new piece on the old position is successful
        $this->assertArrayHasKey('0,0', $_SESSION['board'], "Board should contain the new piece at 0,0");
        $this->assertNull($_SESSION['error'], "Expected to successfully place a new piece where the Queen bee was previously");
        
    }
}