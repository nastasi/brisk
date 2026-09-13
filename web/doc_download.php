<?php
$G_base = "";

/* Obj/brisk.phh needs $DOCUMENT_ROOT to find the configuration file under
   Etc/. With mod_php it was not filled in by itself and this script failed;
   it is taken from $_SERVER, as usermgmt.php, mailmgr.php and the others
   already do. */
foreach (array("HTTP_HOST", "DOCUMENT_ROOT") as $i) {
    if (isset($_SERVER[$i])) {
        $$i = $_SERVER[$i];
        }
    }

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
