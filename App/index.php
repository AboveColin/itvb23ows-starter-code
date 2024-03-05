<?php
session_start();
include_once 'util.php';
include_once 'renderer.php';

if (!isset($_SESSION['board'])) {
    header('Location: restart.php');
    exit(0);
}

$board = $_SESSION['board'];
$player = $_SESSION['player'];
$hand = $_SESSION['hand'];

function calculatePositions($board, $offsets, $player) {
    $validPositions = [];

    // als het de eerste zet is, dan is alleen 0,0 een geldige zet
    if (count($board) == 1) {
        $firstPiecePosition = key($board);
        list($p, $q) = explode(',', $firstPiecePosition);
        foreach ($offsets as $offset) {
            $newPos = ($p + $offset[0]) . ',' . ($q + $offset[1]);
            // Since it's the second move, all adjacent positions except where another piece exists are valid
            if (!array_key_exists($newPos, $board)) {
                $validPositions[] = $newPos;
            }
        }
    } else {
        // For moves after the second, calculate valid positions based on existing logic
        foreach (array_keys($board) as $pos) {
            list($p, $q) = explode(',', $pos);
            foreach ($offsets as $offset) {
                $newPos = ($p + $offset[0]) . ',' . ($q + $offset[1]);
                if (!array_key_exists($newPos, $board) && isValidPosition($newPos, $board, $player)) {
                    $validPositions[] = $newPos;
                }
            }
        }
    }

    return array_unique($validPositions);
}


$to = calculatePositions($board, $GLOBALS['OFFSETS'], $player);
?> <script> <?php echo "console.log('".json_encode($to)."')"; ?> </script> <?php
if (empty($to)) $to[] = '0,0';

function getPlayerTiles($hand, $player) {
    foreach ($hand[$player] as $tile => $ct) {
        for ($i = 0; $i < $ct; $i++) {
            echo '<div class="tile player' . $player . '"><span>' . $tile . "</span></div> ";
        }
    }
}


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
<!DOCTYPE html>
<html>
<head>
    <title>Hive</title>
    <link rel="stylesheet" href="/css/styling.css">
    </head>
<body>
    <div class="board">
        <?php renderBoard($board); ?>
    </div>
    <div class="hand">White:
        <?php 
            renderHand($hand, 0); 
        ?>
    </div>
    <div class="hand">Black: 
        <?php 
            renderHand($hand, 1); 
        ?>
    </div>
    <div class="turn">Turn: 
        <?php 
            displayTurn($player); 
        ?>
    </div>
        <form method="post" action="play.php">
            <select name="piece">
                <?php
                    displayPiece($hand, $player);
                ?>
            </select>
            <select name="to">
                <?php
                    displayTo($to);
                ?>
            </select>
            <input type="submit" value="Play">
        </form>
        <form method="post" action="move.php">
            <select name="from">
                <?php
                    displayFrom($board);
                ?>
            </select>
            <select name="to">
                <?php
                    displayTo($to);
                ?>
            </select>
            <input type="submit" value="Move">
        </form>
        <form method="post" action="pass.php">
            <input type="submit" value="Pass">
        </form>
        <form method="post" action="restart.php">
            <input type="submit" value="Restart">
        </form>
        <strong>
            <?php
                displayError()
            ?>
        </strong>
        <ol>
            <?php
                game();
            ?>
        </ol>
        <form method="post" action="undo.php">
            <input type="submit" value="Undo">
        </form>
    </body>
</html>

