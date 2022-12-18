<!DOCTYPE html>

<style>
table, th, td {
  border: 1px solid black;
}

.center {
  margin-left: auto;
  margin-right: auto;
}

.gamecell {
    text-align: center;
    cursor: pointer;
}

.lightgreen {
    background-color: rgb(0,100,0);
}

.darkgreen {
    background-color: rgb(0,255,0);
}

.red {
    background-color: rgb(200,0,0);
}
</style>


<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.2.1/axios.min.js" integrity="sha512-zJYu9ICC+mWF3+dJ4QC34N9RA0OVS1XtPbnf6oXlvGrLGNB8egsEzu/5wgG90I61hOOKvcywoLzwNmPqGAdATA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<div id="app">
    @yield('topInstructions')
    <table width="80%" class="center">
        @yield('gameContent')
    </table>
    @yield('belowGame')
</div>

<script>
@yield('scripts')
</script>
</html>