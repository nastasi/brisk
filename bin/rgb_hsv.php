#!/usr/bin/php
<?php
function RGB_to_HSV ($norm, $R, $G, $B)  // RGB Values:Number 0-255
{                                 // HSV Results:Number 0-1
   $HSL = array();

   $var_R = ($R / $norm);
   $var_G = ($G / $norm);
   $var_B = ($B / $norm);

   $var_Min = min($var_R, $var_G, $var_B);
   $var_Max = max($var_R, $var_G, $var_B);
   $del_Max = $var_Max - $var_Min;

   $V = $var_Max;

   if ($del_Max == 0)
   {
      $H = 0;
      $S = 0;
   }
   else
   {
      $S = $del_Max / $var_Max;

      $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
      $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
      $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

      if      ($var_R == $var_Max) $H = $del_B - $del_G;
      else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
      else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

      if ($H<0) $H++;
      if ($H>1) $H--;
   }

   $HSL = array($H * $norm, $S * $norm, $V * $norm);

   return $HSL;
}

function HSV_to_RGB($norm, $H, $S, $V) {
    $H /= $norm;
    $S /= $norm;
    $V /= $norm;
    //1
    $H *= 6;
    //2
    $I = floor($H);
    $F = $H - $I;
    //3
    $M = $V * (1 - $S);
    $N = $V * (1 - $S * $F);
    $K = $V * (1 - $S * (1 - $F));
    //4
    switch ($I) {
        case 0:
            list($R,$G,$B) = array($V,$K,$M);
            break;
        case 1:
            list($R,$G,$B) = array($N,$V,$M);
            break;
        case 2:
            list($R,$G,$B) = array($M,$V,$K);
            break;
        case 3:
            list($R,$G,$B) = array($M,$N,$V);
            break;
        case 4:
            list($R,$G,$B) = array($K,$M,$V);
            break;
        case 5:
        case 6: //for when $H=1 is given
            list($R,$G,$B) = array($V,$M,$N);
            break;
    }
    return array($R * $norm, $G * $norm, $B * $norm);
}

function main()
{
    GLOBAL $argv;
    
    if ($argv[1] == "-tohsv" || $argv[1] == "-toxhsv") {
        $a = RGB_to_HSV($argv[2], $argv[3], $argv[4], $argv[5]);
        if ($argv[1] == "-tohsv") {
            printf("%f,%f,%f\n", $a[0], $a[1], $a[2]);
        }
        else {
            printf("%02x%02x%02x\n", (int)$a[0], (int)$a[1], (int)$a[2]);
        }
    }
    
    if ($argv[1] == "-torgb" || $argv[1] == "-toxrgb") {
        $a = HSV_to_RGB($argv[2], $argv[3], $argv[4], $argv[5]);
        if ($argv[1] == "-torgb") {
            printf("%f,%f,%f\n", $a[0], $a[1], $a[2]);
        }
        else {
            printf("%02x%02x%02x\n", (int)$a[0], (int)$a[1], (int)$a[2]);
        }
    }
}

main();

?>