<?php

namespace Colin\Hive;

use Colin\Hive\MoveCalculator;

class GameValidator extends BaseGameLogic {
    private $moveCalculator;

    public function __construct() {
        parent::__construct();
        $this->moveCalculator = new MoveCalculator();
    }

    public function hasValidMoves($board, $hand, $player) {

        foreach (array_keys($hand[$player]) as $tile) {
            if ($hand[$player][$tile] > 0) {
                foreach (array_keys($board) as $pos) {
                    if ($board[$pos][count($board[$pos]) - 1][0] == $player) {
                        if ($tile == 'A') {
                            $calculatedMoves = $this->moveCalculator->calculateAntMoves(
                                $pos, $board, $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } elseif ($tile == 'S') {
                            $calculatedMoves = $this->moveCalculator->calculateSpiderMoves(
                                $pos, $board, $player);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } elseif ($tile == 'G') {
                            $calculatedMoves = $this->moveCalculator->calculateGrasshopperMoves(
                                $pos, $board);
                            if (!empty($calculatedMoves)) {
                                return true;
                            }
                        } else {
                            $calculatedMoves = $this->moveCalculator->calculatePositions(
                                $board, $this->getOffsets(), $player);
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
        $neighbors =$this->getNeighbors($pos);
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