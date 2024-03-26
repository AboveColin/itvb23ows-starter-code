<?php


use Colin\Hive\GameRenderer;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\GameValidator;
use Colin\Hive\MoveCalculator;
use Colin\Hive\Database;
use PHPUnit\Framework\TestCase;

class DropdownBugTest extends TestCase
{
    private $game;
    private $gameRenderer;
    private $gameLogic;
    private $moveCalculator;
    private $gameValidator;
    /*
        1. De dropdown die aangeeft welke stenen een speler kan plaatsen bevat ook stenen die
        de speler niet meer heeft. Bovendien bevat de dropdown die aangeeft waar een speler
        stenen kan plaatsen ook velden waar dat niet mogelijk is, en bevat de dropdown die
        aangeeft vanaf welke positie een speler een steen wil verplaatsen ook velden die
        stenen van de tegenstander bevaAen.
    */
    protected function setUp(): void
    {
        $dbMock = $this->createMock(Database::class);
        $this->gameLogic = new BaseGameLogic();
        $this->gameValidator = new GameValidator();
        $this->moveCalculator = new MoveCalculator();
        $this->game = new GameController($dbMock, $this->gameLogic, $this->moveCalculator, $this->gameValidator);
        $this->gameRenderer = new GameRenderer();
    }
    
    public function testDisplayPiece()
    {
        /*
            Test that the displayPiece method only shows the pieces that the player has in their hand
        */
        $hand = [0 => ["Q" => 1, "B" => 2], 1 => ["Q" => 0, "B" => 1]];
        ob_start();
        $this->gameRenderer->displayPiece($hand, 0);
        $output = ob_get_clean();
        $this->assertStringContainsString('<option value="Q" >Q</option>', $output);
        $this->assertStringContainsString('<option value="B" >B</option>', $output);
        $this->assertEquals(2, substr_count($output, '<option value='));
    }

    public function testCalculatePositions()
    {
        /*
            Test that the calculatePositions method only returns valid positions
        */
        $board = ['0,0' => [[0, 'Q']]]; // state met de queen op 0,0
        $player = 0;
        $validPositions = $this->moveCalculator->calculatePositions($board, $this->gameLogic->getOffsets(), $player);
        
        $expectedPositions = ['0,1', '1,0', '-1,1', '0,-1', '-1,0', '1,-1'];
        foreach ($expectedPositions as $position) {
            $this->assertContains($position, $validPositions);
        }
    }

    public function testDisplayFrom()
    {
        /*
            Test that the displayFrom method only shows the positions that the player has pieces on
        */
        $board = ['0,0' => [[0, 'Q']], '1,1' => [[1, 'B']]];
        ob_start();
        $this->gameRenderer->displayFrom($board, 0);
        $output = ob_get_clean();
        $this->assertStringContainsString('<option value="0,0">0,0</option>', $output);
        $this->assertStringNotContainsString('<option value="1,1">1,1</option>', $output);
    }

    protected function tearDown(): void
    {
        session_unset();
    }
}
