<?php
namespace Colin\Hive;
session_start();

require_once 'vendor/autoload.php';
use Colin\Hive\Database;
use Colin\Hive\GameController;
use Colin\Hive\BaseGameLogic;
use Colin\Hive\MoveCalculator;
use Colin\Hive\GameValidator;
use Colin\Hive\GameRenderer;

$host = getenv('MYSQL_HOST') ?: '172.99.0.2';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$database = getenv('MYSQL_DB') ?: 'hive';

$db = new Database($host, $user, $password, $database);
$gameLogic = new BaseGameLogic();
$moveCalculator = new MoveCalculator();
$gameValidator = new GameValidator();
$game = new GameController($db, $gameLogic, $moveCalculator, $gameValidator);
$gameRenderer = new GameRenderer();


if (!isset($_SESSION['board'])) {
    $game->restart();
}

$game->startInitGame();

$game->handlePostRequests();

$board = $game->getBoard();
$player = $game->getPlayer();
$hand = $game->getHand();

// Bug fix 1
$to = $moveCalculator->calculatePositions($board, $gameLogic->getOffsets(), $player);


$moveto = [];
foreach ($gameLogic->getOffsets() as $pq) {
    foreach (array_keys($board) as $pos) {
        $pq2 = explode(',', $pos);
        $moveto[] = ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
    }
}

$moveto = array_unique($moveto);
if (!count($moveto)) {
    $moveto[] = '0,0';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hive</title>
    <link rel="stylesheet" href="/css/styling.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?">
    </head>
<body>
    <?php $gameRenderer->renderOutcome(); ?>
        <div class="board">
            <div id="loadingScreen">
                Processing AI Move...
            </div>
            <?php $gameRenderer->renderGhostTiles($board, $gameLogic->getOffsets()) ?>
            <?php $gameRenderer->renderBoard($board); ?>
        </div>
    <div class="hand">White:
        <?php
            $gameRenderer->renderHand($hand, 0);
        ?>
    </div>
    <div class="hand">Black:
        <?php
            $gameRenderer->renderHand($hand, 1);
        ?>
    </div>
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
            <select name="from" id="from">
                <?php
                    $gameRenderer->displayFrom($board, $player);
                ?>
            </select>
            <select name="to" id="fromTo">
                <?php
                    foreach ($moveto as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="submit" value="Move">
        </form>
        
        <?php
            $gameRenderer->displayError();
        ?>
        <hr>
        <div class="actionButtons">
            <form method="post" action="index.php">
                <input area-hidden="true" name="AIMove" value="true" class="hidden">
                <input type="submit" value="AIMove">
            </form>
            <form method="post" action="index.php">
                <input area-hidden="true" name="pass" value="true" class="hidden">
                <input type="submit" value="Pass">
            </form>
            <form method="post" action="index.php">
                <input area-hidden="true" name="restart" value="true" class="hidden">
                <input type="submit" value="Restart">
            </form>
            <form method="post" action="index.php">
                <input area-hidden="true" name="undo" value="true" class="hidden">
                <input type="submit" value="Undo">
            </form>
        </div>
        <div class="log">
            <code>
                <ol>
                    <?php
                        $gameRenderer->displayLog($db);
                    ?>
                </ol>
            </code>
        </div>
    </body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var currentPlayer = <?php echo json_encode($player); ?>;
            var playerClass = currentPlayer === 1 ? 'player1' : 'player0';

            const AIMoveButton = document.querySelector('input[value="AIMove"]');
            AIMoveButton.addEventListener('click', function() {
                document.getElementById('loadingScreen').style.display = 'flex';
            });

            const fromSelect = document.querySelector('#from');
            const tiles = document.querySelectorAll(`.tile.${playerClass}`);

            const removeExistingHighlights = () => {
                tiles.forEach(
                    tile => tile.style.border = tile.style.border === '2px solid red' ? '' : tile.style.border
                    );
            };

            const highlightTile = (position) => {
                const selectedTile = document.querySelector(`.tile[data-position="${position}"].${playerClass}`);
                if (selectedTile) {
                    selectedTile.style.border = '2px solid red';
                }
            };

            fromSelect.addEventListener('change', function() {
                removeExistingHighlights();
                highlightTile(this.value);
            });

            tiles.forEach(tile => {
                tile.addEventListener('click', function() {
                    if (!this.classList.contains(playerClass)) {
                        return;
                    }
                    fromSelect.value = this.dataset.position;
                    removeExistingHighlights();
                    this.style.border = '2px solid red';
                });
            });
        });
    </script>

</html>
