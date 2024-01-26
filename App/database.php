<?php

function get_state() {
    return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
}

function set_state($state) {
    list($hand, $board, $player) = unserialize($state);
    $_SESSION['hand'] = $hand;
    $_SESSION['board'] = $board;
    $_SESSION['player'] = $player;
}

$host = getenv('MYSQL_HOST') ?: 'localhost';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$database = getenv('MYSQL_DB') ?: 'hive';

return new mysqli($host, $user, $password, $database);

?>