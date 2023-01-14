<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Models\GameSquare;

class Minesweeper extends Model
{
    use HasFactory;

    //Grid size
    public const DEFAULT_GRID_SIZE = 8;
    public const MAX_GRID_SIZE = 100;
    public const MIN_GRID_SIZE = 1;
    public const DEFAULT_MINE_COUNT = 10;
    //Constants for squares, ENUM would be nice...
    public const SAFE_SQUARE = 0;
    public const MINE_SQUARE = 1;
    public const QUESTION_SQUARE = 2;
    //Game state constants
    public const GAMESTATE_ACTIVE = 0;
    public const GAMESTATE_WON = 1;
    public const GAMESTATE_LOST = 2;

    //Define class properties
    public $gridSize = 8;
    protected $mineCount = 10;
    public $squareCount = 0;

    public $gameGrid = [];
    public $mineLocations = [];

    public $gameState = self::GAMESTATE_ACTIVE;

    protected $hideAttributes = false;

    /**
     * Class constructor called on class initilization
     * Here we setup the game grid size and assign pieces
     * 
     * @author Ollie <general@ollie.im>
     * 
     * @param int $gridSize NxN grid size value
     * @param bool $hideAttributes Determines if attributes such as adjoiningMines and mineType are given to the front end
     * 
     * @return void
     */
    public function __construct($gridSize = self::DEFAULT_GRID_SIZE, $mineCount = self::DEFAULT_MINE_COUNT, $hideAttributes = false)
    {
        //Setup some class properties we will need later
        $this->hideAttributes = $hideAttributes;
        $this->gridSize = $gridSize;
        if ($gridSize > self::MAX_GRID_SIZE) {
            $this->gridSize = self::MAX_GRID_SIZE;
        } else if ($gridSize < self::MIN_GRID_SIZE) {
            $this->gridSize = self::MIN_GRID_SIZE;
        }
        $this->mineCount = $mineCount;
        $this->squareCount = $this->gridSize * $this->gridSize;
        //Prevent an endless loop when we try to allocate mines by ensuring the number of mines required doesn't exceed the number of cells we have!
        if ($this->mineCount > $this->squareCount) {
            $this->mineCount = $this->squareCount;
        }
        $this->allocateMines();//For efficiency and randomness we generate mines first
        $this->generateBoard();
    }

    /**
     * 
     * Assigns each pieces to the game grid
     * 
     * @return array $this->gameGrid Returns current game grid with all pieces assigned
     */
    public function generateBoard() {
        //Create an empty array to store the current row
        $currentRow = [];
        //Loop until we have made iterations equal to our required square count
        for ($i = 0; $i < $this->squareCount; $i++) {
            //Check our mineLocations array to determine if this squareId ($i) is a mine or not
            $squareType = $this->getSquareType($i);
            $gameSquare = new GameSquare;//Create a new GameSquare object
            if ($this->hideAttributes) {//If we need to hide attributes from the front end, do that
                $gameSquare->setHidden(['squareType', 'adjoiningMines']);
            }
            //Assign our baby square's properties
            $gameSquare->squareType = $squareType;
            $gameSquare->squareId = $i;
            //Calculate and assign how many mines we touch
            $result = $this->calculateAdjoiningMines($i);
            $gameSquare->adjoiningMines = $result["adjoiningMines"];
            $gameSquare->directionsToCheck = $result["directionsToCheck"];
            unset($result);
            //Add our little square into the current row's array
            $currentRow[] = $gameSquare;
            //Check if the next square sits on a new by checking if it's equally divisible by the number of cells in a row
            if (($i + 1) % $this->gridSize == 0) {
                //It is, so let's add the currentRow into our gameGrid array and create a new row for the next loop
                array_push($this->gameGrid, $currentRow);
                $currentRow = [];
            }
        }
        
        return $this->gameGrid;
    }

