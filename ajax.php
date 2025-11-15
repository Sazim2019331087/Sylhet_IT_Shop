<html>
    <head>
        <title>AJAX</title>
        <script src="js/jquery.min.js"></script>
    </head>
    <body>
        
        <div id="result">
            <button id="dec">Decrement</button>
            <span id="amount">0</span> Pieces
            <button id="inc">Increment</button>
            <br>
            Total Price: <span id="price">0</span> Tk
            <button id="reset">Reset All</button>
        </div>

        <div id="notify">

        </div>
        
        <script>
            $(document).ready(function(){
                $("#dec").click(
                    function(){
                        $.ajax({
                            method: "POST",
                            url: "decrement.php",
                            data: {
                                amount: $("#amount").text()
                            },
                            success: function(response){
                                data = JSON.parse(response);
                                $("#amount").text(data.amount);                
                                $("#price").text(data.price); 
                            }
                        });
                    }
                );

                $("#inc").click(
                    function(){
                        $.ajax({
                            method: "POST",
                            url: "increment.php",
                            data: {
                                amount: $("#amount").text()
                            },
                            success: function(response){
                                data = JSON.parse(response);
                                $("#amount").text(data.amount);
                                $("#price").text(data.price);                 
                            }
                        });
                    }
                );
                
                $("#reset").click(
                    function(){
                        $("#price").text(0);
                        $("#amount").text(0);
                        $("#notify").html("<h1>Price reset</h1>").css({"color":"green","fontSize":"30px"}).fadeIn(2000).delay(3000).fadeOut(1000);
                    }
                );

                
            });
        </script>
    </body>
</html>
