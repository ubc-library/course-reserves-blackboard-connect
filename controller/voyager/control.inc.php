<?php
class Controller_voyager{
  function __construct(){
    $this->model=getModel('voyager');
  }
  
  function call($command, $params) {
    return $this->model->call($command, $params);
  }

  function getJSON($command, $params) {
    $res = $this->call($command, $params);
    return $res;
  }

  function getArray($command, $params) {
    $res = $this->call($command, $params);
    $dec = json_decode($res, TRUE);
    return $dec['data'];
  }
}
