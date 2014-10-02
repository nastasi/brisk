<?php
require_once("Obj/brisk.phh");

function main()
{
    GLOBAL $G_doc_path, $_GET; 

    if (! isset($_GET['doc'])) {
        return(FALSE);
    }

    $ext = "pdf";
    $cont_type = "application/octet-stream";
    if (isset($_GET['ext']) && ($_GET['ext'] == "txt")) {
        $cont_type = "plain/text";
        $ext = $_GET['ext'];
    }

    $fname = sprintf("%s%s.%s", $G_doc_path, basename($_GET['doc']), $ext);
    if (! file_exists($fname)) {
        return(FALSE);
    }

    header(sprintf("Content-Type: %s", $cont_type));
    header(sprintf("Content-Disposition: attachment; filename=brisk_%s", basename($fname)));
    readfile($fname);
    return(TRUE);
}

main();
?>
