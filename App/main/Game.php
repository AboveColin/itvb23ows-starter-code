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

    public function setBoard($board) {
        $this->board = $board;
    }

    public function addToBoard($to, $piece) {
        $this->board[$to] = $piece;
    }

    public function getPlayer() {
        return $this->player;
    }

    public function setPlayer($player) {
        $this->player = $player;
    }

    public function getHand() {
        return $this->hand;
    }

    public function setHand($hand, $player) {
        $this->hand[$player] = $hand;
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

    
    public function pass() {
        $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "pass", null, null, ?, ?)');
        $state = $this->db->get_state();
        $stmt->bind_param('iis', $_SESSION['game_id'], $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    // bug fix 5
    public function undo() {
        // Fetch the last move from the database
        $stmt = $this->db->prepare('SELECT * FROM moves WHERE game_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('i', $_SESSION['game_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $_SESSION['error'] = "No moves to undo";
            return;
        }
    
        if ($row = $result->fetch_assoc()) {
            // Set the game state to the state before the last move
            $state = unserialize($row['state']);
            list($hand, $board, $player) = $state;
    
            // Update the game state in the session
            $_SESSION['hand'] = $hand;
            $_SESSION['board'] = $board;
            $_SESSION['player'] = $player;
            $_SESSION['last_move'] = $row['previous_id'];
    
            // Delete the last move from the database as it is being undone
            $stmt = $this->db->prepare('DELETE FROM moves WHERE id = ?');
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();

            // set player to the previous player
            $_SESSION['player'] = 1 - $_SESSION['player'];
        }
    }
    

    function checkifPlayerplayedQueen($player) {
        $hand = $_SESSION['hand'][$player];
        if ($hand['Q'] == 0) {
            return true;
        }
        return false;
    }

    public function insertPlay($piece, $to) {
        $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "play", ?, ?, ?, ?)');
        $state = $this->db->get_state();
        $stmt->bind_param('issis', $_SESSION['game_id'], $piece, $to, $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
    }

    public function play($piece, $to) {

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];

        if (!$hand[$piece])
            $_SESSION['error'] = "Player does not have tile";
        elseif (isset($board[$to]))
            $_SESSION['error'] = 'Board position is not empty';
        
        // Must play queen bee by the fourth move bug fix
        elseif (array_sum($hand) == 8 && $hand['Q']) {
            if ($piece != 'Q') {
                $_SESSION['error'] = 'Must play queen bee by the fourth move'; #bug fix 3
            } else {
                $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
                $_SESSION['hand'][$player][$piece]--;
                $_SESSION['player'] = 1 - $_SESSION['player'];
                $this->insertPlay($piece, $to);
            }
        }
           
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
            $this->insertPlay($piece, $to);
        }
    }

    private function insertMove($from, $to, $tile) {
        $gameId = $_SESSION['game_id'];
        $previousId = $_SESSION['last_move'];
        $state = $this->db->get_state();
        
        $query = 'INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "move", ?, ?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('issis', $gameId, $from, $to, $previousId, $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
    }

    public function isHiveConnected($board) {
        if (count($board) <= 1) {
            // The hive is connected if there's only one tile in the board.
            return true;
        }
    
        $visited = [];
        $start = array_key_first($board); // Starting from the first tile in the board.
        $this->dfs($start, $board, $visited); // Depth-first search to visit all connected tiles.
    
        // If the number of visited nodes equals the number of tiles in the board, the hive is connected.
        return count($visited) === count($board);
    }

    private function dfs($pos, $board, &$visited) {
        // Depth-first search to visit all connected tiles.
        if (array_key_exists($pos, $visited)) {
            // Already visited this position.
            return;
        }

        $visited[$pos] = true; // Mark as visited.

        // Recursively visit all neighbors.
        $neighbors = $this->getNeighbors($pos);
        foreach ($neighbors as $neighbor) {
            if (isset($board[$neighbor]) && !isset($visited[$neighbor])) {
                $this->dfs($neighbor, $board, $visited);
            }
        }
    }

    private function getNeighbors($pos) {
        // use offsets from Gamelogic
        $offsets = $this->gameLogic->getOffsets();
        list($x, $y) = explode(',', $pos);
        $neighbors = [];
        foreach ($offsets as $offset) {
            $neighbors[] = ($x + $offset[0]) . ',' . ($y + $offset[1]);
        }
        return $neighbors;
    }

    
    public function move($from, $to) {
        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];
        
        // Clear any previous error
        unset($_SESSION['error']);
        
        // Validations for the move
        if (!isset($board[$from])) {
            $_SESSION['error'] = 'Board position is empty';
            return;
        }
        if ($board[$from][count($board[$from])-1][0] != $player) {
            $_SESSION['error'] = "Tile is not owned by player";
            return;
        }
        if ($hand['Q']) {
            $_SESSION['error'] = "Queen bee is not played";
            return;
        }
        
        // Simulate the move and check if it's valid
        $tile = array_pop($board[$from]);
        $this->board[$to][] = $tile;

        

        // grasshopper move validation
        if ($tile[1] === "G" && !$this->gameLogic->isValidGrasshopperMove($from, $to, $board)) {
            $_SESSION['error'] = "Invalid grasshopper move";
            $board[$from][] = $tile; // Return the tile to its original position
            return;
        }

        // hive connected validation
        // simulate the move and check if it's valid
        $tempBoard = $board; // Create a copy of the current board state
        unset($tempBoard[$from]); // Remove the tile from its original position
        $tempBoard[$to] = [$tile]; // Simulate the tile's new position

        // Now check if the hive is connected after the simulated move
        if (!$this->isHiveConnected($tempBoard)) {
            $_SESSION['error'] = "Move would split hive";
        }

        if (!$this->gameLogic->hasNeighbour($to, $board)) {
            $_SESSION['error'] = "Move would split hive";
            return;
        }
        
        
        // More validations and actual move
        if ($from == $to) {
            $_SESSION['error'] = 'Tile must move';
        } elseif (isset($board[$to]) && $tile[1] != "B") {
            $_SESSION['error'] = 'Tile not empty';
        } elseif (in_array($tile[1], ["Q", "B"]) && !$this->gameLogic->slide($board, $from, $to)) {
            $_SESSION['error'] = 'Tile must slide';
        } else {
            // Finalizing the move
            $board[$to] = isset($board[$to]) ? array_merge($board[$to], [$tile]) : [$tile];
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertMove($from, $to, $tile);
        }
        
        if (empty($board[$from])) {
            unset($board[$from]);
        }

        if (!isset($_SESSION['error'])) {
            $_SESSION['board'] = $board;
        } else {
            // If there's an error, revert the tile to its original position
            $board[$from][] = $tile;
        }
    }

    
}