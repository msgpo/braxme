<?php
session_start();
include("config.php");
require("aws.php");

$share = @mysql_safe_string( $_GET['p'] );
$alias = @mysql_safe_string( $_GET['a'] );
$open = @mysql_safe_string($_GET['o']);

header("Content-Type: application/octet-stream");

if( $alias == ''){

    $result = do_mysqli_query("1","
            select filename, filetype, folder, title, comment, views, likes from photolib where filename='$share' and (providerid=$_SESSION[pid] or providerid=0 )
            ");
} else {
    
    $result = do_mysqli_query("1","
            select filename, filetype, folder, title, comment, views, likes from photolib where alias='$alias' and providerid=$_SESSION[pid] 
            ");
}
if( !$row = do_mysqli_fetch("1",$result)){

    header("Content-Disposition: filename='expired.jpg'");

    $filename = "$rootserver/img/expired.jpg";


    if ($fd = fopen ($filename, "rb")) {

        fpassthru($fd);
        fclose( $fd);
        exit();
    }
}

$filename = "$rootserver/$installfolder/$row[folder]$row[filename]";

$download_filename = $appname."."."$row[filetype]";

//Ubuntu Touch - Can't get permission to auto-download so pop in browser
if($_SESSION['mobiledevice']=='U'){
    $awsurl = getAWSObjectUrlShortTerm( $row['filename']);

    header('Location: '.$awsurl);
    exit();
}


header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$download_filename");
header("Cache-control: private;no-cache"); //prevent proxy caching

echo getAwsObject($row['filename']);

/*
if ($fd = fopen ($filename, "rb")) {

    fpassthru($fd);
    fclose( $fd);
    exit();
}
*/
