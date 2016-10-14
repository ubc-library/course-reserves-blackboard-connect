<?php
class Controller_resolve {
  var $licr;
  const TARGET = 4;
  const ITEM = 2;
  const DENY = 1;
  function resolve() {
    $template_data = array (
        'forcetheme' => 'default' 
    );
    if (! $target = $_GET ['target']) {
      $template_data ['controller_error'] = 'Missing target URL.';
      return $template_data;
    }
    $tmp = urldecode ( urldecode ( $target ) );
    if (! preg_match ( '/(i\.[b-zB-Z0-9]*)$/', $tmp, $m )) {
      // it's a course, or tag, or garbage. Either way, just redirect
      header ( 'Location: ' . $target );
      exit ();
    }
    $hash = $m [1];
    $this->licr = getModel ( 'licr' );
    $info = $this->licr->getArray ( 'GetByHash', array (
        'hash' => $hash 
    ) );
    if (! $info) {
      $template_data ['controller_error'] = 'Requested item not found.';
      
      return $template_data;
    }
    $item_id = $info ['item_id'];
    $courses = $this->licr->getArray ( 'GetCoursesByItem', array (
        'item' => $item_id 
    ) );
    if (! $courses) {
      // should just pass it?
      $template_data ['controller_error'] = 'There is no course associated with this item.';
      return $template_data;
    }
    $course_ids = array_keys ( $courses );
    $determination = array ();
    $dflags = 0;
    foreach ( $course_ids as $course_id ) {
      $instance = $this->licr->getArray ( 'GetCIInfo', array (
          'course' => $course_id,
          'item_id' => $item_id 
      ) );
      if (! $instance) {
        $template_data ['controller_error'] = 'Missing instance information.';
        return $template_data;
      }
      $start = strtotime ( $instance ['dates'] ['course_item_start'] );
      $end = strtotime ( $instance ['dates'] ['course_item_end'] );
      $now = date ( 'U' );
      $current = (($start < $now) && ($end > $now));
      $encumbered = $instance ['fairdealing'] | $instance ['transactional'];
      if ($encumbered) {
        if ($current) {
          $dflags |= self::TARGET; //was, illogically, self::DENY;
        } else {
          $dflags |= self::TARGET;
        }
      } else {
        if ($current) {
          $dflags |= self::TARGET;
        } else {
          $dflags |= self::ITEM;
        }
      }
    }
    if ($dflags & self::TARGET) {
      header ( 'Location: ' . $target );
      exit ();
    } elseif ($dflags & self::ITEM) {
      $uri = trim ( $info ['uri'] );
      if ($uri) {
        header ( 'Location: ' . $uri );
        exit ();
      }
      redirect ( $target );
      // redirect ( '/get/hash/' . $hash );
    } elseif ($dflags & self::DENY) {
      redirect ( '/blocked.encumbered' );
    } else {
      die ( '??'.$dflags );
    }
  }
}
