<?php

?>
<html>
<head>
<title>WS</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="xynt-streaming.js"></script>
<script type="text/javascript" src="commons.js"></script>
<script type="text/javascript" src="heartbit.js"></script>

<!-- <script type="text/javascript" src="myconsole.js"></script> -->

<script type="text/javascript"><!--
     var socket;

     window.onload = function() {
        var host = "ws://dodo.birds.van/brisk/xynt_test01.php?isstream=true&transp=websocket&f_test=1"; // SET THIS TO YOUR SERVER

        console.log("QUI");
        try
        {
            socket = new WebSocket(host);
            console.log('WebSocket - status ' + socket.readyState);

            socket.onopen = function(msg)
            {
                if(this.readyState == 1)
                {
                    console.log("We are now connected to websocket server. readyState = " + this.readyState);
                }
            };

            //Message received from websocket server
            socket.onmessage = function(msg)
            {
                console.log(" [ + ] Received: " + msg.data);
            };

            //Connection closed
            socket.onclose = function(msg)
            {
                console.log("Disconnected - status " + this.readyState);
            };

            socket.onerror = function()
            {
                console.log("Some error");
            }
        }

        catch(ex)
        {
            console.log('Some exception : '  + ex);
        }

     };
 //-->
</script>
</head>
<body>