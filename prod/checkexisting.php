<?php
session_start();
require_once("config.php");

$email = @mysql_safe_string($_POST['email']);

    if( $email == ''){
        Error("handletaken","Email is required");
        exit();
    }
    if( $email[0]=='@'){
        
        if( !CheckHandleDuplicate($email) ){
            
            $seed = 0;
            $alt = "";
            while($alt ==''){
                $seed+=1;
                $alt = GetAlt($email, $seed);
            }
            Error("handletaken","$email is not available. Alternative suggested $alt.",$alt);
            exit();
            
        }
        $banned = strstr($email, "braxme");
        if($banned !== false){
            Error("handletaken","$email is reserved. Please try another handle.",'');
            exit();
            
        }
    }

    $result = do_mysqli_query("1",
    
        "select replyemail from provider where replyemail = '$email' and active='Y' "
    );
    if( $row = do_mysqli_fetch("1",$result))
    {
        Error("emailtaken","$email is an existing account. Please use Forgot Password on the Login Page to access your existing account.",'');
        exit();
    }
    
    function Error($error, $msg, $alt)
    {
        $arr = array('error'=> "$error",
                     'msg'=> "$msg",
                     'alt'=> "$alt"
                    );


        echo json_encode($arr);
        exit();
    }
    function CheckHandleDuplicate($handle)
    {
        $result = do_mysqli_query("1",
        
            "select handle from provider where handle = '$handle' and active='Y' "
        );
        if( $row = do_mysqli_fetch("1",$result)){
            return false;
        }
        return true;
        
    }
    function GetAlt($handle, $seed)
    {
        $alt = $handle.$seed;
        if(CheckHandleDuplicate($alt)){
            return $alt;
        }
        return "";
    }
    
    
    
    
    Error("","","");
    
    exit();
    
?>