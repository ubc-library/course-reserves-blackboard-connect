<?php
class Controller_login{

  public function authenticate(){
    require_once(Config::get('approot').'/core/authentication.php');
    if(logged_in()){
      ssv('message','You are already logged in.');
      error_log('Already logged in');
      redirect();
    }
    $authmethod=sv('authentication',Config::get('authentication'));
    ssv('authentication',NULL);//clear temp authentication flag
    if(!$authmethod){
      ssv('message','You do not need to log in.');
      error_log('noauth');
      redirect();
    }
    ssv('user',NULL);
    if($authmethod=='ldap'){
      $success=authenticate_ldap();
    }else if ($authmethod=='shibboleth'){
      $success=authenticate_shibboleth();
    }else{
      return array('controller_error'=>'Unrecognized authentication method ['.$authmethod.']');
    }
    if(!$success){
      ssv('message','Incorrect username or password.');
      error_log('bad login');
    }
    redirect();
  }

  public function bye(){
    ssv('message','Goodbye, '.sv('userinfo')['firstname'].'.');
    logout();
  }
}