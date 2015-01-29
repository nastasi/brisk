<?php
/*
 *  brisk - donometer.php
 *
 *  Copyright (C) 2006-2015 Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it 
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABLILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details. You should have received a
 * copy of the GNU General Public License along with this program; if
 * not, write to the Free Software Foundation, Inc, 59 Temple Place -
 * Suite 330, Boston, MA 02111-1307, USA.
 *
 */

$G_base = "";

require_once("Obj/brisk.phh");


$im = @imagecreatetruecolor(320, 200)
      or die("Cannot Initialize new GD image stream");

// allocate some solors
// $white      = imagecolorallocate($im, 0xfa, 0xfa, 0xfa);
$bg         = imagecolorallocate($im, 0xff, 0xd7, 0x80);
$gray       = imagecolorallocate($im, 0x70, 0x70, 0x70);
$darkgray   = imagecolorallocate($im, 0x00, 0x00, 0x00);
$orange     = imagecolorallocate($im, 0xff, 0xae, 0x00);
$darkorange = imagecolorallocate($im, 0xc4, 0x86, 0x00);

imagefilledrectangle($im, 0,0, 399,399, $bg);

// make the 3D effect
$delta = ($G_donors_cur * 360) / $G_donors_all;

$y = 90;

imagefilledellipse($im, 160, $y + 16, 300, 150, $darkgray);
imagefilledellipse($im, 160, $y + 17, 299, 149, $darkgray);

for ($i = $y+15 ; $i > $y ; $i--) {
  imagefilledarc($im, 160, $i, 300, 150, 270, 270 + $delta, $darkorange, IMG_ARC_PIE);
  imagefilledarc($im, 160, $i, 300, 150, 270 + $delta, 630, $darkgray, IMG_ARC_PIE);
}

imagefilledarc($im, 160, $y, 300, 150, 270, 270 + $delta, $orange, IMG_ARC_PIE);
imagefilledarc($im, 160, $y, 300, 150, 270 + $delta, 630, $gray, IMG_ARC_PIE);


imagefilledarc($im, 160, $y, 298, 148, 270, 270 + $delta, $darkgray, IMG_ARC_EDGED | IMG_ARC_NOFILL);
imagefilledarc($im, 160, $y, 300, 150, 270, 270 + $delta, $darkgray, IMG_ARC_EDGED | IMG_ARC_NOFILL);
imagefilledarc($im, 160, $y, 298, 148, 270 + $delta, 630, $darkgray, IMG_ARC_EDGED | IMG_ARC_NOFILL);
imagefilledarc($im, 160, $y, 300, 150, 270 + $delta, 630, $darkgray, IMG_ARC_EDGED | IMG_ARC_NOFILL);


$image_p = imagecreatetruecolor(80,50);
imagecopyresampled($image_p, $im, 0, 0, 0, 0, 80, 50, 320, 200);

header ("Content-type: image/png");
imagepng($image_p);
imagedestroy($im);
imagedestroy($image_p);
?> 
