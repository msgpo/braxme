<?php
session_start();
require("validsession.inc.php");
require_once("config.php");

    $providerid = mysql_safe_string("$_SESSION[pid]");

    $broadcasttype = mysql_safe_string("$_POST[broadcasttype]");
    $channel = mysql_safe_string("$_POST[channel]");
    $title = base64_encode(mysql_safe_string("$_POST[title]"));
    if($broadcasttype ==''){
        exit();
    }
    if($broadcasttype=='braxlive' || $broadcasttype=='webcam'){
        $channel = "";
    }
    if($channel == '' && $broadcasttype!='braxlive' && $broadcasttype!='webcam' ){
        $result = do_mysqli_query("1","select channel, title from streamingaccounts where providerid = $providerid and videotype='$broadcasttype'   ");
        if($row = do_mysqli_fetch("1",$result)){
            $title_decoded = base64_decode($row['title']);
            $arr = array('channel'=> "$row[channel]",
                         'title' => "$title_decoded"
                        );
            echo json_encode($arr);
            exit();
        }
    }
    

    $result = do_mysqli_query("1", 
            " update provider set streamingaccount='$broadcasttype/$channel' where providerid=$providerid "
            );
    
    $result = do_mysqli_query("1", 
            " delete from streamingaccounts where providerid = $providerid and videotype='$broadcasttype' "   
            );
    
        
    $result = do_mysqli_query("1", 
            " insert into streamingaccounts( providerid, videotype, channel, title ) values ($providerid, '$broadcasttype','$channel','$title') "
            );
