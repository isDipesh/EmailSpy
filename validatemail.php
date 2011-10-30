<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function checkError($r){
    //if the error code starts from 4, it's a temoporary error
        if(substr($r, 0, 1)==='4'){echo "<br><font color='red'>Temporary Error Occured! Please try again later!</font>";
        return 6;
        }
        //if the error code starts from 5, it's a permanent error
        else if(substr($r, 0, 1)==='5'){echo "<br><font color='red'>Permanent Error Occured!</font>";
        return 7;
        }
        else {return 0;}
        }

 function my_checkdnsrr($d){
         return checkdnsrr($d.'.','A');
    }

$email=$_GET['e'];
?>
<html>
    <head>
        <title>Validating <?php echo $email?></title>
    </head>
</html>
<?php
echo "The address is $email<br>";
$result =  VerifyEmail ($email);
echo ("<br><br>The result is :-    $result");


/*
 * 0- the email address is valid
 * 1- No e-mail address provided
 * 2 - improper format, failed regex test
 * 3 - the domain doesn't exist
 * 4 - the domain exists but validation of its users is not supported
 * 5 - the domain has no mail exchange records
 * 6 - temporary error occured
 * 7 - permanent error occured
 * 8 - the e-mail address is rejected by the SMTP server
 * 9 - Other error ocurred.
*/


function VerifyEmail ($email) {

    $result = array();

    #Use a regular expression to make sure the $Email string is in proper format
    #<username>@<domain>.<suffix> || <username>@<subnet>.<domain>.<suffix>
    if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email)) {
        echo "<font color='red'>The format of the e-mail address is not correct!</font><br>";
        return 2;
    }
    echo "<font color='green'>The format of the e-mail address is correct!</font><br>";

    list ($username, $domain) = preg_split ("/@/", $email);
    //check if the domain name exists

    if(!my_checkdnsrr($domain)) {
        echo "<font color='red'>The domain $domain is invalid!</font><br>";
        return 3;
    }

    echo "<font color='green'>The domain $domain is valid!</font><br>";

    //check if it is yahoo
    $subject = "abcdef";
$pattern = '/^def/';
preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, 3);
if (preg_match('/^yahoo\./', $domain)|| preg_match('/^ymail\./', $domain)){
    echo "<font color='#AFA320'>Checking Yahoo addresses is not supported yet!</font>";
    return 4;
}

    //run the dig command and get the output
    exec("dig -t mx $domain", $output);
    //the 5th line has info on answers, etc
    //echo $output[5];
    //find the number of answers from 5th line of the output of dig command
    preg_match('/.*ANSWER: ([\d]).*/', $output[5], $matches);

    if(!$matches[1]) {
        echo "<font color='red'>The server has no MX records!</font><br>";
        return 5;
    }
    if($matches[1]==='1') {
        echo "<font color='green'>$matches[1] MX record was found for $domain!</font><br>";
    }
    else {
        echo "<font color='green'>$matches[1] MX records were found for $domain!</font><br>";
    }
    //answers start from 11

    //get available mail-servers
    if(getmxrr($domain, $mxhosts, $mxweight)) {
        for($i=0;$i<count($mxhosts);$i++) {
            $mxs[$mxhosts[$i]] = $mxweight[$i];
        }
        asort($mxs);
        $mailers = array_keys($mxs);
    } elseif(my_checkdnsrr($domain)) {
        $mailers[0] = gethostbyname($domain);
    } else {
        $mailers=array();
    }

    $connect_timeout = 1000;
    $errno = 0;
    $errstr = 0;

    //foreach ($mailers as $mailer) {
    $mailer=$mailers[0];
    echo "<br>Connecting $mailer...<br>";

    //    open socket

    if($sock = fsockopen($mailer, 25 ,$errno , $errstr, $connect_timeout)) {
        $response = fread($sock,8192);
        echo "$response<br>";
        echo "HELO yahoo.com<br>";
        fwrite($sock,"HELO yahoo.com\r\n");
        $response = fread($sock,8192);
        echo "$response<br>";
        if ($tmp=checkerror($response)) return $tmp;
        echo "MAIL FROM: geekboy@yahoo.com<br>";
        fwrite($sock, "MAIL FROM: <geekboy@yahoo.com>\r\n");
        $response = fread($sock,8192);
        echo "$response<br>";
        if ($tmp=checkerror($response)) return $tmp;
        echo "RCPT TO: $email<br>";
        fwrite($sock, "RCPT TO: <$email>\r\n");
        $response = fread($sock,8192);
        echo "$response<br>";
        if ($tmp=checkerror($response)) return $tmp;
        //if the first two characters of the reply is 25, it's a success
        if(substr($response, 0, 2)==='25'){
            echo "<br><font color='green'>The e-mail address exists!</font>";
            return 0;
        }
        else if (substr($response, 0, 3)==='550'){
            echo "<br><font color='red'>The e-mail address does not exist!</font>";
            return 8;
            }
        else {
            echo "<br><font color='red'>Other error occured!</font>";
            return 9;}

    }

    //}

}




?>
