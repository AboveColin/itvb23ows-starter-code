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
    // bug fix #1
    $validPositions = [];

    if (count($board) == 1 && isset($board['0,0'])) {
        foreach ($offsets as $offset) {
            $newPos = $offset[0] . ',' . $offset[1];
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


// Bug fix 1
$to = calculatePositions($board, $GLOBALS['OFFSETS'], $player);
if (empty($to)) $to[] = '0,0';

$moveto = [];
foreach ($GLOBALS['OFFSETS'] as $pq) {
    foreach (array_keys($board) as $pos) {
        $pq2 = explode(',', $pos);
        // echo ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
        $moveto[] = ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
    }
}

$moveto = array_unique($moveto);
if (!count($moveto)) $moveto[] = '0,0';

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?">
    </head>
<body>
    <div class="board">
        <?php renderBoard($board); ?>
    </div>
    <hr>
    <div class="hand">White:
        <?php 
            renderHand($hand, 0); 
        ?>
    </div>
    <hr>
    <div class="hand">Black: 
        <?php 
            renderHand($hand, 1); 
        ?>
    </div>
    <hr>
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
                    foreach ($to as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="submit" value="Play">
        </form>
        <form method="post" action="move.php">
            <select name="from">
                <?php
                    displayFrom($board, $player);
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

