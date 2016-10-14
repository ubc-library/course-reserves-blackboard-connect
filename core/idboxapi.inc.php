<?php
function idboxCall($command,$params){
  $data=$params;
  $data['command']=$command;
  $hash='idbox_'.$command.'_'.md5(serialize($params));
  if($ret=MC::get($hash)){
    return $ret;
  }
  $res = curlPost('http://idbox.api.library.ubc.ca/api.php?',$data);
  $dec=json_decode($res, TRUE);

  if($dec && $dec['success']){
    MC::set($hash, $dec['data'], 600);
    return $dec['data'];
  }

  return false;
}