    /**
     * 
     * Calculates the number of mines this square touches including diagonals
     * 
     * @param int $squareId squareId to check
     * 
     * @return int Number of mines
     */
    public function calculateAdjoiningMines($squareId) {
        //In order to avoid looping each cell and keeping code as efficient as possible, we will narrow down the cells we need to check relative to our current cell and check only those cells directly.
        $directionsToCheck = ['left' => true, 'right' => true, 'up' => true, 'down' => true, "upright" => true, "upleft" => true, "downright" => true, "downleft" => true];
        //Initialize a 0 value for how many mines we're touching
        $adjoiningMines = 0;

        //Validate which directions we need to check, set those we do not need to check to false
        //Validate squares left and right
        //Is this the first or last column in the row?
        //If the next square in the row (our Id + 1) is equally divisible by the size of our grid then we know we are on the last column in the row
        //This is the last col of the row
        if( ($squareId + 1) % $this->gridSize == 0) {
            $directionsToCheck['right'] = false;
            $directionsToCheck['upright'] = false;
            $directionsToCheck['downright'] = false;
        } else if ( $squareId == 0 || $squareId % $this->gridSize == 0 ) {//Equally we check if the square is the first (to avoid Modulo division by 0 error) 
                                                                        // or if the current square is equally divisible by 0 we know it is the first column in the row
            $directionsToCheck['left'] = false;
            $directionsToCheck["upleft"] = false;
            $directionsToCheck["downleft"] = false;
        }
        //Validate squares up/down
        //This is the first row
        if ($squareId < $this->gridSize) {//We know if this squareId is less than the number of squares in the first row then we are on the first row
            $directionsToCheck['up'] = false;
            $directionsToCheck['upright'] = false;
            $directionsToCheck["upleft"] = false;
        } else {
            //This is the last row
            if ($squareId + 1 > $this->squareCount - $this->gridSize) {//Subtract the number of cells in a row from the total number of squares to find the starting squareId for the last row in our grid
                                                                        //Take our squareId and add 1 to compensate for 0 index and check if it exceeds this row to determine if we must check it 
                $directionsToCheck['down'] = false;
                $directionsToCheck["downleft"] = false;
                $directionsToCheck['downright'] = false;
            }
        }

        //Now we loop our directions array
        foreach($directionsToCheck as $direction => $value) {
            //If this direction is set to true then we check the corresponding square
            if ($value) {
                //Initialize a default value
                $squareToCheck = -1;
                switch($direction) {
                    case "left":
                        //We need to take our current Id and subtract by 1 to get the square left of us
                        $squareToCheck = $squareId - 1;
                        break;
                    case "right":
                        //Take our current Id and add 1 to get the square to the right
                        $squareToCheck = $squareId + 1;
                        break;
                    case "up":
                        //current Id minus cells in a row to get the square above
                        $squareToCheck = $squareId - $this->gridSize;
                        break;
                    case "upright":
                        //current Id minus cells in a row takes us above, plus one and we are in the top right cell
                        $squareToCheck = $squareId - $this->gridSize + 1;
                        break;
                    case "upleft":
                        //current Id minus cells in a row minus 1 to get top left cell
                        $squareToCheck = $squareId - $this->gridSize - 1;
                        break;
                    case "down":
                        //current Id plus # cells in a row to get cell directly below
                        $squareToCheck = $squareId + $this->gridSize;
                        break;
                    case "downright":
                        //current Id plus # cells in a row to get cell below then add 1 to get bottom right cell
                        $squareToCheck = $squareId + $this->gridSize + 1;
                        break;
                    case "downleft":
                        //current Id plus # cells in a row to get bottom cell then minus one for bottom left cell
                        $squareToCheck = $squareId + $this->gridSize - 1;
                        break;
                }

                //Check if the square determined above is a mine
                if ($this->getSquareType($squareToCheck) == self::MINE_SQUARE) {
                    //It is, so we increment the number of adjoining mines
                    $adjoiningMines++;
                }

            }
        }

        return ["adjoiningMines" => $adjoiningMines, "directionsToCheck" => $directionsToCheck];
        //return $directionsToCheck; // Would help to save this data to the GameSquare object to assist with revealing adjacent blank squares
    }

