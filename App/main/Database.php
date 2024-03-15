<?php

namespace Colin\Hive;

use mysqli;

class Database {
    private $connection;

    public function getState() {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function insertId() {
        return $this->connection->insert_id;
    }
    
    public function setState($state) {
        list($hand, $board, $player) = unserialize($state);
        $_SESSION['hand'] = $hand;
        $_SESSION['board'] = $board;
        $_SESSION['player'] = $player;
    }
    
    public function __construct($host, $user, $password, $database, $port = 3306) {
        $this->connection = new mysqli($host, $user, $password, $database, $port);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

}

?>
