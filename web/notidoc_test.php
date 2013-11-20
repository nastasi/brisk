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
         var i, noti_content = "";

         for (i = 0 ; i < 20 ; i++) {
             noti_content += "solo una prova<br>";
         }

         nd = new notify_document(gst, noti_content, -1, [ "pippo", "pluto", "paperino" ], 200, 200, true, 0);

         tva = setInterval(function(nd){ console.log("nd.ret = "+nd.ret_get()+"  gst.st_loc: "+gst.st_loc+"  gst.st_loc_new: "+gst.st_loc_new  ); }, 1000, nd);
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
