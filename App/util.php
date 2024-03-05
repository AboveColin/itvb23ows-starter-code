<?php
// Path: App/util.php

$GLOBALS['OFFSETS'] = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

function isNeighbour($a, $b) {
    $a = explode(',', $a);
    $b = explode(',', $b);
    if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1) return true;
    if ($a[1] == $b[1] && abs($a[0] - $b[0]) == 1) return true;
    if ($a[0] + $a[1] == $b[0] + $b[1]) return true;
    return false;
}

function hasNeighBour($a, $board) {
    foreach (array_keys($board) as $b) {
        if (isNeighbour($a, $b)) return true;
    }
}

function neighboursAreSameColor($player, $a, $board) {
    foreach ($board as $b => $st) {
        if (!$st) continue;
        $c = $st[count($st) - 1][0];
        if ($c != $player && isNeighbour($a, $b)) return false;
    }
    return true;
}

function len($tile) {
    return $tile ? count($tile) : 0;
}



function slide($board, $from, $to) {
    if (!hasNeighBour($to, $board)) return false;
    if (!isNeighbour($from, $to)) return false;
    $b = explode(',', $to);
    $common = [];
    foreach ($GLOBALS['OFFSETS'] as $pq) {
        $p = $b[0] + $pq[0];
        $q = $b[1] + $pq[1];
        if (isNeighbour($from, "$p,$q")) $common[] = "$p,$q";
    }

    if (count($common) < 2) return false;

    $lenCommon0 = isset($board[$common[0]]) ? len($board[$common[0]]) : 0;
    $lenCommon1 = isset($board[$common[1]]) ? len($board[$common[1]]) : 0;

    $lenFrom = isset($board[$from]) ? len($board[$from]) : 0;
    $lenTo = isset($board[$to]) ? len($board[$to]) : 0;

    return min($lenCommon0, $lenCommon1) <= max($lenFrom, $lenTo);
}

function isValidPosition($position, $board, $player) {
    if (hasNeighBour($position, $board) && neighboursAreSameColor($player, $position, $board)) {
        return true;
    }
    return false;
}

?>