<?php

class Controller_licr {

  function __construct() {
    $this->model = getModel('licr');
  }

  function call($command, $params) {
    return $this->model->call($command, $params);
  }

  function getJSON($command, $params=FALSE) {
    $res = $this->call($command, $params?$params:array());
    return $res;
  }

  function getArray($command, $params=FALSE) {
    $res = $this->call($command, $params?$params:array());
    $dec = json_decode($res, TRUE);
    if(!$dec){
      error_log("LICR::getArray cannot decode JSON\n".var_export($res,true));
    }
    if(isset($dec['data'])){
      return $dec['data'];
    }else{
      return $dec;
    }
  }
}
