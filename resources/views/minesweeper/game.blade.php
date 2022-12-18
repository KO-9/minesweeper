@extends('minesweeper.template.header')

@section('topInstructions')
<div style="width: 80%" class="center">
<p>Left click to activate square.<br />
Right click to mark as potential mine</p>
</div>
@endsection

@section('gameContent')
<tr v-for="row in gameGrid" >
  <td v-for="cell in row" :class="gameClass(cell)" colspan="1" v-on:click="activate(cell)" v-on:contextmenu="setFlag($event, cell)">@{{ printCellGameInfo(cell) }}</td>
</tr>
@endsection

@section('belowGame')
<div style="width: 80%" class="center">
    <div :class="messageClass">
        @{{ bottomMessage }}
        <div :class="bottomButtonClass">
            <button v-on:click="newGame">Play again</button>
            <label for="newGameGridSize">Grid Size</label> <input type="text" name="newGameGridSize" v-model="newGameGridSize"></input>
            <label for="newGameMineCount">Mine Count</label> <input type="text" name="newGameMineCount" v-model="newGameMineCount"></input>
        </div>
    </div>
</div>
@endsection

@section('scripts')
var vm = new Vue({
  el: '#app',
  data: {
    gameState: {{ $gameState }},
    gameGrid: {!! json_encode($game) !!},
    mineCount: {{ $mineCount }},
    squaresActivated: 0,
    newGameGridSize: {{ count($game[0]) }},
    newGameMineCount: {{ $mineCount }},
    messageClass: "",
    bottomButtonClass: "",
  },
  computed: {
    gridSize: function() {
        return this.gameGrid[0].length
    },
    bottomMessage: function() {//Message to be shown under the game board
        let msg;
        switch(this.gameState) {
            case {{ \App\Models\Minesweeper::GAMESTATE_ACTIVE }}:
                msg = (this.gridSize * this.gridSize) - this.mineCount - this.squaresActivated + " safe squares left";
                break;
            case {{ \App\Models\Minesweeper::GAMESTATE_WON }}:
                msg = "You win!";
                break;
            case {{ \App\Models\Minesweeper::GAMESTATE_LOST }}:
                msg = "You lost :(";
                break;
        }

        return msg;
    },
  },
  methods: {
    gameClass: function (cell) {
        //Determine which classes to use for the table td cells
        let tdclass = "gamecell";
        if (!cell.revealed) {//If the cell hasn't been revealed yet we want it to be coloured
            if (!cell.flagged) {
                let row = Math.floor(cell.squareId / this.gridSize)
                let colourOne = "lightgreen"
                let colourTwo = "darkgreen";
                if (row % 2 == 0) {//Flip the colours on alternate rows for checkered pattern
                    colourOne = "darkgreen";
                    colourTwo = "lightgreen";
                }
                if (cell.squareId % 2 == 0) {//Alternate square colours to make it a bit easier on the eyes
                    tdclass = `${tdclass} ${colourOne}`;
                } else {
                    tdclass = `${tdclass} ${colourTwo}`;
                }
            } else {
                tdclass = `${tdclass} red`;
            }
        }
        return tdclass;
    },
    printCellGameInfo: function (cell, showOnlyMines) {
      if (!cell.revealed) {
        return "\xa0\xa0";
      }
      if (cell.squareType == {{ \App\Models\Minesweeper::MINE_SQUARE }} ) {
        return 'X';
      }
      if (cell.adjoiningMines) {
        console.log(showOnlyMines)
        return cell.adjoiningMines;
      }
      return "\xa0\xa0";
    },
    getSquareUsingSquareId: function (squareId) {
        //let gridSize = vm.gameGrid[0].length;
        let row = Math.floor(squareId / vm.gridSize);
        let column = squareId % vm.gridSize

        return vm.gameGrid[row][column];
    },
    setFlag: function (event, cell) {
        cell.flagged = !cell.flagged;
        event.preventDefault();
        console.log(cell);
    },
    activate: function (cell) {
        if ( this.gameState != {{ \App\Models\Minesweeper::GAMESTATE_ACTIVE }} ) {
            //Hold up..
            return;
        }
        const postData = { "_token": "{{ csrf_token() }}", squareId: cell.squareId }
        axios.post('{{ route('game.activateCell') }}', postData).then(function (response) {
            let square = vm.getSquareUsingSquareId(response.data.squareId);
            square.revealed = true;
            square.squareType = response.data.squareType;
            square.adjoiningMines = response.data.adjoiningMines;
            if (square.squareType == {{ \App\Models\Minesweeper::MINE_SQUARE }} ) {
                //Oh no we lost :(
                vm.gameState = {{ \App\Models\Minesweeper::GAMESTATE_LOST }}
            } else {
                vm.squaresActivated++;
            }
        });
    },
    newGame: function() {
        const postData = { "_token": "{{ csrf_token() }}", gridSize: this.newGameGridSize, mineCount: this.newGameMineCount }
        axios.post('{{ route('game.newGame') }}', postData).then(function (response) {
            vm.gameGrid = response.data.game;
            vm.gameState = response.data.gameState;
            vm.mineCount = response.data.mineCount;
            vm.squaresActivated = 0;
        });
    },
  },
})
@endsection