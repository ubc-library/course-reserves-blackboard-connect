<?php
class Controller_blocked {
  var $forcetheme = 'default';
  public function blocked() {
  }
  public function nocourse() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>Course not found</h2>
<p>The Library Course Reserves system has no record of this course. If the course was recently created, 
it may take up to 24 hours for it to become available.</p>
<p>If you still see this message after 24 hours, please contact your Course Reserves librarian.</p>
    ' 
    );
  }
  public function workshop() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>Inactive Course</h2>
<p>This course is not currently active in Library Online Course Reserve.
 If you wish to see if your course qualifies for support, please contact your branch Reserves Librarian. 
 For contact information, see <a href="http://courses.library.ubc.ca">courses.library.ubc.ca</a>.
</p>
    ' 
    );
  }
  public function nouser() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>User not found</h2>
<p>The Library Course Reserves system has no record of your user account. If you were recently
enrolled, be aware that it may take up to 24 hours for your account to appear in the Course Reserves
system.</p>
<p>If you still see this message after 24 hours, please contact Student Services.</p>
    ' 
    );
  }
  public function notregistered() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>Not registered</h2>
<p>You do not appear to be registered in this course. If you have recently registered, it may take
up to 24 hours for your enrollment to be entered into the Course Reserves system.</p>
<p>If you still see this message after 24 hours, please contact your Subject Liaison Librarian.</p>
    ' 
    );
  }
  public function encumbered() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>Restricted</h2>
<p>This course reading is restricted by fair dealing or a transactional license
and is only available to enrolled students while the course is being offered. For more information 
please see <a href="http://copyright.ubc.ca/guidelines-and-resources/faq/">UBC Copyright</a>.    		
</p>
<p>If you have recently registered in this course, it may take
up to 24 hours for your enrollment to be entered into the Course Reserves system.
If you still see this message after 24 hours, please contact your Subject Liaison Librarian.</p>
    '
    );
  }
  public function badlink() {
    $forcetheme = gv ( 'ft', $this->forcetheme );
    return array (
        'forcetheme' => $forcetheme,
        'message' => '
<h2>Not Found</h2>
<p>The resource specified in the link was not found.</p>
    '
    );
  }
}
