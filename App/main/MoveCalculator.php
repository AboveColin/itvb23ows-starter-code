<?php

namespace Colin\Hive;

class MoveCalculator extends BaseGameLogic {
    
    public function slide($board, $from, $to) {
        # Bug fix #2
        if (!$this->hasNeighBour($to, $board) || !$this->isNeighbour($from, $to)) {
            return false;
        }

        $b = explode(',', $to);
        $common = [];
        foreach ($this->offsets as $pq) {
            $p = $b[0] + $pq[0];
            $q = $b[1] + $pq[1];
            if ($this->isNeighbour($from, "$p,$q")) {
                $common[] = "$p,$q";
            }
                
        }
    
        if (count($common) < 2)  {
            return false;
        }
    
        $lenCommon0 = isset($board[$common[0]]) ? $this->len($board[$common[0]]) : 0;
        $lenCommon1 = isset($board[$common[1]]) ? $this->len($board[$common[1]]) : 0;
    
        $lenFrom = isset($board[$from]) ? $this->len($board[$from]) : 0;
        $lenTo = isset($board[$to]) ? $this->len($board[$to]) : 0;
    
        return min($lenCommon0, $lenCommon1) <= max($lenFrom, $lenTo);
    }

    public function calculatePositions($board, $offsets, $player) {
        // bug fix #1
        $validPositions = [];

        if ($this->isInitialMove($board)) {
            $validPositions = $this->calculateInitialPositions($offsets, $board);
        } else {
            // For moves after the second, calculate valid positions based on existing logic
            $validPositions = $this->calculateSubsequentPositions($board, $offsets, $player);
        }
    
        if (empty($validPositions)) {
            $validPositions[] = '0,0';
        }
    
        return array_unique($validPositions);
    }

    private function isInitialMove($board) {
        return count($board) == 1 && isset($board['0,0']);
    }

    private function gcd($a, $b) {
        // functie om de rechte lijn te berekenen
        return $b ? $this->gcd($b, $a % $b) : $a;
    }

    private function calculateInitialPositions($offsets, $board) {
        $validPositions = [];
        foreach ($offsets as $offset) {
            $newPos = $offset[0] . ',' . $offset[1];
            if (!array_key_exists($newPos, $board)) {
                $validPositions[] = $newPos;
            }
        }
        return $validPositions;
    }

    private function calculateSubsequentPositions($board, $offsets, $player) {
        $validPositions = [];
        foreach (array_keys($board) as $pos) {
            list($p, $q) = explode(',', $pos);
            foreach ($offsets as $offset) {
                $newPos = ($p + $offset[0]) . ',' . ($q + $offset[1]);
                if (!array_key_exists($newPos, $board) && $this->isValidPosition($newPos, $board, $player)) {
                    $validPositions[] = $newPos;
                }
            }
        }
        return $validPositions;
    }

    public function isValidGrasshopperMove($from, $to, $board) : bool {
        $isValid = false; // Initialize the validity of the move as false
        
        if ($from !== $to) {
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
    
                if ($currentPosKey === $to) {
                    if ($hasJumped && isset($board[$currentPosKey])) {
                        // Invalid if the destination is occupied
                        $isValid = false;
                    } else {
                        // Valid if it has jumped and the destination is not occupied
                        $isValid = $hasJumped;
                    }
                    break;
                } elseif (!isset($board[$currentPosKey])) {
                    if ($hasJumped) {
                        // If it has already jumped and finds an empty space, the move is valid
                        $isValid = true;
                    } else {
                        // If it hasnt jumped yet, the move is invalid
                        $isValid = false;
                    }
                    break;
                } else {
                    $hasJumped = true; // Mark that it has jumped over a stone
                }
            }
        }
    
        return $isValid;
    }

    public function calculateGrasshopperMoves($from, $board) {
        $validMoves = [];
        foreach (array_keys($board) as $pos) {
            for ($i = 0; $i < 6; $i++) {
                $newPos =
                (explode(',', $pos)[0] + $this->getOffsets()[$i][0])
                    . ',' .
                (explode(',', $pos)[1] + $this->getOffsets()[$i][1]);

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
                $newPos = (
                        explode(',', $currentPosition)[0] + $offset[0]) . ',' .
                    (
                        explode(',', $currentPosition)[1] + $offset[1]);
                
                if (!isset($board[$newPos]) && // Check if the new position is empty
                    !isset($visited[$newPos]) && // Check if the position hasn't been visited
                    $this->slide($board, $currentPosition, $newPos)) { // Check if a slide to the new position is valid
                    
                    // Temporarily simulate the ant's move to ensure the hive remains connected
                    $tempBoard = $board;
                    unset($tempBoard[$from]);
                    $tempBoard[$newPos] = [[$player, 'A']]; // Simulate moving the ant to the new position
                    
                    // Ensure the hive remains connected after the simulated move
                    if ($this->isHiveConnected($tempBoard)) {
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
            if (!isset($visited[$newPos])
                && $this->slide($board, $currentPos, $newPos)
                && $this->isAdjacentToAtLeastOneTile($newPos, $board)) {
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

}
