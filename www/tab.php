<?php

    /**
     * Pseudo-OAuth for the library tab
     */
    session_start ();
    header ('Content-type: text/html');

    require_once ('../core/init.php');
    require_once ('../core/db.inc.php');
    require_once ('../core/utility.inc.php');
    require_once ('../thirdparty/lti/OAuth.php');
    require_once ('../core/idboxapi.inc.php');
    //if($_SERVER['REMOTE_ADDR']=='137.82.99.108' || $_SERVER['REMOTE_ADDR']=='142.103.157.250'){
    //error_log(serialize($_POST));
    //$_POST=array();
    //}
    if (!$_POST) {

        ?>
        <h1>Authentication Missing</h1>
        <p>A problem has been identified with Internet Explorer 11 not passing identification information to the Library Course Reserves
            application. Library technical staff are currently working to address the issue, but there is no estimated time for a resolution.</p>
        <p>The current workaround is to use a different browser such as Chrome, Firefox, Safari, Opera, or a previous version of Internet Explorer.</p>
        <p>We regret the inconvenience.</p>
        <?php
        exit();
    }

    error_log ("Library Tab Environment: " . Config::get ('environment'));

    $oauth_signature = $_POST ['oauth_signature'];
    $oauth_timestamp = $_POST ['oauth_timestamp'];

    if(Config::get('environment')=='dev'){
        error_log ("OAuth Signature: " . "{$oauth_signature}");
    }

    unset ($_POST ['oauth_signature']);
    $url = Config::get ('tab_url');
    $hash = false;
    if (!empty ($_POST ['hash'])) {
        $hash = $_POST ['hash'];
    }
    //if(Config::get('environment')!='development'){
    unset($_POST['hash']);
    //}

    ksort ($_POST);
    $baseString = [
        'POST&' . $url
    ];
    foreach ($_POST as $k => $v) {
        $baseString [] = "$k=$v";
    }
    $baseString = implode ('&', $baseString);
    $baseString = rawurlencode (mb_convert_encoding ($baseString, 'UTF-8'));

    $sharedKey = Config::get ('tab_sharedsecret');

    // $hmac=hash_hmac('sha1',$baseString,$sharedKey);
    $hmac = base64_encode (hash_hmac ('sha1', $baseString, $sharedKey, true));

    if ($hmac !== $oauth_signature) {

        if(Config::get('environment')=='dev'){
            error_log ("baseString: " . "{$baseString}");
            error_log ("sharedKey: " . "{$sharedKey}");
            error_log ("hmac: " . "{$hmac}");
        }

        die ('<h1>Unauthorized Access</h1>');
    }
    $delta = abs ($oauth_timestamp - 1000 * microtime (true));
    if ($delta > 10000) {
        die ("<h1>Expired $delta</h1>");
    }
    $puid = $_POST ['user_id'];
    if (preg_match ('/^[0-9]+$/', $puid)) { // it's not a PUID, it's a libraryid
        $licr = getController ('licr');
        $puid = $licr->getArray ('GetPUID', [
            'libraryid' => $puid
        ]);
        $puid = $puid ['puid'];
        if (!$puid) {
            die ("<h1>No user found!</h1>");
        }
    }
    //$instant = md5 ( $puid . date ( 'U' ) . 'sa*lt' );

    ssv ('puid', $puid);
    if ($hash) {
        header ("Location: /get/hash/$hash"); // /instant/$instant" );
        exit();
    }

    header ("Location: /librarytab");
    exit();
