<?php
require_once(dirname(__FILE__).'/../../core/curl.inc.php');

class Model_licr {
  function call($command, $params, $use_memcache=FALSE) {
    if (empty ( $params )|| is_null($params)) {
      $params = array();
    }
    if (! isset ( $params ['puid'] ) && sv ( 'puid' )) {
      $params ['puid'] = sv ( 'puid' );
    }
    $params['activeuser']=sv('puid');
    $data = $params;
    $data ['command'] = $command;
    $data=$this->stringify($data);
    if($use_memcache){
      $key='licr'.md5(serialize($data));
      if($res=MC::get($key)){
        return $res;
      }
    }
    $data['vtimestamp']=(string)date('U');
    $data['vrand']=(string)mt_rand();
    ksort($data);
    $verification=sha1(Config::get('secret').serialize($data));
    $data['verification']=$verification;
    $res = curlPost(Config::get ( 'licr_api' ), $data );
    if($use_memcache){
      MC::set($key,$res,600);
    }
    return $res;
  }
  function getJSON($command, $params = NULL, $use_memcache=FALSE) {
    $res = $this->call ( $command, $params, $use_memcache );
    return $res;
  }
  function getArray($command, $params = NULL, $use_memcache=FALSE) {
    $res = $this->call ( $command, $params, $use_memcache );
    $dec = json_decode ( $res, TRUE );
    return $dec ['data'];
  }
  private function stringify($arr){
    if(is_array($arr)){
      foreach($arr as $k=>$v){
        $arr[$k]=$this->stringify($v);
      }
      return $arr;
    }else{
      return (string)$arr;
    }
  }
}