    /**
     * Retrieve a square from the gameGrid array, given a squareId
     * 
     * @param int $squareId squareId we are looking for
     * 
     * @return \App\Models\GameSquare
     */
    public function getSquareUsingSquareId($squareId) {
        
        $row = floor($squareId / $this->gridSize);//Divide the square id with the size of the grid and round down to nearest integer to get the index for our first array in gameGrid
        $column = $squareId % $this->gridSize;//Modulo the squareId by grid size to return the key for the second index in which our square resides within the gameGrid array

        $square = $this->gameGrid[$row][$column];//Retrieve the GameSquare from the gameGrid index using our keys found above

        return $square;
        
    }

    /**
     * Return if this square is self::SAFE_SQUARE or self::MINE_SQUARE by checking our mineLocations array
     * 
     * @return int self::SAFE_SQUARE or self::MINE_SQUARE
     */
    public function getSquareType($squareId) {
        //Check if our squareId is within our mineLocations array and then cast this to an int to match our class consts
        return (int)in_array($squareId, $this->mineLocations);
    }

    /**
     * Psuedo-randomly allocates all mines to squares within our gameGrid
     * 
     * Could be improved by using deterministic code
     * 
     * @return array $this->mineLocations current location of all mines
     */
    public function allocateMines() {
        //Create an anonymous reusable function to return a random square within our game grid
        $getRandomMineLocation = function() {
            $mineLocation = rand(0, $this->squareCount - 1);
            return $mineLocation;
        };
        //While the number of items within our mineLocations array is less than the required amount
        while(count($this->mineLocations) < $this->mineCount) {
            //While $mineLocation value is unsset (first iteration) or while the current $mineLocation already exists within our $mineLocations array (prevent duplication assign)
            while(!isset($mineLocation) || in_array($mineLocation, $this->mineLocations)) {
                //Assign a random mine location and validate it with the above loop
                $mineLocation = $getRandomMineLocation();
            }
            //Mine location satisfies our loop so let's add it to our mineLocations array
            array_push($this->mineLocations, $mineLocation);
        }
        //Return our full array of mineLocations
        return $this->mineLocations;
    }

    public function returnAdjoiningBlankSquares($square, &$blankSquaresToReveal)
    {
        foreach($square->directionsToCheck as $direction => $value) {
            //If this direction is set to true then we check the corresponding square
            if ($value) {
                //Initialize a default value
                $squareToCheck = -1;
                switch($direction) {
                    case "left":
                        //We need to take our current Id and subtract by 1 to get the square left of us
                        $squareToCheck = $square->squareId - 1;
                        break;
                    case "right":
                        //Take our current Id and add 1 to get the square to the right
                        $squareToCheck = $square->squareId + 1;
                        break;
                    case "up":
                        //current Id minus cells in a row to get the square above
                        $squareToCheck = $square->squareId - $this->gridSize;
                        break;
                    case "upright":
                        //current Id minus cells in a row takes us above, plus one and we are in the top right cell
                        $squareToCheck = $square->squareId - $this->gridSize + 1;
                        break;
                    case "upleft":
                        //current Id minus cells in a row minus 1 to get top left cell
                        $squareToCheck = $square->squareId - $this->gridSize - 1;
                        break;
                    case "down":
                        //current Id plus # cells in a row to get cell directly below
                        $squareToCheck = $square->squareId + $this->gridSize;
                        break;
                    case "downright":
                        //current Id plus # cells in a row to get cell below then add 1 to get bottom right cell
                        $squareToCheck = $square->squareId + $this->gridSize + 1;
                        break;
                    case "downleft":
                        //current Id plus # cells in a row to get bottom cell then minus one for bottom left cell
                        $squareToCheck = $square->squareId + $this->gridSize - 1;
                        break;
                }

                $squareToCheck = $this->getSquareUsingSquareId($squareToCheck);
                if ($squareToCheck->isSquareBlank()) {
                    $blankSquaresToReveal[] = $squareToCheck->squareId;
                    return $this->returnAdjoiningBlankSquares($squareToCheck, $blankSquaresToReveal);
                }
            }
        }        
    }

}