<html>
<head>
<title><?php echo "$PHP_SELF";?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="commons.js"></script>

<!-- <script type="text/javascript" src="myconsole.js"></script> -->

<script type="text/javascript"><!--
     var sess = "for_test";
     var stat = "";
     var subst = "";
     var gst = new globst();
     var nd;
     var tva;

     window.onload = function() {
         var i, sss = "";

         for (i = 0 ; i < 20 ; i++) {
             sss += "solo una prova<br>";
         }

         nd = new notify_document(gst, sss, 4000, [ "pippo", "pluto", "paperino" ], 200, 200, true, 2000);

         tva = setInterval(function(ndd){ console.log("nd.ret = "+ndd.ret_get()); }, 3000, nd);
     }
 //-->
</script>
<link rel="stylesheet" type="text/css" href="brisk.css">
</head>
         <!--  style="position: static; width: 800px; height: 600px; background-color: yellow;" -->
<body>
         <!--  style="position: static; width: 600px; height: 400px; background-color: pink;" -->
<div>
div in body
</div>
</body>
</html>
