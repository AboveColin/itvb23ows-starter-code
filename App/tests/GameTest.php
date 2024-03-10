<?php 
use PHPUnit\Framework\TestCase;
use Colin\Hive\GameLogic;
use Colin\Hive\Database;
use Colin\Hive\Game;

class GameTest extends TestCase
{
    private $game;

    public function testClassConstructor()
    {
        $host = getenv('MYSQL_HOST') ?: 'localhost';
        $user = getenv('MYSQL_USER') ?: 'root';
        $password = getenv('MYSQL_PASSWORD') ?: '';
        $database = getenv('MYSQL_DB') ?: 'hive';

        $db = new Database($host, $user, $password, $database);
        $gameLogic = new GameLogic();
        $this->game = new Game($db, $gameLogic);

        $this->game->startInitGame();

    }

    public function testbug1() {
        /*
        1. De dropdown die aangeeO welke stenen een speler kan plaatsen bevat ook stenen die
        de speler niet meer heeO. Bovendien bevat de dropdown die aangeeO waar een speler
        stenen kan plaatsen ook velden waar dat niet mogelijk is, en bevat de dropdown die
        aangeeO vanaf welke posiGe een speler een steen wil verplaatsen ook velden die
        stenen van de tegenstander bevaAen.
        */
        $this->game->addToBoard('0,0', 'Q');
        $this->game->setPlayer(1);
        $this->game->addToBoard('1,0', 'A');
        $this->game->setPlayer(0);
        // $this->game->setHand([]

        


    }


    
}