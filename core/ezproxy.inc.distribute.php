<?php
////////////////EZPROXY///////////////
function prependezproxy($url){
    $parsed=parse_url($url);
    if($parsed['host']=='') return $url; // already proxied, FBOFW
    $db = new PDO('mysql:host=;dbname=', '', '');
    if(!$db) return 'DB CONNECT ERROR';
    $prepend=0;
    $sql="SELECT COUNT(*) AS c FROM `domain` WHERE `domain`=:domain";
    $stmt=$db->prepare($sql);
    $stmt->execute(array('domain'=>$parsed['host']));
    $rc=$stmt->fetch(PDO::FETCH_ASSOC);
    if($rc['c']>0){
        $prepend=1;
    }else{
        $sql="SELECT COUNT(*) AS c FROM `host` WHERE `host`=:domain";
        $stmt=$db->prepare($sql);
        $stmt->execute(array('domain'=>$parsed['host']));
        $rc=$stmt->fetch(PDO::FETCH_ASSOC);
        if($rc['c']>0){
            $prepend=1;
        }
    }
    return $prepend;
    /*
        if($prepend){
            $url='http://ezproxy.library.ubc.ca/login?url='.$url;
        }
        return $url;
    */
}