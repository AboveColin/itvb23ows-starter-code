<?php

namespace Colin\Hive;

class GameLogic {
    private $offsets;

    public function __construct() {
        $this->offsets = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];
    }

    public function getOffsets() {
        return $this->offsets;
    }

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
            if ($this->isNeighbour($a, $b)) return true;
        }
    }
    
    function neighboursAreSameColor($player, $a, $board) {
        foreach ($board as $b => $st) {
            if (!$st) continue;
            $c = $st[count($st) - 1][0];
            if ($c != $player && $this->isNeighbour($a, $b)) return false;
        }
        return true;
    }
    
    function len($tile) {
        return $tile ? count($tile) : 0;
    }
    
    function slide($board, $from, $to) {
        if (!$this->hasNeighBour($to, $board)) return false;
        if (!$this->isNeighbour($from, $to)) return false;
        $b = explode(',', $to);
        $common = [];
        foreach ($this->offsets as $pq) {
            $p = $b[0] + $pq[0];
            $q = $b[1] + $pq[1];
            if ($this->isNeighbour($from, "$p,$q")) $common[] = "$p,$q";
        }
    
        if (count($common) < 2) return false;
    
        $lenCommon0 = isset($board[$common[0]]) ? $this->len($board[$common[0]]) : 0;
        $lenCommon1 = isset($board[$common[1]]) ? $this->len($board[$common[1]]) : 0;
    
        $lenFrom = isset($board[$from]) ? $this->len($board[$from]) : 0;
        $lenTo = isset($board[$to]) ? $this->len($board[$to]) : 0;
    
        return min($lenCommon0, $lenCommon1) <= max($lenFrom, $lenTo);
    }

    function isValidPosition($position, $board, $player) {
        if ($this->hasNeighBour($position, $board) && $this->neighboursAreSameColor($player, $position, $board)) {
            return true;
        }
        return false;
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
        $offsets = $this->getOffsets();
        list($x, $y) = explode(',', $pos);
        $neighbors = [];
        foreach ($offsets as $offset) {
            $neighbors[] = ($x + $offset[0]) . ',' . ($y + $offset[1]);
        }
        return $neighbors;
    }


    function calculatePositions($board, $offsets, $player) {
        // bug fix #1
        $validPositions = [];
    
        if (count($board) == 1 && isset($board['0,0'])) {
            foreach ($offsets as $offset) {
                $newPos = $offset[0] . ',' . $offset[1];
                if (!array_key_exists($newPos, $board)) {
                    $validPositions[] = $newPos;
                }
            }
        } else {
            // For moves after the second, calculate valid positions based on existing logic
            foreach (array_keys($board) as $pos) {
                list($p, $q) = explode(',', $pos);
                foreach ($offsets as $offset) {
                    $newPos = ($p + $offset[0]) . ',' . ($q + $offset[1]);
                    if (!array_key_exists($newPos, $board) && $this->isValidPosition($newPos, $board, $player)) {
                        $validPositions[] = $newPos;
                    }
                }
            }
        }

        // eerste zet
        if (empty($validPositions)) $validPositions[] = '0,0';
    
        return array_unique($validPositions);
    }

    private function gcd($a, $b) {
        // functie om de rechte lijn te berekenen
        return $b ? $this->gcd($b, $a % $b) : $a;
    }

    public function isValidGrasshopperMove($from, $to, $board) : bool {
        if ($from === $to) {
            return false; // Een sprinkhaan mag zich niet verplaatsen naar het veld waar hij al staat.
        }

        $fromCoords = explode(',', $from);
        $toCoords = explode(',', $to);
        $direction = [$toCoords[0] - $fromCoords[0], $toCoords[1] - $fromCoords[1]];

        // Check for straight line movement
        $gcd = $this->gcd(abs($direction[0]), abs($direction[1]));
        if ($gcd !== 0) {
            $direction[0] /= $gcd;
            $direction[1] /= $gcd;
        }

        $currentPosition = $fromCoords;
        $hasJumped = false;
        while (true) {
            $currentPosition[0] += $direction[0];
            $currentPosition[1] += $direction[1];
            $currentPosKey = implode(',', $currentPosition);

            if (!isset($board[$currentPosKey])) {
                if ($hasJumped) { 
                    // Een sprinkhaan mag niet over lege velden springen. Dit betekent dat alle
                    // velden tussen de start- en eindpositie bezet moeten zijn. 
                    return $currentPosKey === $to;
                } else {
                    //Een sprinkhaan moet over minimaal één steen springen. 
                    return false;
                }
            } else {
                $hasJumped = true; 
                // Een sprinkhaan verplaatst zich door in een rechte lijn een sprong te maken 
                // naar een veld meteen achter een andere steen in de richting van de sprong.
            }

            if ($currentPosKey === $to) {
                return false; 
                //Een sprinkhaan mag niet naar een bezet veld springen.
            }
        }
        return false;
    }

    public function calculateGrasshopperMoves($from, $board, $player) {
        $validMoves = [];
        foreach (array_keys($board) as $pos) {
            for ($i = 0; $i < 6; $i++) {
                $newPos = (explode(',', $pos)[0] + $this->getOffsets()[$i][0]) . ',' . (explode(',', $pos)[1] + $this->getOffsets()[$i][1]);
                if ($this->isValidGrasshopperMove($from, $newPos, $board)) {
                    $validMoves[] = $newPos;
                }
            }
        }
        return array_unique($validMoves); // Remove duplicates
    }

    public function calculateAntMoves($from, $board, $player) {
        $validMoves = [];
        $visited = [$from => true];
        $queue = new \SplQueue();
        $queue->enqueue($from);
    
        while (!$queue->isEmpty()) {
            $currentPosition = $queue->dequeue();
            foreach ($this->getOffsets() as $offset) {
                $newPos = (explode(',', $currentPosition)[0] + $offset[0]) . ',' . (explode(',', $currentPosition)[1] + $offset[1]);
                
                if (!isset($board[$newPos]) && // Check if the new position is empty
                    !isset($visited[$newPos]) && // Check if the position hasn't been visited
                    $this->slide($board, $currentPosition, $newPos)) { // Check if a slide to the new position is valid
                    
                    // Temporarily simulate the ant's move to ensure the hive remains connected
                    $tempBoard = $board;
                    unset($tempBoard[$from]);
                    $tempBoard[$newPos] = [[$player, 'A']]; // Simulate moving the ant to the new position
                    
                    if ($this->isHiveConnected($tempBoard)) { // Ensure the hive remains connected after the simulated move
                        $validMoves[] = $newPos;
                        $visited[$newPos] = true;
                        $queue->enqueue($newPos);
                    }
                }
            }
        }
    
        return $validMoves;
    }

    public function checkIfMoveinCalculatedArray($to, $calculatedMoves) {
        
        foreach ($calculatedMoves as $move) {
            if ($move === $to) {
                return true;
            }
        }
        return false;
    }


    public function calculateSpiderMoves($from, $board, $player) {
        $validMoves = [];
        $visited = [];

        if (count($board) == 1 && isset($board[$from])) {
            // Als er alleen nog maar 1 spin is geplaatst en niets anders.
            return $validMoves;
        }

        $this->dfsSpider($from, $board, $player, 0, $visited, $validMoves);
        return array_unique($validMoves); // Remove duplicates
    }

    private function dfsSpider($currentPos, $board, $player, $depth, $visited, &$validMoves) {
        if ($depth == 3) {
            // max diepte van 3, dus geen zetten meer
            if (!isset($board[$currentPos]) && $this->isAdjacentToAtLeastOneTile($currentPos, $board)) {
                // check of de spin op een leeg veld staat en of het veld grenst aan minimaal 1 andere steen
                $validMoves[] = $currentPos;
            }
            return;
        }

        $visited[$currentPos] = true; // Mark the current position as visited
        
        foreach ($this->getOffsets() as $offset) {
            $newPos = (explode(',', $currentPos)[0] + $offset[0]) . ',' . (explode(',', $currentPos)[1] + $offset[1]);
            if (!isset($visited[$newPos]) && $this->slide($board, $currentPos, $newPos) && $this->isAdjacentToAtLeastOneTile($newPos, $board)) {
                // Recursief de mogelijke zetten van de spin berekenen
                $this->dfsSpider($newPos, $board, $player, $depth + 1, $visited, $validMoves);
            }
        }

        unset($visited[$currentPos]); // Unmark the current position for other paths
    }

    private function isAdjacentToAtLeastOneTile($pos, $board) {
        // Check if the given position is adjacent to at least one existing tile on the board
        foreach ($this->getOffsets() as $offset) {
            $adjacentPos = (explode(',', $pos)[0] + $offset[0]) . ',' . (explode(',', $pos)[1] + $offset[1]);
            if (isset($board[$adjacentPos])) {
                // Found an adjacent tile
                return true;
            }
        }
        return false;
    }

    public function hasValidMoves($board, $hand, $player) {

        foreach (array_keys($hand[$player]) as $tile) {
            if ($hand[$player][$tile] > 0) {
                foreach (array_keys($board) as $pos) {
                    if ($board[$pos][count($board[$pos]) - 1][0] == $player) {
                        if ($tile == 'A') {
                            $calculatedMoves = $this->calculateAntMoves($pos, $board, $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } elseif ($tile == 'S') {
                            $calculatedMoves = $this->calculateSpiderMoves($pos, $board, $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } elseif ($tile == 'G') {
                            $calculatedMoves = $this->calculateGrasshopperMoves($pos, $board, $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } else {
                            $calculatedMoves = $this->calculatePositions($board, $this->getOffsets(), $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false; // No valid moves found
    }

    // Win check functions
    public function isQueenSurrounded($board, $player) {
        foreach ($board as $pos => $tiles) {
            foreach ($tiles as $tile) {
                if ($tile[1] == 'Q' && $tile[0] == $player) {
                    return $this->areAllNeighborsOccupied($pos, $board);
                }
            }
        }
        return false;
    }

    private function areAllNeighborsOccupied($pos, $board) {
        $neighbors = $this->getNeighbors($pos);
        foreach ($neighbors as $neighbor) {
            if (!isset($board[$neighbor])) {
                return false; // Found an unoccupied neighbor
            }
        }
        return true; // All neighbors are occupied
    }

    public function isDraw($board) {
        $whiteQueenSurrounded = $this->isQueenSurrounded($board, 0);
        $blackQueenSurrounded = $this->isQueenSurrounded($board, 1);

        return $whiteQueenSurrounded && $blackQueenSurrounded;
    }
    
}