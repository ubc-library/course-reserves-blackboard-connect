<?php

    class Controller_librarytab
    {
        function librarytab()
        {
            if(Config::get('environment') == 'preproduction'){
                $puid = $this->getPuid();
	    }
            if(!$puid = $this->getPuid()){
                error_log("NO PUID!!!!  NO PUID!!!!  NO PUID!!!!");
                redirect(Config::get('bburl') . Config::get('lastbit'));
                return array(
                    'controller_error' => 'PUID not set'
                );
            };

            ssv('puid', $puid);

            if (gv('hash')) {
                redirect('/get/hash/' . gv('hash'));
                exit ();
            }

            require_once(Config::get('approot') . '/core/idboxapi.inc.php');
            $licr = getController('licr');


            $tvars = array(
                'baseurl'  => Config::get('baseurl'),
                'puid'     => $puid,
                'courses'  => $licr->getArray('Enrolment', array('puid' => $puid)),
                'programs' => $licr->getArray('GetEnrolledPrograms',array())//twig if will not bother if this is empty, so we can add it here as well
            );

            if (!empty($tvars['courses'])) {
                foreach ($tvars ['courses'] as $i => $course) {
                    $tvars ['courses'] [$i] ['current'] = strtotime($course ['enddate']) < date('U') ? 0 : 1;
                }
            }

            $isStaff = idboxCall('InGroup', array(
                'group_name' => Config::get('idbox_group_access'),
                'puid'       => $puid
            ));

            if ($isStaff) {
                $tvars ['allcourses']   = 1; // show searchbox instead of static list
                $tvars ['prevsearches'] = array();
                if (isset ($_SESSION ['prevsearches'])) {
                    foreach ($_SESSION ['prevsearches'] as $ps) {
                        $ci                        = $licr->getArray('GetCourseInfo', array(
                            'course' => $ps
                        ));
                        $tvars ['prevsearches'] [] = array(
                            'title'     => $ci ['title'],
                            'course_id' => $ps,
                            'location'  => $ci ['campus'],
                            'branch'    => $ci ['branch'],
                            'visible'   => $ci ['visible'],
                            'total'     => $ci ['total']
                        );
                    }
                }
                if (empty($tvars['courses'])) {
                    $tvars['courses'] = array();
                }
                $tvars ['contacts'] = $licr->getArray('ListUserContacts', array(
                    'puid' => $puid
                ));
                if (!$tvars ['contacts'] || count($tvars ['contacts']) == 0){
                    $tvars ['contacts'] = false;
                }
            }else{
                if ($tvars ['courses'] === false) {
                    unset ($_SESSION ['puid']);
                    return array(
                        'controller_error' => "User [$puid] not found"
                    );
                }

                $tvars ['allcourses'] = 0; // show static list of courses
                foreach ($tvars ['courses'] as $i => $crs) {
                    $ci = $licr->getArray('GetCourseInfo', array(
                        'course' => $i
                    ));
                    $tvars ['courses'] [$i] ['visible'] = $ci ['visible'];
                }
                $tvars ['contacts'] = $licr->getArray('ListUserContacts', array(
                    'puid' => $puid
                ));
                if (!$tvars ['contacts'] || count($tvars ['contacts']) == 0){
                    $tvars ['contacts'] = false;
                }
            }

            $tvars['top10'] = getFromWiki('https://wiki.ubc.ca/extensions/EmbedPage/getPage.php?title=/index.php/Library:Library_Tab_In_Connect&referer=https://cr.library.ubc.ca/librarytab');
            $tvars['top10'] = str_replace('http://help.library.ubc.ca/ask-colorbox', 'https://www.library.ubc.ca/help/ask-colorbox.html', $tvars['top10']);
            $tvars['top10'] = str_replace('WikiEditor', '', $tvars['top10']);
            return $tvars;
        }

        // store courses clicked on, for convenience (staff view only)
        function stashsearch()
        {
            if (!isset ($_SESSION ['prevsearches'])) {
                $_SESSION ['prevsearches'] = array();
            }
            if ($course_id = pv('course_id')) {
                $_SESSION ['prevsearches'] [$course_id] = $course_id;
            }
            exit ();
        }

        // remove courses from the previously-clicked area
        function clearstash()
        {
            if ($course_id = pv('course_id')) {
                unset ($_SESSION ['prevsearches'] [$course_id]);
            } else {
                $_SESSION ['prevsearches'] = array();
            }
            exit ();
        }

        private function getPuid(){
            if (!$puid = sv('puid')) {
                if (!$puid = pv('puid')) {
                    return false;
                }
            }
            return $puid;
        }
    }
