<?php

namespace Colin\Hive;

use mysqli;

class Database {
    private $connection;

    public function get_state() {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function insert_id() {
        return $this->connection->insert_id;
    }
    
    public function set_state($state) {
        list($hand, $board, $player) = unserialize($state);
        $_SESSION['hand'] = $hand;
        $_SESSION['board'] = $board;
        $_SESSION['player'] = $player;
    }
    
    public function __construct($host, $user, $password, $database) {
        $this->connection = new mysqli($host, $user, $password, $database);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

}