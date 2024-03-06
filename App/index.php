<?php
namespace Colin\Hive;
session_start();

require 'vendor/autoload.php';
use Colin\Hive\Database;
use Colin\Hive\Game;
use Colin\Hive\GameLogic;
use Colin\Hive\GameRenderer;

$host = getenv('MYSQL_HOST') ?: 'localhost';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$database = getenv('MYSQL_DB') ?: 'hive';

$db = new Database($host, $user, $password, $database);
$gameLogic = new GameLogic();
$game = new Game($db, $gameLogic);
$gameRenderer = new GameRenderer();

$game->startInitGame();

$game->handlePostRequests();

if (!isset($_SESSION['board'])) {
    $game->restart();
    exit(0);
}

$board = $game->getBoard();
$player = $game->getPlayer();
$hand = $game->getHand();

// Bug fix 1
$to = $gameLogic->calculatePositions($board, $gameLogic->getOffsets(), $player);


$moveto = [];
foreach ($gameLogic->getOffsets() as $pq) {
    foreach (array_keys($board) as $pos) {
        $pq2 = explode(',', $pos);
        $moveto[] = ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
    }
}

$moveto = array_unique($moveto);
if (!count($moveto)) $moveto[] = '0,0';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Hive</title>
    <link rel="stylesheet" href="/css/styling.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?">
    </head>
<body>
    <div class="board">
        <?php $gameRenderer->renderBoard($board); ?>
    </div>
    <hr>
    <div class="hand">White:
        <?php 
            $gameRenderer->renderHand($hand, 0); 
        ?>
    </div>
    <hr>
    <div class="hand">Black: 
        <?php 
            $gameRenderer->renderHand($hand, 1); 
        ?>
    </div>
    <hr>
    <div class="turn">Turn: 
        <?php 
            $gameRenderer->displayTurn($player); 
        ?>
    </div>
        <form method="post" action="index.php" name="GameAction">
            <select name="piece">
                <?php
                    $gameRenderer->displayPiece($hand, $player);
                ?>
            </select>
            <select name="to">
                <?php
                    foreach ($to as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="submit" value="Play">
        </form>
        
        <form method="post" action="index.php">
            <select name="from">
                <?php
                    $gameRenderer->displayFrom($board, $player);
                ?>
            </select>
            <select name="to">
                <?php
                    foreach ($moveto as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="submit" value="Move">
        </form>
        <form method="post" action="index.php">
            <input type="hidden" name="pass" value="true">
            <input type="submit" value="Pass">
        </form>
        <form method="post" action="index.php">
            <input type="hidden" name="restart" value="true">
            <input type="submit" value="Restart">
        </form>
        <strong>
            <?php
                $gameRenderer->displayError();
            ?>
        </strong>
        <ol>
            <?php
                $game->game();
            ?>
        </ol>
        <form method="post" action="index.php">
            <input type="hidden" name="undo" value="true">
            <input type="submit" value="Undo">
        </form>
    </body>
</html>

<?php
exit();




function game() {
    $db = include 'database.php';
    $stmt = $db->prepare('SELECT * FROM moves WHERE game_id = ?');
    $stmt->bind_param('i', $_SESSION['game_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_array()) {
        echo '<li>'.htmlspecialchars($row[2]).' '.htmlspecialchars($row[3]).' '.htmlspecialchars($row[4]).'</li>';
    }
}

?>
