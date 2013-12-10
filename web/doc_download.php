<?php
require_once("Obj/brisk.phh");

function main()
{
    GLOBAL $G_doc_path, $_GET; 

    if (! isset($_GET['doc'])) {
        return(FALSE);
    }

    $fname = sprintf("%s%s.pdf", $G_doc_path, basename($_GET['doc']));
    if (! file_exists($fname)) {
        return(FALSE);
    }

    header("Content-Type: application/octet-stream");
    header(sprintf("Content-Disposition: attachment; filename=brisk_%s", basename($fname)));
    readfile($fname);
    return(TRUE);
}

main();
?>