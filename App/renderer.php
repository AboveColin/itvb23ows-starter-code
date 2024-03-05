<?php
// Path: App/renderer.php
    
function displayTile($tile, $pos, $min_p, $min_q, $playerClass) {
    list($p, $q) = explode(',', $pos);
    $h = count($tile);
    $left = ($p - $min_p) * 4 + ($q - $min_q) * 2;
    $top = ($q - $min_q) * 4;
    $stacked = $h > 1 ? 'stacked' : '';
    $innerTile = $tile[$h-1][1];

    
    echo "<div class=\"tile $playerClass $stacked\" style=\"left: {$left}em; top: {$top}em;\">($p,$q)<span>$innerTile</span></div>";
}



function displayError() {
    if (isset($_SESSION['error'])) {
        echo "<strong>{$_SESSION['error']}</strong>";
        unset($_SESSION['error']);
    }
}

function renderBoard($board) {
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
}

function renderHand($hand, $player) {
    echo getPlayerTiles($hand, $player);
}

function displayTurn($player) {
    echo $player == 0 ? "White" : "Black";
}

function displayPiece($hand, $player) {
    foreach ($hand[$player] as $tile => $ct) {
        if ($ct > 0) { // alleen stukken die je nog hebt in je hand
            echo "<option value=\"$tile\">$tile</option>";
        }
    }
}


function displayTo($to) {
    foreach ($to as $pos) {
        echo "<option value=\"$pos\">$pos</option>";
    }
}

function displayFrom($board) {
    foreach (array_keys($board) as $pos) {
        echo "<option value=\"$pos\">$pos</option>";
    }
}
