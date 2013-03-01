<?php
$comps = $_GET['comp'];
$c = array();

if (mb_strlen($comps, "ASCII") != 12)
    exit;

for ($i = 0, $idx = 0 ; $i < 12 ; $i++) {
    if (($comps[$i] >= '0' && $comps[$i] <= '9') ||
        ($comps[$i] >= 'a' && $comps[$i] <= 'f')) {
        if (($i % 2) == 1) {
            $c[$idx] = hexdec(substr($comps, $i-1, 2));
            $idx++;
        }
        continue;
    }
    exit;
}

header ('Content-type: image/png');
$img_r = @imagecreatefrompng("img/sup_msk_r.png");
$img_y = @imagecreatefrompng("img/sup_msk_y.png");

$ret = imagefilter($img_r, IMG_FILTER_COLORIZE, $c[0], $c[1], $c[2], 0);
$ret = imagefilter($img_y, IMG_FILTER_COLORIZE, $c[3], $c[4], $c[5], 0);

imagecopy($img_r, $img_y, 0,0, 0,0, 21, 16);

imagesavealpha($img_r, TRUE);

imagepng($img_r);
?>
