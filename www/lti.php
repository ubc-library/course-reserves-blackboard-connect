<?php

/**
 * LTI Producer/Provider for LICR
 * @package LICRLTIpackage
 */
session_start ();
unset($_SESSION['puid']);
require_once ('../core/init.php');
require_once ('../core/db.inc.php');
require_once ('../core/utility.inc.php');
require_once ('../thirdparty/lti/LTI_Tool_Provider.php');
require_once ('../core/idboxapi.inc.php');

try {
  $ltidb = @new PDO ( Config::get ( 'licr_dsn' ), Config::get ( 'licr_user' ), Config::get ( 'licr_password' ) );
} catch ( PDOException $p ) {
  die ( 'Database connection failure: ' . $p->getMessage () . ' [' . Config::get ( 'licr_dsn' ) . ']' );
}

$lti_db_connector = LTI_Data_Connector::getDataConnector ( '', $ltidb, 'PDO' );
$lti_consumer = new LTI_Tool_Consumer ( Config::get ( 'lti_consumer_key' ), $lti_db_connector );
if (! is_null ( $lti_consumer->created )) {
  $lti_consumer->enabled = TRUE;
}
$lti_consumer->name = 'UBC';
$lti_consumer->secret = Config::get ( 'lti_sharedsecret' );
$lti_consumer->enable_until = time () + (30 * 24 * 60 * 60);
$lti_consumer->save ();

$lti_tool = new LTI_Tool_Provider ( 'doLaunch', $ltidb );
$lti_tool->execute ();

function doLaunch($tool_provider) {
  $licr = getController ( 'licr' );
  $puid = $tool_provider->user->getId ();
  $course = $tool_provider->resource_link->getId ();
  $course_info = $licr->getArray ( 'GetCourseInfo', array (
      'course' => $course 
  ) );
  if (! $course_info) {
    return Config::get('baseurl').'/blocked.nocourse';
  }
  $course_id = $course_info ['course_id'];
  $isStaff = idboxCall ( 'InGroup', array (
      'puid' => $puid,
      'group_name' => Config::get ( 'idbox_group_access' ) 
  ) );
  if (! $isStaff) {
    if (preg_match ( '/^[0-9]+$/', $puid )) { // it's not a PUID, it's a libraryid
      $puid = $licr->getArray ( 'GetPUID', array (
          'libraryid' => $puid 
      ) );
      $puid = $puid ['puid'];
      if (! $puid) {
        return Config::get('baseurl').'/blocked.nouser';
      }
    }
    $role = $licr->GetArray ( 'GetRole', array (
        'course' => $course,
        'puid' => $puid 
    ) );
    if (! $role || ! $role ['exists'] || ! $role ['active']) {
      //$tool_provider->reason = "You are not enrolled in this course.";
      //return false;
      return Config::get('baseurl').'/blocked.notregistered';
      
    }
  }
  ssv ( 'puid', $puid );
  if ($isStaff) {
    return Config::get ( 'baseurl' ) . "/instructorhome/id/$course_id/from/lti";
  } else {
    return Config::get ( 'baseurl' ) . "/studenthome/id/$course_id/from/lti";
  }
}

