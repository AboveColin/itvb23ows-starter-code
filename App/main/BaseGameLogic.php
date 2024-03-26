<?php

namespace Colin\Hive;

class BaseGameLogic {
    protected $offsets;

    public function __construct() {
        $this->offsets = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];
    }

    public function getOffsets() {
        return $this->offsets;
    }

    public function isNeighbour($a, $b) {
        $returnvar = false;
        $a = explode(',', $a);
        $b = explode(',', $b);
        if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1) {
            $returnvar = true;
        }
        if ($a[1] == $b[1] && abs($a[0] - $b[0]) == 1) {
            $returnvar = true;
        }
        if ($a[0] + $a[1] == $b[0] + $b[1]) {
            $returnvar = true;
        }
        return $returnvar;
    }
    
    public function hasNeighBour($a, $board) {
        foreach (array_keys($board) as $b) {
            if ($this->isNeighbour($a, $b)) {
                return true;
            }
        }
    }
    
    public function neighboursAreSameColor($player, $a, $board) {
        foreach ($board as $b => $st) {
            if (!$st) {
                continue;
            }
            $c = $st[count($st) - 1][0];
            if ($c != $player && $this->isNeighbour($a, $b)) {
                return false;
            }
        }
        return true;
    }
    
    public function len($tile) {
        return $tile ? count($tile) : 0;
    }

    public function isValidPosition($position, $board, $player) {
        if ($this->hasNeighBour($position, $board) && $this->neighboursAreSameColor($player, $position, $board)) {
            return true;
        }
        return false;
    }

    protected function getNeighbors($pos) {
        $offsets = $this->offsets;
        list($x, $y) = explode(',', $pos);
        $neighbors = [];
        foreach ($offsets as $offset) {
            $neighbors[] = ($x + $offset[0]) . ',' . ($y + $offset[1]);
        }
        return $neighbors;
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
}
