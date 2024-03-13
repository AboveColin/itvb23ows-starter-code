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

    public function isValidGrasshopperMove($from, $to, $board) {
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

    

    

    
}