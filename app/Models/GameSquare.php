<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSquare extends Model
{
    use HasFactory;

    protected $fillable = ['squareType', 'squareId'];

    public $revealed = false;
    public $squareType = \App\Models\Minesweeper::SAFE_SQUARE;
    public $squareId = -1;
    public $adjoiningMines = 0;
    public $flagged = false;
    public $directionsToCheck = [];

    protected $appends = ['squareType', 'adjoiningMines', 'revealed', 'squareId', 'flagged'];

    public function isMine()
    {
        return $this->squareType == \App\Models\Minesweeper::MINE_SQUARE;
    }

    /**
     * Checks square type and adjoining mines to see if square is blank
     * 
     * @param $square GameSquare
     * 
     * @return bool
     */
    public function isSquareBlank()
    {
        return !$this->isMine() && $this->adjoiningMines == 0;
    }

    

    public function getFlaggedAttribute()
    {
        return $this->flagged;
    }

    public function getSquareTypeAttribute()
    {
        return $this->squareType;
    }

    public function getRevealedAttribute()
    {
        return $this->revealed;
    }

    public function getAdjoiningMinesAttribute()
    {
        return $this->adjoiningMines;
    }

    public function getSquareIdAttribute()
    {
        return $this->squareId;
    }

    public function __toString()
    {
        switch($this->squareType) {
            case \App\Models\Minesweeper::SAFE_SQUARE:
                return " ";
            case \App\Models\Minesweeper::MINE_SQUARE:
                return "X";
            case \App\Models\Minesweeper::QUESTION_SQUARE:
                return "?";
        }
    }

}
