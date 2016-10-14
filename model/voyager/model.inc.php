<?php

/*
 * methods for interacting with Voyager
 * 
 */

class Model_voyager{
    /*
     * @return Array 
     */

  public function call($command,$params){
    if(method_exists($this,$command)){
      return $this->$command($params);
    }
    return false;
  }

  public function getBarcodes($bibids){
    $bibids=$bibids['bibid'];
    $bibids=explode(',',$bibids);
    if(!is_array($bibids)){
    	$bibids=array($bibids);
    }
    $bibids=array('bib'=>$bibids);
    $res = curlPost('http://parsnip.library.ubc.ca/getbarcode/',$bibids);
    return $res;
  }
}
