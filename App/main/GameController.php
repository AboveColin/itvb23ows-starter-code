<?php

namespace Colin\Hive;


class GameController {
    
    private $db;
    private $moveCalculator;
    private $gameValidator;
    private $gameLogic;

    private $board;
    private $player;
    private $hand;
    private $gameID;
    private $lastMove;
    private $error;
    private $turn;

    public function __construct($db, $baseGameLogic, $moveCalculator, $gameValidator) {
        $this->db = $db;
        $this->gameLogic = $baseGameLogic;
        $this->moveCalculator = $moveCalculator;
        $this->gameValidator = $gameValidator;
    }

    public function startInitGame() {

        $this->board = $_SESSION['board'];

        if (!isset($this->board)) {
            $this->restart();
        }

        $this->player = $_SESSION['player'];
        $this->hand = $_SESSION['hand'];
        $this->gameID = $_SESSION['game_id'];
        $this->lastMove = $_SESSION['last_move'] ?? 0;
        $this->error = $_SESSION['error'] ?? null;
        $this->turn = $_SESSION['turn'] ?? 0;
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

    public function handlePostRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch($_POST) {
                default:
                    break;
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
                    $this->aiMove();
                    break;
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    public function aiMove() {

        $url = 'http://' . getenv('AI_HOST') . ":" . getenv('AI_PORT');
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

            // Anders natuurlijk via deze functie, maar logic mocht worden overgeslagen
            // $this->play($response[1], $response[2]);
        } elseif ($response[0] === "move") {
            $_SESSION['board'][$response[2]] = [[$_SESSION['player'], $response[1]]];
            $_SESSION['hand'][$this->turn % 2][$response[1]]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertMove($response[1], $response[2]);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;

            // Anders natuurlijk via deze functie, maar logic mocht worden overgeslagen
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
        $_SESSION['hand'] = [0 => [
            "Q" => 1,
            "B" => 2,
            "S" => 2,
            "A" => 3,
            "G" => 3
        ],
        1 => [
            "Q" => 1,
            "B" => 2,
            "S" => 2,
            "A" => 3,
            "G" => 3
        ]];
        $_SESSION['player'] = 0;
        $_SESSION['game_over'] = false;
        $_SESSION['winner'] = null;
        $_SESSION['turn'] = 0;


        $this->db->prepare('INSERT INTO games VALUES ()')->execute();
        $_SESSION['game_id'] = $this->db->insertId();
    }

    
    public function pass() {
        // Before allowing the pass, check if the player has any valid moves
        if ($this->gameValidator->hasValidMoves($this->board, $this->hand, $this->player)) {
            $_SESSION['error'] = "Cannot pass, valid moves are available.";
            return;
        }

        // Continue with the pass logic if no valid moves are available
        $stmt = $this->db->prepare(
            'insert into moves
            (game_id, type, move_from, move_to, previous_id, state)
            values (?, "pass", null, null, ?, ?)'
        );
        $state = $this->db->getState();
        $stmt->bind_param('iis', $_SESSION['game_id'], $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insertId();
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
        $stmt = $this->db->prepare(
            'insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "play", ?, ?, ?, ?)'
        );
        $state = $this->db->getState();
        $stmt->bind_param('issis', $_SESSION['game_id'], $piece, $to, $_SESSION['last_move'], $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insertId();
    }

    public function play($piece, $to) {

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];

        if (!$hand[$piece]) {
            $_SESSION['error'] = "Player does not have tile";
        } elseif (isset($board[$to])) {
            $_SESSION['error'] = 'Board position is not empty';
        } elseif (array_sum($hand) == 8 && $hand['Q']) {
            // Must play queen bee by the fourth move bug fix
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
        } elseif (count($board) && !$this->gameLogic->hasNeighBour($to, $board)) {
            $_SESSION['error'] = "board position has no neighbour";
        } elseif (array_sum($hand) < 11 && !$this->gameLogic->neighboursAreSameColor($player, $to, $board)) {
            $_SESSION['error'] = "Board position has opposing neighbour";
        } elseif (array_sum($hand) <= 8 && $hand['Q']) {
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
        $state = $this->db->getState();
        
        $query = 'INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state)
                VALUES (?, "move", ?, ?, ?, ?)';
                
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('issis', $gameId, $from, $to, $previousId, $state);
        $stmt->execute();
        $_SESSION['last_move'] = $this->db->insertId();
    }

    private function isValidSpecialPieceMove($from, $to, $board, $player) {
        $tile = $board[$from][count($board[$from])-1];
        switch($tile[1]) {
            case "G":
                return $this->moveCalculator->isValidGrasshopperMove($from, $to, $board);
            case "A":
                return $this->moveCalculator->checkIfMoveinCalculatedArray(
                    $to,
                    $this->moveCalculator->calculateAntMoves($from, $board, $player)
                );
            case "S":
                return $this->moveCalculator->checkIfMoveinCalculatedArray(
                    $to,
                    $this->moveCalculator->calculateSpiderMoves($from, $board, $player)
                );
            default:
                return true;
        }
    }

    private function positionNotEmpty($from, $board) {
        if (!isset($board[$from])) {
            $_SESSION['error'] = 'Board position is empty';
            return false;
        }
        return true;
    }

    private function tileOwnedByPlayer($from, $board, $player) {
        if ($board[$from][count($board[$from])-1][0] != $player) {
            $_SESSION['error'] = "Tile is not owned by player";
            return false;
        }
        return true;
    }

    private function queenBeePlayed($player) {
        $hand = $_SESSION['hand'][$player];
        if ($hand['Q']) {
            $_SESSION['error'] = "Queen bee is not played";
            return false;
        }
        return true;
    }

    private function hiveRemainsConnected($from, $to, $board) {
        // Simulate the move for connectivity check
        $tile = array_pop($board[$from]);
        $tempBoard = $board; // Create a copy of the current board state
        unset($tempBoard[$from]); // Remove the tile from its original position
        $tempBoard[$to][] = $tile; // Simulate the tile's new position

        if (!$this->gameLogic->isHiveConnected($tempBoard)) {
            $_SESSION['error'] = "Move would split hive";
            return false;
        }
        return true;
    }

    private function checkifHasNeighbour($to, $board) {
        if (!$this->gameLogic->hasNeighbour($to, $board)) {
            $_SESSION['error'] = "Move would split hive";
            return false;
        }
        return true;
    }

    private function checkIfMoved($from, $to) {
        if ($from == $to) {
            $_SESSION['error'] = 'Tile must move';
            return false;
        }
        return true;
    }

    private function checkStackMove($from, $to, $board) {
        $tile = $board[$from][count($board[$from])-1];
        if ($tile[1] != "B" && isset($board[$to])) {
            $_SESSION['error'] = 'Tile not empty';
            return false;
        }
        return true;
    }

    private function checkSlide($from, $to, $board) {
        $tile = $board[$from][count($board[$from])-1];
        if (in_array($tile[1], ["Q", "B"]) && !$this->moveCalculator->slide($board, $from, $to)) {
            $_SESSION['error'] = 'Tile must slide';
            return false;
        }
        return true;
    }

    private function validateMove($from, $to, $board, $player) {
        return $this->positionNotEmpty($from, $board) &&
            $this->tileOwnedByPlayer($from, $board, $player) &&
            $this->queenBeePlayed($player) &&
            $this->isValidSpecialPieceMove($from, $to, $board, $player) &&
            $this->hiveRemainsConnected($from, $to, $board) &&
            $this->checkifHasNeighbour($to, $board) &&
            $this->checkIfMoved($from, $to) &&
            $this->checkStackMove($from, $to, $board);
    }


    
    public function move($from, $to) {
        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        
        // Clear any previous error
        unset($_SESSION['error']);
        
        // Validations for the move
        if (!$this->validateMove($from, $to, $board, $player)) {
            return;
        } else {
            // Finalizing the move
            $tile = array_pop($board[$from]);
            $this->board[$to][] = $tile;
            
            $board[$to] = isset($board[$to]) ? array_merge($board[$to], [$tile]) : [$tile];
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $this->insertMove($from, $to);
            $this->checkGameEnd();
            $_SESSION['turn'] += 1;

            #bug fix 4
            if (empty($board[$from])) {
                unset($board[$from]);
            }
        }

        
        

        // if (!isset($_SESSION['error'])) {
        //     $_SESSION['board'] = $board;
        // } else {
        //     // If there's an error, revert the tile to its original position
            
        // }
    }

    public function checkGameEnd() {
        if ($this->gameValidator->isDraw($this->board)) {
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 'draw';
            return;
        }

        if ($this->gameValidator->isQueenSurrounded($this->board, 0)) { // Check if the white queen is surrounded
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 1; // Black wins
            return;
        }

        if ($this->gameValidator->isQueenSurrounded($this->board, 1)) { // Check if the black queen is surrounded
            $_SESSION['game_over'] = true;
            $_SESSION['winner'] = 0; // White wins
            return;
        }

        // No conditions met, the game continues
        $_SESSION['game_over'] = false;
    }
}
