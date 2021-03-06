<?php
require_once('localsettings/secure/localsettings.php');

    if($batchruns !='Y') {

        if(!isset($_SERVER['HTTPS'])) {
            exit();
        }
        if(BotCheck()){
            exit();
        }
    }

require_once('htmlpurifier-4.8.0-standalone/HTMLPurifier.standalone.php');
require('colorscheme.php');

    $purifierconfig = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($purifierconfig);

    function do_sql_connect( $connectnum, $sqlurl, $usr, $pwd, $database  )
    {
        $mysqli = mysqli_init();
        $temp = explode(":",$sqlurl);
        $url = $temp[0];
        $port = $temp[1];
        $mysqli->ssl_set( NULL , NULL , "/var/www/html/rds-combined-ca-bundle.pem" , NULL,  NULL );
        $mysqli->real_connect($url, $usr,  $pwd, $database, $port);    
        $mysqli->set_charset('utf8');

        if($mysqli->connect_errno){
            echo "<br>SQL Connect Error $connectnum<br>";
            echo $mysqli->connect_error;
            exit();
        }
        return $mysqli;
    }

    /* Connect to all the Databases and initialize Database Objects */

    $dbconnect1 = do_sql_connect( "1", $_SESSION['sqlurl'], $_SESSION['sqlusr'], $_SESSION['sqlpwd'], $_SESSION['database'] );
    $dbconnect2 = do_sql_connect( "2", $_SESSION['sqlurl2'], $_SESSION['sqlusr2'], $_SESSION['sqlpwd2'], $_SESSION['database2'] );
    $dbconnect3 = do_sql_connect( "3", $_SESSION['sqlurl3'], $_SESSION['sqlusr3'], $_SESSION['sqlpwd3'], $_SESSION['database3'] );
    $dbconnect6 = do_sql_connect( "6", $_SESSION['sqlurl6'], $_SESSION['sqlusr6'], $_SESSION['sqlpwd6'], $_SESSION['database6'] );
    $dbconnect_news = do_sql_connect( "news", $_SESSION['sqlurl_news'], $_SESSION['sqlusr_news'], $_SESSION['sqlpwd_news'], $_SESSION['database_news'] );

    
/*******************
 * BRAXPRODUCTION SHARD 1
 *******************/
    function do_mysqli_query( $connect, $query ){
        
        global $dbconnect1;
        global $dbconnect2;
        global $dbconnect3;
        global $dbconnect6;
        global $dbconnect_news;
        
        $db_mysqli['1'] = $dbconnect1;
        $db_mysqli['2'] = $dbconnect2;
        $db_mysqli['3'] = $dbconnect3;
        $db_mysqli['6'] = $dbconnect6;
        $db_mysqli['news'] = $dbconnect_news;
        
        if(!isset($db_mysqli[$connect])){
            echo "$query<br>";
            echo "No Connection Specified in Query<br>";
            exit();
        }
        $result = $db_mysqli[$connect]->query($query);
        if(!$result){
            //echo "$query<br>";
            //echo "Error $db_mysqli[$connect]->error";
            //exit();
        }
        //echo "No Error query<br>";
        //echo "$db_mysqli[$connect]->error";
        //exit();
        return $result;

    }
    
    function do_mysqli_fetch($connect, $result ){
        if(!$result)
        {
            return false;
        }
        if($result->num_rows == 0){
        //    return false;
        }
        
        $row =  $result->fetch_assoc();
        return $row;
    }
    function do_mysqli_fetch_row($connect, $result ){
        
        if($result->num_rows == 0){
            return false;
        }
        return $result->fetch_row();
        
    }
    function do_mysqli_fetch_array($connect, $result ){
        if($result->num_rows == 0){
            return false;
        }
        
        return $result->fetch_array();
        
    }
    
    



    
/*******************
 * BRAXPRODUCTION SHARD 2
 *******************/
    

    //do_mysqli_query("1", "SET NAMES 'utf8'");

    
