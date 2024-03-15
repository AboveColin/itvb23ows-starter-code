<?php
use PHPUnit\Framework\TestCase;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;
use Colin\Hive\Database;

class UndoBugTest extends TestCase {
    /*
    5. De undo-functionaliteit werkt nog niet goed. De oude zeAen worden nog niet
        verwijderd, en de toestand van het bord wordt niet altijd goed hersteld. Bovendien
        kan je ook undo’en als er nog geen zetten gedaan zijn, en dan lijkt het erop dat je een
        toestand uit een ander spel ziet.
    */
    private $game;
    private $db;
    private $gameLogic;
    private $gameValidator;
    private $moveCalculator;

    private function insertIntodb() {
        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insertId();
        return $_SESSION['game_id'];
    }

    protected function setUp(): void
    {
        $host = 'localhost';
        $user = 'root';
        $password = '123456';
        $database = 'hive';

        $this->db = new Database($host, $user, $password, $database);
        $this->gameLogic = new BaseGameLogic();
        $this->moveCalculator = new MoveCalculator();
        $this->game = new GameController($this->db, $this->gameLogic, $this->moveCalculator, $this->gameValidator);

        $_SESSION['board'] = [];

        $_SESSION['player'] = 0;

        $_SESSION['hand'] = [
            0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];

        $_SESSION['game_id'] = $this->insertIntodb();

        $_SESSION['last_move'] = 0;
    }

    

    public function testUndoWithoutMoves() {
        /*
            Test that the undo function produces an error when there are no moves to undo
        */
        $_SESSION['game_id'] = $this->insertIntodb();

        $_SESSION['last_move'] = 0;
        $_SESSION['board'] = [];
        $_SESSION['player'] = 0;
        $_SESSION['hand'] = [
            0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];
        
        $this->game->undo();
        $this->assertEquals('No moves to undo', $_SESSION['error']);
    }

    public function TestundoWithMoves() {
        /*
            Test that the undo function works correctly when there is a move to undo
            and the board is restored to the correct state
        */
        $_SESSION['board'] = [
            '0,0' => [[0, 'Q']],
            '1,0' => [[1, 'A']],
        ];
        $_SESSION['player'] = 0;
        $_SESSION['hand'] = [
            0 => ['Q' => 0],
            1 => ['A' => 2],
        ];

        // Create a new game
        $_SESSION['game_id'] = $this->insertIntodb();
        $_SESSION['last_move'] = 1;

        // Simulate moving the white queen
        $this->game->move('0,0', '0,1');

        // Undo the move
        $this->game->undo();
        $this->assertNull($_SESSION['error'], 'Undo should not produce an error');
        $this->assertArrayHasKey('0,0', $_SESSION['board'], "Board should contain the moved piece at 0,0");
        $this->assertEquals([[0, 'Q']], $_SESSION['board']['0,0'], "Position 0,0 should now have the white queen");
    }

    protected function tearDown(): void
    {
        session_unset();
    }

}
