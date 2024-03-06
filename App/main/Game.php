<?php

namespace Colin\Hive;

class Game {
    
    private $db;
    private $gameLogic;

    private $board;
    private $player;
    private $hand;
    private $game_id;
    private $last_move;
    private $error;

    public function __construct($db, $gameLogic) {
        $this->db = $db;
        $this->gameLogic = $gameLogic;
    }

    public function startInitGame() {

        $this->board = $_SESSION['board'];

        if (!isset($this->board)) {
            $this->gameLogic->restart();
        }

        $this->player = $_SESSION['player'];
        $this->hand = $_SESSION['hand'];
        $this->game_id = $_SESSION['game_id'];
        $this->last_move = $_SESSION['last_move'] ?? 0;
        $this->error = $_SESSION['error'] ?? null;
    }

    public function getBoard() {
        return $this->board;
    }

    public function getPlayer() {
        return $this->player;
    }

    public function getHand() {
        return $this->hand;
    }

    public function getGameId() {
        return $this->game_id;
    }

    public function getLastMove() {
        return $this->last_move;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = null;
    }

    public function handlePostRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch($_POST) {
                case isset($_POST['piece']) && isset($_POST['to']):
                    $this->play($_POST['piece'], $_POST['to']);
                    break;
                case isset($_POST['from']) && isset($_POST['to']):
                    $this->move($_POST['from'], $_POST['to']);
                    break;
                case isset($_POST['restart']):
                    $this->restart();
                    break;
                case isset($_POST['undo']):
                    $this->undo();
                    break;
                case isset($_POST['pass']):
                    $this->pass();
                    break;
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    public function restart() {

        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;

        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insert_id();
    }

    public function game() {
        $stmt = $this->db->prepare('SELECT * FROM moves WHERE game_id = ?');
        $stmt->bind_param('i', $_SESSION['game_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_array()) {
            if ($row[2] == 'play' || $row[2] == 'move') {
                echo '<li>'.htmlspecialchars($row[2]).' '.htmlspecialchars($row[3]).' '.htmlspecialchars($row[4]).'</li>';
            } else {
                echo '<li>'.htmlspecialchars($row[2]) .'</li>';
            }
        } 
    }

    public function pass() {
        $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "pass", null, null, ?, ?)');
        $state = $this->db->get_state();
        $stmt->bind_param('iis', $_SESSION['game_id'], $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    public function undo() {
        $stmt = $this->db->prepare('SELECT * FROM moves WHERE id = '.$_SESSION['last_move']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_array();
        $_SESSION['last_move'] = $result[5];
        $this->db->set_state($result[6]);
    }

    public function play($piece, $to) {

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];

        if (!$hand[$piece])
            $_SESSION['error'] = "Player does not have tile";
        elseif (isset($board[$to]))
            $_SESSION['error'] = 'Board position is not empty';
        elseif (array_sum($hand) == 8 && $piece != 'Q') #bug fix 3
            $_SESSION['error'] = 'Must play queen bee by the fourth move'; #bug fix 3
        elseif (count($board) && !$this->gameLogic->hasNeighBour($to, $board))
            $_SESSION['error'] = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->gameLogic->neighboursAreSameColor($player, $to, $board))
            $_SESSION['error'] = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            $_SESSION['error'] = 'Must play queen bee';
        } else {
            $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "play", ?, ?, ?, ?)');
            $state = $this->db->get_state();
            $stmt->bind_param('issis', $_SESSION['game_id'], $piece, $to, $_SESSION['last_move'], $state);
            $stmt->execute();
            $_SESSION['last_move'] = $this->db->insert_id();
        }
    }

    public function move($from, $to) {
        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];
        unset($_SESSION['error']);
        
        if (!isset($board[$from]))
            $_SESSION['error'] = 'Board position is empty';
        elseif ($board[$from][count($board[$from])-1][0] != $player)
            $_SESSION['error'] = "Tile is not owned by player";
        elseif ($hand['Q'])
            $_SESSION['error'] = "Queen bee is not played";
        else {
            // doe een move code.
            $tile = array_pop($board[$from]);
            if (!$this->gameLogic->hasNeighBour($to, $board))
                $_SESSION['error'] = "Move would split hive";
            else {
                $all = array_keys($board);
                $queue = [array_shift($all)];
                while ($queue) {
                    $next = explode(',', array_shift($queue));
                    foreach ($this->gameLogic->getOffsets() as $pq) {
                        list($p, $q) = $pq;
                        $p += $next[0];
                        $q += $next[1];
                        if (in_array("$p,$q", $all)) {
                            $queue[] = "$p,$q";
                            $all = array_diff($all, ["$p,$q"]);
                        }
                    }
                }
                if ($all) {
                    $_SESSION['error'] = "Move would split hive";
                } else {
                    if ($from == $to) $_SESSION['error'] = 'Tile must move';
                    elseif (isset($board[$to]) && $tile[1] != "B") $_SESSION['error'] = 'Tile not empty';
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!$this->gameLogic->slide($board, $from, $to))
                            $_SESSION['error'] = 'Tile must slide';
                    }
                }
            }
            // bug fix 4
            if (!isset($_SESSION['error'])) {
                if (empty($board[$from])) { // Check of de from positie leeg is en fix hiermee de bug
                    unset($board[$from]); 
                }
                if (isset($board[$to])) array_push($board[$to], $tile);
                else $board[$to] = [$tile];
                $_SESSION['player'] = 1 - $_SESSION['player'];
                $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "move", ?, ?, ?, ?)');
                $state = $this->db->get_state();
                $stmt->bind_param('issis', $_SESSION['game_id'], $from, $to, $_SESSION['last_move'], $state);
                $stmt->execute();
                $_SESSION['last_move'] = $this->db->insert_id();
            } else {
                // als er wel een error is, zet de tile terug naar originele positie
                if (isset($board[$from])) array_push($board[$from], $tile);
                else $board[$from] = [$tile];
            }
            $_SESSION['board'] = $board;
        }
    }

    
}