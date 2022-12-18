<?php

/**
 * Simple Minesweeper game
 * 
 * @author Ollie <general@ollie.im>
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Minesweeper as Game;

class Minesweeper extends Controller
{
    /**
     * /game web Route
     * 
     * @param $request Request
     * 
     * @return View Laravel view
     */
    public function game(Request $request)
    {
        $mineCount = 10;
        $gridSize = 10;
        $mineGame = new Game($gridSize, $mineCount, true);
        $request->session()->put('game', $mineGame);

        //$mineGame = $request->session()->get('game');
        //dump($mineGame);
        return view('minesweeper.game')->with('game', $mineGame->gameGrid)->with('gameState', $mineGame->gameState)->with('mineCount', $mineCount)
            ->with('showOnlyMines', true);
    }

    /**
     * /spec1 and /spec2 web Route
     * 
     * @param $request Request
     * 
     * @return View Laravel view
     */
    public function specOneAndTwo(Request $request)
    {
        $showOnlyMines = (int)($request->getPathInfo() == "/spec1");//Bit of a nasty hack for temporary code to prevent code reuse
        $gridSize = 8;
        $mineCount = 10;
        $mineGame = new Game($gridSize, $mineCount, false);
        return view('minesweeper.spec1and2')->with('game', $mineGame->gameGrid)->with('showOnlyMines', $showOnlyMines);
    }

    #region API Functions
    /**
     * Client POST request to activate/reveal a squareId
     * 
     * /game/api/activate api Route
     * 
     * @param $request Request
     * 
     * @return ResponseJson
     */
    public function gameActivateCell(Request $request)
    {
        $mineGame = $request->session()->get('game');
        $squareId = $request->get('squareId');
        $square = $mineGame->getSquareUsingSquareId($squareId);
        $blankSquaresToReveal = [];
        if ($square->isSquareBlank()) {
            //Find all adjoining blank squares
            //$mineGame->returnAdjoiningBlankSquares($square, $blankSquaresToReveal);//Doesn't work atm
        }
        $returnData = [
            "action" => "reveal",
            "adjoiningMines" => $square->isMine() ? 0 : $square->adjoiningMines,//Hide adjoiningMines for mine squares to prevent cheating
            "squareId" => $square->squareId,
            "squareType" => $square->squareType,
        ];

        return response()->json($returnData);
    }

    /**
     * Client POST request to start a new game
     * 
     * @param $request Request
     * 
     * @return ResponseJson
     */
    public function newGame(Request $request)
    {
        $gridSize = $request->get('gridSize');
        $mineCount = $request->get('mineCount');
        $mineGame = new Game($gridSize, $mineCount, true);
        $request->session()->put('game', $mineGame);

        return response()->json([
            'game' => $mineGame->gameGrid,
            'gameState' => $mineGame->gameState,
            'mineCount' => $mineCount,
        ]);

    }
    #endregion
}
