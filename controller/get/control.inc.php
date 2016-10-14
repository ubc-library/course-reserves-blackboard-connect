<?php
/*
 * /get/course/{course_id}/hash/i.{hash} -- go directly to item /get/hash/i.{hash} - display item popup (this is the short URL)
 */
class Controller_get {
  var $licr;
  function __construct() {
    $this->licr = getModel ( 'licr' );
  }
  function get() {
    require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
    if (! $hash = gv ( 'hash' )) {
      return array (
          'forcetheme' => 'default',
          'controller_error' => 'No identifier supplied.'
      );
    }
    $type = $hash [0]; // first character
    if (! sv ( 'puid' )) {
      return array (
          'forcetheme' => 'default',
          'controller_error' => 'You must log in to <a href="' . Config::get ( 'bburl' ) . '">Connect</a> to view this resource.'
      );
    }
    if($type=='p'){//program
      list($type,$progid)=explode('.',$hash);
      redirect("/program/id/$progid");
    }
    $info = $this->licr->getArray ( 'GetByHash', array (
        'hash' => $hash
    ) );
    if (! $info) {
      return array (
          'forcetheme' => 'default',
          'controller_error' => 'Resource not found.'
      );
    }
    if ($type == 'i') { // Item
      $course_ids = false;
      if (! gv ( 'course' )) {
        $access = 'None';
        $item_course_ids = array_keys ( $this->licr->getArray ( 'GetCoursesByItem', array (
            'item' => $info ['item_id']
        ) ) );
        $in_a_relevant_course = false;
        // Check if enrolled in a program
        $programs = $this->licr->getArray ( 'GetEnrolledPrograms' );
        foreach ( $programs as $program_id => $program_info ) {
          $enrolled_course_ids = $this->licr->getArray ( 'ListProgramCourses', array (
              'program' => $program_id
          ) );
          $enrolled_course_ids=array_keys($enrolled_course_ids['courses']);
          foreach ( $enrolled_course_ids as $e ) {
            if (in_array ( $e, $item_course_ids )) {
              $this->licr->getArray('RegisterClick',array('item_id'=>$info['item_id']));
              redirect ( '/program/id/' . $program_id . '/open/' . $info ['item_id'] );
            }
          }
        }
        $enrolment = $this->licr->getArray ( 'Enrolment' );
        $enrolled_course_ids = array_keys ( $enrolment );
        if (! $in_a_relevant_course) {
          foreach ( $enrolled_course_ids as $e ) {
            if (in_array ( $e, $item_course_ids )) {
              $in_a_relevant_course = true;
              $course_ids = array (
                  $e
              );
              $access = strtolower ( $enrolment [$e] ['role_name'] );
              if ($access && $access != 'student') {
                $access = 'instructor'; // because of TAs etc
              } else {
//                $access = 'None'; // whaaaaat was i thinking
              }
              break;
            }
          }
        }

        if (! $in_a_relevant_course) {
          // check if staff
          $isStaff = idboxCall ( 'InGroup', array (
              'group_name' => Config::get ( 'idbox_group_access' ),
              'puid' => sv ( 'puid' )
          ) );
          if (! $isStaff) {
            $isStaff = idboxCall ( 'InGroup', array (
                'group_name' => 'CR-Admin',
                'puid' => sv ( 'puid' )
            ) );
          }
          if ($isStaff) {
            $access = 'instructor';
            $course_ids = $info ['course_ids']; // from GetByHash
          }
        }
        if ($access == 'None') {
          // not fair dealing? Then just go to the item URL, don't register a click
          // first, what's the FD status
          $course_ids = $info ['course_ids'];
          $encumbered = 0;
          foreach ( $course_ids as $course_id ) {
            $instance = $this->licr->getArray ( 'GetCIInfo', array (
                'course' => $course_id,
                'item_id' => $info ['item_id']
            ) );
            if (! $instance) {
              return array (
                  'forcetheme' => 'default',
                  'controller_error' => 'No course associated with this item'
              );
            }
            $start = strtotime ( $instance ['dates'] ['course_item_start'] );
            $end = strtotime ( $instance ['dates'] ['course_item_end'] );
            $now = date ( 'U' );
            $current = (($start < $now) && ($end > $now));
            if ($current) {
              $encumbered |= ($instance ['fairdealing'] | $instance ['transactional']);
            }
          }
          if ($encumbered) {
            redirect('/blocked.encumbered/ft/clean');
          } else {
            return $this->gotourl ( $info ['uri'], FALSE );
          }
        }
        if ($access == 'Student' && ! $course_ids) {
          redirect('/blocked.notregistered/ft/clean');
        }
        // establish a course context for this item to be viewed in
        // note, student *could* be in multiple courses with the same item
        // (unlikely) so we just choose the first one.
        $program_id=false;
        foreach($course_ids as $course_id){
          $program_has_course=$this->licr->getArray('GetProgramsByCourse',array('course'=>$course_id));
          if($program_has_course){
            $program_id=$program_has_course[0];
          }
        }
        if($program_id){
          $this->licr->getArray ( 'RegisterClick', array (
              'item_id' => $info['item_id']
          ) );
          redirect ( '/program/id/' . $program_id . '/open/' . $info ['item_id'] );
        }else{
          redirect ( '/' . $access . 'home/id/' . $course_ids [(count ( $course_ids ) - 1)] . '/open/' . $info ['item_id'] );
        }
      }       // gv('course') not set
      else { // indicates that we are in a course
        $program_id=false;
        $program_has_course=$this->licr->getArray('GetProgramsByCourse',array('course'=>gv('course')));
        if($program_has_course){
          $program_id=$program_has_course[0];
          $this->licr->getArray ( 'RegisterClick', array (
              'item_id' => $info['item_id']
          ) );
          redirect ( '/program/id/' . $program_id . '/open/' . $info ['item_id'] );
        }
        $enrolled_course_ids = array_keys ( $this->licr->getArray ( 'Enrolment' ) );
        $item_course_ids = array_keys ( $this->licr->getArray ( 'GetCoursesByItem', array (
            'item' => $info ['item_id']
        ) ) );
        if (! in_array ( gv ( 'course' ), $item_course_ids )) {
          return array (
              'forcetheme' => 'default',
              'controller_error' => 'Requested item is not in this course.'
          );
        }
        if (array_search ( gv ( 'course' ), $enrolled_course_ids ) === FALSE) {
          $isStaff = idboxCall ( 'InGroup', array (
              'group_name' => Config::get ( 'idbox_group_access' ),
              'puid' => sv ( 'puid' )
          ) );
          if (! $isStaff) {
            $isStaff = idboxCall ( 'InGroup', array (
                'group_name' => 'CR-Admin',
                'puid' => sv ( 'puid' )
            ) );
          }
          if (! $isStaff) {
            return array (
                'forcetheme' => 'default',
                'controller_error' => '(3) You do not have access to this resource.'
            );
          }
        }

        return $this->gotourl ( $info ['uri'], $info ['item_id'] );
        // }
      }
    } else if ($type == 'c') { // Course
      $enrolment = $this->licr->getArray ( 'Enrolment' );
      $enrolled_course_ids = array_keys ( $enrolment );
      if (! in_array ( $info ['course_id'], $enrolled_course_ids )) {
        // idbox
        $isStaff = idboxCall ( 'InGroup', array (
            'group_name' => Config::get ( 'idbox_group_access' ),
            'puid' => sv ( 'puid' )
        ) );
        if (! $isStaff) {
          $isStaff = idboxCall ( 'InGroup', array (
              'group_name' => 'CR-Admin',
              'puid' => sv ( 'puid' )
          ) );
        }
        if (! $isStaff) {
          return array (
              'forcetheme' => 'default',
              'controller_error' => 'You are not enrolled in this course.'
          );
        }
        $access = 'instructor';
      } else {
        $access = strtolower ( $enrolment [$info ['course_id']] ['role_name'] );
        if ($access != 'student') {
          $access = 'instructor';
        }
      }
      redirect ( '/' . $access . 'home/id/' . $info ['course_id'] );
    } else if ($type == 't') { // Tag
                               // var_dump($info);die();
                               // Course is in program?
      $course_id = $info ['course_id'];
      $program_ids = $this->licr->getArray ( 'GetProgramsByCourse', array (
          'course' => $course_id
      ) );
      if ($program_ids) {
        // course is in program(s); find which program user is enrolled in
        // if not enrolled, just send to the first result (most recently created)
        $enrolled_programs = $this->licr->getArray ( 'GetEnrolledPrograms' );
        $enrolled_program_ids = array_keys ( $enrolled_programs );
        $target_program_id = - 1;
        foreach ( $program_ids as $program_id ) {
          if (in_array ( $program_id, $enrolled_program_ids )) {
            $target_program_id = $program_id;
            break;
          }
        }
        if ($target_program_id == - 1) {
          $target_program_id = $enrolled_program_ids [0];
        }
        redirect ( '/program/id/' . $target_program_id . '/tag_id/' . $info ['tag_id'] );
      } else { // Course is not in a program
        $enrolment = $this->licr->getArray ( 'Enrolment' );
        $enrolled_course_ids = array_keys ( $enrolment );
        if (! in_array ( $info ['course_id'], $enrolled_course_ids )) {
          // idbox
          $isStaff = idboxCall ( 'InGroup', array (
              'group_name' => Config::get ( 'idbox_group_access' ),
              'puid' => sv ( 'puid' )
          ) );
          if (! $isStaff) {
            $isStaff = idboxCall ( 'InGroup', array (
                'group_name' => 'CR-Admin',
                'puid' => sv ( 'puid' )
            ) );
          }
          if (! $isStaff) {
            return array (
                'forcetheme' => 'default',
                'controller_error' => 'You are not enrolled in this course.'
            );
          }
          $access = 'instructor';
        } else {
          $access = strtolower ( $enrolment [$info ['course_id']] ['role_name'] );
          if ($access != 'student') {
            $access = 'instructor';
          }
        }
        redirect ( '/' . $access . 'home/id/' . $info ['course_id'] . '/tag_id/' . $info ['tag_id'] );
      }
    } else {
      return array (
          'forcetheme' => 'default',
          'controller_error' => 'Invalid hash.'
      );
    }
  }
  private function gotourl($uri, $item_id = FALSE) {
    $url = trim ( $uri );
    error_log("GET resolved to the URL: ".$url);
    if (strpos ( $uri, 'docstore' )) {
      $_docstore = getModel ( 'docstore' );
      $hash = $_docstore->getHashFromUri ( $uri );
      $filename = $_docstore->getPDF ( $hash );

      if ($filename === 'errorpdfs/expired.pdf') {
        return array (
            'forcetheme' => 'default',
            'controller_error' => '
					    <h2>Access Expired</h2>
        		  <p>This PDF was made available only for the active time period of your course. It has now been removed in keeping with
        		  Canadian copyright.<br> Please consult your course notes or look for the item in the library&#8217;s book collections.</p>
					'
        );
      }

      if (isset ( $filename )) {

          $this->licr->getArray ( 'RegisterClick', array (
              'item_id' => $item_id
          ) );

        $file = Config::get ( 'docstore_docs' ) . $filename;

        @ini_set('zlib.output_compression',0);
        @ini_set('implicit_flush',1);
        @ob_end_clean();
        set_time_limit(0);

        if (file_exists ($file)) {
          header ('Content-Encoding: none;');
          header ('X-Accel-Buffering: no');
          header ('Content-Description: DocStore File Access');
          header ('Content-Type: application/pdf');
          header ('Content-Disposition: attachment; filename=' . basename ($filename));
          header ("Cache-Control:  max-age=1, must-revalidate");
          header ("Pragma: public");
          ob_end_flush ();
          flush ();
          readfile ($file);
          exit ();
        }
      } else {
        // you should never reach here, as there is an error controller
        // this is here if some random error occurs
        echo "<h1>File Doesn't Exist</h1><br /><p>Please consult with your lecturer/administration and let them know this URL is broken.</p>";
        exit ();
      }
    }

    if ($url) {
      $parsed_url = @parse_url ( $url );
      if (! $parsed_url) {
        preg_match ( '/^(https?)/i', $url, $m );
        if (! empty ( $m [1] )) {
          $parsed_url = array (
              'scheme' => $m [1]
          );
        }
      }
      if ($parsed_url) {
        if (empty ( $parsed_url ['scheme'] ) && preg_match ( '@^([a-z0-9]\.)*[a-z]{2,4}@i', $parsed_url ['path'] )) {
          $fix_url = 'http://' . $url;
          $parsed_url = parse_url ( $fix_url );
          if (! $parsed_url) {
            // dammit
            return array (
                'forcetheme' => 'default',
                'controller_error' => '
								<h2>Malformed URL for this resource (1)</h2>
								<pre>' . htmlentities ( $uri ) . '</pre>
								'
            );
          }
          $url = $fix_url;
        }
        if (! preg_match ( '@^https?$@i', $parsed_url ['scheme'] )) {
          // jeez
          return array (
              'forcetheme' => 'default',
              'controller_error' => '
							<h2>Cannot redirect</h2>
							<p>The URL for this resource does not appear to be for a Web page.</p>
							<pre>' . htmlentities ( $uri ) . '</pre>
							'
          );
        }
        if (true) { // ! $isStaff) {
          if ($item_id) {
            $this->licr->getArray ( 'RegisterClick', array (
                'item_id' => $item_id
            ) );
          }
        }
          error_log("Redirecting to URL: " . $url);
        redirect ( $url );
      } else {
        // also dammit
        return array (
            'forcetheme' => 'default',

            'controller_error' => '
						<h2>Malformed URL for this resource (2)</h2>
						<pre>' . htmlentities ( $url ) . var_export ( $parsed_url, true ) . '</pre>
						'
        );
      }
    } else {
      // Missing URL for this resource (3) - text changed to appease everyone
      return array (
          'forcetheme' => 'default',
          'controller_error' => "<h2>This item is still being processed and cannot yet be viewed.</h2>"
      );
    }
  }
}
