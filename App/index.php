<?php
session_start();
include_once 'util.php';

if (!isset($_SESSION['board'])) {
    header('Location: restart.php');
    exit(0);
}

$board = $_SESSION['board'];
$player = $_SESSION['player'];
$hand = $_SESSION['hand'];

function calculatePositions($board, $offsets) {
    $to = [];
    foreach ($offsets as $pq) {
        foreach (array_keys($board) as $pos) {
            list($p, $q) = explode(',', $pos);
            $to[] = ($pq[0] + $p) . ',' . ($pq[1] + $q);
        }
    }
    return array_unique($to);
}

$to = calculatePositions($board, $GLOBALS['OFFSETS']);
if (empty($to)) $to[] = '0,0';

function displayTile($tile, $pos, $min_p, $min_q, $playerClass) {
    list($p, $q) = explode(',', $pos);
    $h = count($tile);
    $left = ($p - $min_p) * 4 + ($q - $min_q) * 2;
    $top = ($q - $min_q) * 4;
    $stacked = $h > 1 ? 'stacked' : '';
    $innerTile = htmlspecialchars($tile[$h-1][1]);
    echo "<div class=\"tile $playerClass $stacked\" style=\"left: {$left}em; top: {$top}em;\">($p,$q)<span>$innerTile</span></div>";
}

function getPlayerTiles($hand, $player) {
    foreach ($hand[$player] as $tile => $ct) {
        for ($i = 0; $i < $ct; $i++) {
            echo '<div class="tile player' . $player . '"><span>' . htmlspecialchars($tile) . "</span></div> ";
        }
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
        <?php
        $min_p = 1000;
        $min_q = 1000;
        foreach ($board as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            $min_p = min($p, $min_p);
            $min_q = min($q, $min_q);
        }
        foreach (array_filter($board) as $pos => $tile) {
            displayTile($tile, $pos, $min_p, $min_q, 'player' . $tile[count($tile)-1][0]);
        }
        ?>
    </div>
    <div class="hand">White: <?php getPlayerTiles($hand, 0); ?></div>
    <div class="hand">Black: <?php getPlayerTiles($hand, 1); ?></div>
    <div class="turn">Turn: <?php echo $player == 0 ? "White" : "Black"; ?></div>
        <form method="post" action="play.php">
            <select name="piece">
                <?php
                    foreach ($hand[$player] as $tile => $ct) {
                        echo "<option value=\"$tile\">$tile</option>";
                    }
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
                    foreach (array_keys($board) as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <select name="to">
                <?php
                    foreach ($to as $pos) {
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
        <strong><?php if (isset($_SESSION['error'])) echo($_SESSION['error']); unset($_SESSION['error']); ?></strong>
        <ol>
            <?php
                $db = include 'database.php';
                $stmt = $db->prepare('SELECT * FROM moves WHERE game_id = ?');
                $stmt->bind_param('i', $_SESSION['game_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_array()) {
                    echo '<li>'.htmlspecialchars($row[2]).' '.htmlspecialchars($row[3]).' '.htmlspecialchars($row[4]).'</li>';
                }
            ?>
        </ol>
        <form method="post" action="undo.php">
            <input type="submit" value="Undo">
        </form>
    </body>
</html>

