<?php
$tmp=parse_ini_file('../config.ini',true);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME']='local';

foreach($tmp as $env=>$ini){
	if(isset($ini['apphost']) && $_SERVER['SERVER_NAME']==$ini['apphost']){
		Config::set($env,array_merge($tmp['all'],$ini));
		$conf=true;
		break;
	}
}
if(!Config::initialized()){
	die('No suitable configuration found for server '.$_SERVER['SERVER_NAME']."\n");
}

// it turns out that no, you can't just do "static class Config{"
class Config{
  private static $ini=array();
  private static $initialized=FALSE;
  public static function set($env,$ini){
  	$ini['environment']=$env;
  	self::$ini=$ini;
  	self::$initialized=TRUE;
  }
  public static function get($key,$default=NULL){
  	if(isset(self::$ini[$key])) return self::$ini[$key];
  	return $default;
  }
  public static function initialized(){
  	return self::$initialized;
  }
}


//echo Config::get('approot','error');