/*******************
 * BRAXPRODUCTION SHARD 3
 *******************/
    
/*******************
 * BRAXPRODUCTION SHARD 6
 *******************/
    
    
/*******************
 * BRAXPRODUCTION SHARD NEWS
 *******************/


/*********************
 * COMMON FUNCTIONS
 *********************/


    function purify_string($string)
    {
        global $purifier;
        
        if(TrapJs($string)){
            return "";
        }

        if( isset($string)){

            return $purifier->purify( $string);
        } else {
            return "";
        }

    }
    function mysql_safe_string($string)
    {
        global $purifier;
        global $dbconnect1;
        
        if(TrapJs($string)){
            return "";
        }

            //$clean_html = $purifier->purify($dirty_html);
            if( isset($string)){

                return $purifier->purify( mysqli_real_escape_string($dbconnect1, $string));
                //return mysql_real_escape_string($string);
            } else {
                return "";
            }

    }
    function mysql_safe_string_unstripped($string)
    {
        global $dbconnect1;
        
            if( isset($string)){

                    return mysqli_real_escape_string($dbconnect1, $string);
            } else {
                return "";
            }

    }
    function mysql_isset_safe_string($isset, $string)
    {
        global $purifier;
        global $dbconnect1;
        
        if(TrapJs($string)){
            return "";
        }
        
        if(!$isset){

            return "";
        }
        
            
        return $purifier->purify( mysqli_real_escape_string($dbconnect1, $string));
        //return (mysql_real_escape_string($string));

    }

    //function mysql_fetch_assoc($connect)
    //{
    //    return mysql_fetch_assoc($connect);
    //}

    function SaveLastFunction( $providerid, $func, $parm1 )
    {
        if($providerid == ''){
            return;
        }
        if(!isset($_SESSION['loginid'])){
            return;
        }
        if(isset($_SESSION['deviceid'])){
            $deviceid = @mysql_safe_string($_SESSION['deviceid']);
            //$devicecode = @$_SESSION['devicecode'];
        } else {
            $deviceid = "";
        }

        do_mysqli_query("1",
                "delete from lastfunc where providerid=$providerid and (deviceid='' or deviceid='$deviceid'
                 or  datediff(now(),funcdate) > 1 ) "
                );
        do_mysqli_query("1","insert into lastfunc (providerid, deviceid, func, parm1, funcdate )
                    values ($providerid, '$deviceid', '$func','$parm1',now() )
                        ");
        if( $func == 'R')
        {
            $parm1 = intval($parm1);
            do_mysqli_query("1","update provider set lastroomid = $parm1 where providerid=$providerid");
        }
        do_mysqli_query("1","
            update staff set lastaccess=now() where providerid= $providerid and loginid='$_SESSION[loginid]'
        ");

    }

    function GetLastFunction( $providerid, $timelimit )
    {

        if($providerid == '') {
            $arr['elapsed']='';
            $arr['lastfunc']='';
            $arr['parm1']='';
            return (object) $arr;
        }
        $deviceid = @mysql_safe_string($_SESSION['deviceid']);
        /*
        $result = do_mysqli_query("1","
            select timestampdiff(SECOND, lastfuncdate, now()) as elapsed, lastfunc, lastfuncparm1 from provider where providerid=$providerid
               ");
        */
        $result = do_mysqli_query("1","
            select timestampdiff(SECOND, funcdate, now()) as elapsed, func, parm1 from lastfunc where providerid=$providerid
                and deviceid='$deviceid' order by funcdate desc
               ");


        if( $row = do_mysqli_fetch("1",$result) )
        {
            $elapsed = intval($row['elapsed']);
            if($elapsed < $timelimit || intval($timelimit)===0 )
            {
                $arr['elapsed']="$row[elapsed]";
                $arr['lastfunc']="$row[func]";
                $arr['parm1']="$row[parm1]";
                return (object) $arr;
            }
        }
        $arr['elapsed']='';
        $arr['lastfunc']='';
        $arr['parm1']='';
        return (object) $arr;
    }

    function LogDebug( $providerid, $event )
    {
        if($providerid == ''){
            $providerid = 0;
        }
        $event = mysql_safe_string($event);
        do_mysqli_query("1","insert into debuglog (providerid, logdate, event ) values ($providerid, now(), '$event' ) ");

    }
    function InternetTooSlow()
    {
        //if( intval($_SESSION['iscore'])< 2 ){
        //    return true;
        //}
        return false;
    }
    function ActiveInformationRequest($providerid)
    {
        
        $result = do_mysqli_query("1"," 
            select *
            from credentialformtrigger
            where providerid = $providerid and status='N'
                ");
        if($row = do_mysqli_fetch("1",$result)){

            return 'Y';
        }
        return 'N';

    }
    function EncryptE2EPasskey($passkey,$salt)
    {
        if($passkey==''){
            return $passkey;
        }
        $passkey64 = EncryptJs($passkey,$salt);
        return $passkey64;
    }
    function DecryptE2EPasskey($passkey64,$salt)
    {
        if($passkey64==''){
            return $passkey64;
        }
        $passkey = DecryptJs($passkey64,$salt);
        return $passkey;
    }
    function TrapJs($string)
    {
        //$test = rawurldecode($string);
        if(strstr(strtolower($string),"javascript:")!==false){
            return true;
        }
        return false;
    }
    
    function BotCheck() {

      if (isset($_SERVER['HTTP_USER_AGENT']) 
              && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) {
          
        return TRUE;
        
      } else {
          
        return FALSE;
        
      }

    }
    function HttpsWrapper($link)
    {
        global $installfolder;
        //return $text;
        if(strstr(strtolower($link),"https://")!==false){
            return $link;
        }
        $shortlink = $link;
        if(substr( strtolower($link),0,7 )!="http://"){
            $shortlink = substr($link,7);
                
        } 
        
        $wrapper = "https://brax.me/$installfolder/wrap.php?u=" . $shortlink;

        return $wrapper;
    }    
    function CheckLiveStream($streamid)
    {
            $ch = curl_init("https://audio.brax.live:8443/$streamid");

            if($ch!== false ){

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                $response = curl_exec($ch);

                //close connection
                curl_close($ch);
            } else {
                $response = "";
            }

            if(strstr($response, 'could not be found')!== false ){

                return false;
            }

            return true;

    }
    function GetTimeoutPin($providerid)
    {
        $_SESSION['pin']='';
        $result = do_mysqli_query("1","select pin, encoding from timeout where providerid = $providerid ");
        if( $row = do_mysqli_fetch("1",$result)){
            $_SESSION['pin'] = $row['pin'];
        }
        if(intval($_SESSION['timeout_seconds'])==0){
            $_SESSION['pin'] = "";
        }

    }
    function TimeOutCheck()
    {
        if(!isset($_SESSION['pinlock'])){
            return false;
        }
        if(!isset($_SESSION['pin'])){
            return false;
        }

        if(
           $_SESSION['pinlock']!='Y' && 
           $_SESSION['pin']!='' && 
           intval($_SESSION['timeout_seconds'])>0){

                $t = time();
                $t2 = $_SESSION['timeoutcheck'];
                if($t - $t2 > $_SESSION['timeout_seconds'] ){
                    //GetTimeoutPin($_SESSION['pid']);
                    //echo "$_SESSION[pin]";
                    return true;

                }
        }
        return false;
        
    }
    function ServerTimeOutCheck()
    {
        if(!isset($_SESSION['pid']) || $_SESSION['pid']=='') //Invalid Session
        {
            $_SESSION['reset']='Y';
            return true;
        }
        return false;
    }
    function StripEmojis($text)
    {
        
        return preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1FFFF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
        
    }
    function RootServerReplace($url)
    {
        global $rootserver;
        
        return str_replace("https://brax.me","$rootserver", $url);
        
    }
    function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }
    
?>