<?php
namespace Colin\Hive;

class GameRenderer
{
    public function displayTile($tile, $pos, $min_p, $min_q, $playerClass) {
        list($p, $q) = explode(',', $pos);
        $h = count($tile);
        $centerOffsetX = 15; // Horizontal center offset
        $centerOffsetY = 15; // Vertical center offset
        $left = ($p - $min_p) * 4 + ($q - $min_q) * 2 + $centerOffsetX;
        $top = ($q - $min_q) * 4 + $centerOffsetY;
        $stacked = $h > 1 ? 'stacked' : '';
        $innerTile = $tile[$h-1][1];
        $tileType = strtolower($innerTile);
        
        // Construct the file path for the tile image
        $imagePath = "/" . $innerTile . ".png";
    
        // Display image and coordinates
        echo "<div class=\"tile $playerClass $stacked\"
                style=\"left: {$left}em; top: {$top}em;\"
                data-position=\"$pos\" data-type=\"$tileType\">";

        echo "<img src=\"$imagePath\" alt=\"$innerTile\" class=\"tileImage\">";
        echo "<span class=\"coordinates\">($p,$q) $innerTile</span>";
        echo "</div>";
    }
    
    public function renderGhostTiles($board, $offsets) {
        $min_p = 1000;
        $min_q = 1000;

        foreach ($board as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            $min_p = min($p, $min_p);
            $min_q = min($q, $min_q);
        }

        foreach (array_filter($board) as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            foreach ($offsets as $offset) {
                $newPos = ($p + $offset[0]) . ',' . ($q + $offset[1]);
                if (!array_key_exists($newPos, $board)) {
                    $this->displayGhostTile($p + $offset[0], $q + $offset[1], $min_p, $min_q);
                }
            }
        }
    }
    
    public function displayGhostTile($p, $q, $min_p, $min_q) {
        $centerOffsetX = 15; // Horizontal center offset
        $centerOffsetY = 15; // Vertical center offset
        $left = ($p - $min_p) * 4 + ($q - $min_q) * 2 + $centerOffsetX;
        $top = ($q - $min_q) * 4 + $centerOffsetY;

        echo "<div class=\"tile ghost\" style=\"left: {$left}em; top: {$top}em;\">($p,$q)</div>";
    }
    
    public function renderBoard($board) {
        $min_p = 1000;
        $min_q = 1000;
        foreach ($board as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            $min_p = min($p, $min_p);
            $min_q = min($q, $min_q);
        }
        foreach (array_filter($board) as $pos => $tile) {
            $this->displayTile($tile, $pos, $min_p, $min_q, 'player' . $tile[count($tile)-1][0]);
        }
    }
    
    public function getPlayerTiles($hand, $player) {
        if (array_sum($hand[$player]) == 0) {
            echo "No tiles left";
        } else {
            foreach ($hand[$player] as $tile => $ct) {
                for ($i = 0; $i < $ct; $i++) {
                    $tile = substr($tile, 0, 1);
                    $imagePath = "/" . $tile . ".png";
                    echo "<img src=\"$imagePath\" alt=\"$tile\" style=\"width: 25px; height: 25px;\">";
                }
            }
        }
    }
    
    public function renderHand($hand, $player) {
        $this->getPlayerTiles($hand, $player);
    }
    
    public function displayTurn($player) {
        echo $player == 0 ? "White" : "Black";
    }

    public function displayError() {
        if (isset($_SESSION['error'])) {
            echo "<strong>Error: {$_SESSION['error']}</strong>";
            unset($_SESSION['error']);
        }
    }
    
    public function displayPiece($hand, $player) {
        foreach ($hand[$player] as $tile => $ct) {
            if ($ct > 0) {
                // alleen stukken die je nog hebt in je hand
                // bug fix 1
                echo "<option value=\"$tile\" >$tile</option>";
            }
        }
    }
    
    public function displayFrom($board, $player) {
        // laat alleen de stukken zien die je hebt in je hand
        // bug fix 1
        $from = [];
        foreach (array_keys($board) as $pos) {
            if ($board[$pos][count($board[$pos])-1][0] == $player) {
                $from[] = $pos;
            }
        }
        foreach ($from as $pos) {
            echo "<option value=\"$pos\">$pos</option>";
        }
    }

    public function displayLog($db) {
        $stmt = $db->prepare('SELECT * FROM moves WHERE game_id = ?');
        $stmt->bind_param('i', $_SESSION['game_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_array()) {
            if ($row[2] == 'play') {
                echo '<li><img src="'.
                htmlspecialchars($row[3]) .'.png" style="width: 15px"/> '.
                htmlspecialchars($row[2]).' '.
                htmlspecialchars($row[3]).' '.
                htmlspecialchars($row[4]);

            } elseif ($row[2] == 'move') {
                echo '<li><img src="'.
                htmlspecialchars($row[2]) .'.png" style="width: 15px"/> '.
                htmlspecialchars($row[2]).' '.
                htmlspecialchars($row[3]).' '.
                htmlspecialchars($row[4]);

            } else {
                echo '<li>'.htmlspecialchars($row[2]);
            }
            echo '</li>';
        }
    }

    private function displayMoveTile($p, $q, $min_p, $min_q) {
        $centerOffsetX = 15; // Horizontal center offset
        $centerOffsetY = 15; // Vertical center offset
        $left = ($p - $min_p) * 4 + ($q - $min_q) * 2 + $centerOffsetX;
        $top = ($q - $min_q) * 4 + $centerOffsetY;

        echo "<div class=\"tile move\" style=\"left: {$left}em; top: {$top}em;\">($p,$q)</div>";
    }

    public function renderAntMoves($board, $gameLogic, $pos) {
        $antMoves = $gameLogic->calculateAntMoves($pos, $board, 0);

        $min_p = 1000;
        $min_q = 1000;

        foreach ($board as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            $min_p = min($p, $min_p);
            $min_q = min($q, $min_q);
        }

        foreach ($antMoves as $pos) {
            list($p, $q) = explode(',', $pos);
            $this->displayMoveTile($p, $q, $min_p, $min_q);
        }
    }

    public function renderSpiderMoves($board, $gameLogic, $pos) {
        $spiderMoves = $gameLogic->calculateSpiderMoves($pos, $board, 0);

        $min_p = 1000;
        $min_q = 1000;

        foreach ($board as $pos => $tile) {
            list($p, $q) = explode(',', $pos);
            $min_p = min($p, $min_p);
            $min_q = min($q, $min_q);
        }

        foreach ($spiderMoves as $pos) {
            list($p, $q) = explode(',', $pos);
            $this->displayMoveTile($p, $q, $min_p, $min_q);
        }
    }

    public function renderOutcome() {
        if (isset($_SESSION['game_over']) && $_SESSION['game_over']) {
            $message = "";
            $messageClass = "outcome";
            
            if ($_SESSION['winner'] === 'draw') {
                $message = "The game ended in a draw.";
                $messageClass .= " draw"; // Class for draw-specific styling
            } else {
                $winnerColor = $_SESSION['winner'] == 0 ? "White" : "Black";
                $message = "Game over. {$winnerColor} wins!";
                $messageClass .= " win"; // Class for win-specific styling
            }
            
            echo "<div class=\"{$messageClass}\">{$message}</div>";
        }
    }
    
}
