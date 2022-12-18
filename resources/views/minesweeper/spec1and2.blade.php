@extends('minesweeper.template.header')

@section('gameContent')
<tr v-for="row in gameGrid" >
  <td v-for="cell in row" class="gamecell" colspan="1">@{{ printCellGameInfo(cell) }}</td>
</tr>
@endsection


@section('scripts')
var vm = new Vue({
  el: '#app',
  data: {
    gameGrid: {!! json_encode($game) !!}
  },
  methods: {
    printCellGameInfo: function (cell, showOnlyMines) {
      if (cell.squareType == {{ \App\Models\Minesweeper::MINE_SQUARE }} ) {
        return 'X';
      }
      if (cell.adjoiningMines && !{{$showOnlyMines}}) {
        return cell.adjoiningMines;
      }
      return "\xa0\xa0";
    },
  },
})
@endsection