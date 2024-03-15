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
    private $turn;

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
        $this->turn = $_SESSION['turn'] ?? 0;
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
                case isset($_POST['AIMove']):
                    $this->AIMove();
                    break;
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    public function AIMove() {

        $url = 'http://ai:5000/';
        $data = [
            'move_number' => $this->turn,
            'hand' => $this->hand,
            'board' => $this->board
        ];

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        $response = json_decode($response, true);

        if ($response[0] === "play") {
            // Mogelijk zal de AI zetten doen die ongeldig zijn op grond van de interpretatie van de
            // regels zoals je applicatie die heeO, maar dit mag je negeren. Je mag gewoon de zet
            // uitvoeren die de AI voorstelt, ook als deze niet geldig is.
            $_SESSION['board'][$response[2]] = [[$_SESSION['player'], $response[1]]];
            $_SESSION['hand'][$this->turn % 2][$response[1]]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertPlay($response[1], $response[2]);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;

            // anders natuurlijk via deze functie
            // $this->play($response[1], $response[2]);
        } elseif ($response[0] === "move") {
            $_SESSION['board'][$response[2]] = [[$_SESSION['player'], $response[1]]];
            $_SESSION['hand'][$this->turn % 2][$response[1]]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertMove($response[1], $response[2]);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;
            // anders natuurlijk via deze functie
            // $this->move($response[1], $response[2]);
        } else {
            $_SESSION['board'][$response[2]] = [[$_SESSION['player'], $response[1]]];
            $_SESSION['hand'][$this->turn % 2][$response[1]]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->pass();
            $_SESSION['turn'] += 1;
        }


    }

    public function restart() {

        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;
        $_SESSION['game_over'] = false;
        $_SESSION['winner'] = null;
        $_SESSION['turn'] = 0;


        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insert_id();
    }

    
    public function pass() {
        // Before allowing the pass, check if the player has any valid moves
        if ($this->gameLogic->hasValidMoves($this->board, $this->hand, $this->player)) {
            $_SESSION['error'] = "Cannot pass, valid moves are available.";
            return;
        }

        // Continue with the pass logic if no valid moves are available
        $stmt = $this->db->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "pass", null, null, ?, ?)');
        $state = $this->db->get_state();
        $stmt->bind_param('iis', $_SESSION['game_id'], $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
        $_SESSION['player'] = 1 - $_SESSION['player'];
        $_SESSION['turn'] += 1;
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

            $_SESSION['turn'] -= 1;
        }
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
                $this->checkGameEnd();
                $_SESSION['turn'] += 1;
            }
        }
           
        elseif (count($board) && !$this->gameLogic->hasNeighBour($to, $board))
            $_SESSION['error'] = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->gameLogic->neighboursAreSameColor($player, $to, $board))
            $_SESSION['error'] = "Board position has opposing neighbour, trying to move from: " . $to . " with " . $piece . " sc: " . " sum: " . array_sum($hand);
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            $_SESSION['error'] = 'Must play queen bee';
        } else {
            $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertPlay($piece, $to);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;
        }
    }

    private function insertMove($from, $to) {
        $gameId = $_SESSION['game_id'];
        $previousId = $_SESSION['last_move'];
        $state = $this->db->get_state();
        
        $query = 'INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "move", ?, ?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('issis', $gameId, $from, $to, $previousId, $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insert_id();
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

        if ($tile[1] === "A" && !$this->gameLogic->checkIfMoveinCalculatedArray($to, $this->gameLogic->calculateAntMoves($from, $board, $player))) {
            $_SESSION['error'] = "Invalid ant move";
            $board[$from][] = $tile; // Return the tile to its original position
            return;
        }

        if ($tile[1] === "S" && !$this->gameLogic->checkIfMoveinCalculatedArray($to, $this->gameLogic->calculateSpiderMoves($from, $board, $player))) {
            $_SESSION['error'] = "Invalid spider move";
            $board[$from][] = $tile; // Return the tile to its original position
            return;
        }

        // hive connected validation
        // simulate the move and check if it's valid
        $tempBoard = $board; // Create a copy of the current board state
        unset($tempBoard[$from]); // Remove the tile from its original position
        $tempBoard[$to] = [$tile]; // Simulate the tile's new position

        // Now check if the hive is connected after the simulated move
        if (!$this->gameLogic->isHiveConnected($tempBoard)) {
            $_SESSION['error'] = "Move would split hive";
            $board[$from][] = $tile; // Return the tile to its original position
            return;
        }

        if (!$this->gameLogic->hasNeighbour($to, $board)) {
            $_SESSION['error'] = "Move would split hive";
            $board[$from][] = $tile; // Return the tile to its original position
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
            $this->insertMove($from, $to);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;
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

    public function checkGameEnd() {
        if ($this->gameLogic->isDraw($this->board)) {
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 'draw';
            return;
        }

        if ($this->gameLogic->isQueenSurrounded($this->board, 0)) { // Check if the white queen is surrounded
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 1; // Black wins
            return;
        }

        if ($this->gameLogic->isQueenSurrounded($this->board, 1)) { // Check if the black queen is surrounded
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 0; // White wins
            return;
        }

        // No conditions met, the game continues
        $_SESSION['game_over'] = false;
    }
}