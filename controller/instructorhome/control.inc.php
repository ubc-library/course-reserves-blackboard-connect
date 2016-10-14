<?php

    /**
     * Class Controller_instructorhome
     */
    class Controller_instructorhome
    {
        /**
         * @return array
         */
        public function instructorhome()
        {
            $course_id = gv('id');
            if (!$course_id) {
                return array(
                    'controller_error' => 'No course id specified'
                );
            }
            $licr = getController('licr');
            if (!$puid = sv('puid')) {
                $short = $licr->getArray('GetCourseInfo', array('course' => $course_id))['shorturl'];
                if ($short) {
                    redirect($short);
                } else {
                    return array(
                        'controller_error' => '<h2>Course not found</h2>'
                    );
                }
            }
            $tvars = array();
            $tvars = array(
                'course_info' => $licr->getArray('GetCourseInfo', array(
                    'course' => $course_id
                ))
            );
            if (isset($tvars['course_info']['success']) && !$tvars['course_info']['success']) {
                return array('controller_error' => '<h2>Course not found</h2>');
            }
            if (!$tvars['course_info']['active']) {
                redirect(Config::get('baseurl') . '/blocked.workshop/ft/clean');
            }

            require_once(Config::get('approot') . '/core/idboxapi.inc.php');

            $isStaff = idboxCall('InGroup', array(
                'puid'       => $puid,
                'group_name' => Config::get('idbox_group_access')
            ));
            ssv('isStaff', ($isStaff ? 'yes' : 'no'));
            $isStaff = sv('isStaff') == 'yes';
            $isBookstore = idboxCall('InGroup', array(
                'puid'       => $puid,
                'group_name' => Config::get('idbox_readonly_access')
            ));
            ssv('isBookstore', ($isBookstore ? 'yes' : 'no'));
            $isBookstore = sv('isBookstore') == 'yes';

            if (!($isStaff = sv('isStaff', FALSE))) {
                $isStaff = idboxCall('InGroup', array('puid' => $puid, 'group_name' => Config::get('idbox_group_access')));
                ssv('isStaff', ($isStaff ? 'yes' : 'no'));
            }
            $isStaff = sv('isStaff') == 'yes';
            if (!($isBookstore = sv('isBookstore', FALSE))) {
                $isBookstore = idboxCall('InGroup', array('puid' => $puid, 'group_name' => Config::get('idbox_readonly_access')));
                ssv('isBookstore', ($isBookstore ? 'yes' : 'no'));
            }
            $isBookstore = sv('isBookstore') == 'yes';

            if ($isStaff || $isBookstore) {
                $tvars ['user_info'] = array(
                    'puid' => $puid
                );
                /*
                 * idboxCall ( 'PersonInfo', array ( 'puid' => $puid ) );
                */
            } else {
                $role = $licr->getArray('GetRole', array(
                    'puid'   => $puid,
                    'course' => $course_id
                ));
                if ($role ['role'] == 'Student') {
                    return array(
                        'controller_error' => 'Access denied: You do not have Instructor role in this course.'
                    );
                }
                $tvars ['user_info'] = array(
                    'puid' => $puid
                );
                /*
                 * $licr->getArray ( 'GetUserInfo', array ( 'puid' => $puid ) );
                */
            }
            $tvars ['studentmode'] = buildurl_get(array(
                'action' => 'studenthome',
                'id'     => $course_id
            ));

            $tvars ['env_url'] = (Config::get('baseurl'));

            // =========== Data Processing Starts Here ====================

            $ct = $licr->getArray('ListTags', array(
                'course'    => $course_id
                , 'student' => '0'
            ));


            $tagLookup = array();
            foreach ($ct as $tag_id => $tag_info) {
                $tagLookup [$tag_info['tag_id']] = $tag_info;
            }


            $freqs = array();
            foreach ($ct as $tag_id => $tag_info) {
                $freqs [$tag_id] = $tag_info ['count'];
                $ct [$tag_id] ['hash'] = 't' . md5($tag_info ['name']);
            }

            if (count($ct)) {
                $max = max($freqs);
                $min = min($freqs);
                $range = $max - $min;
                foreach ($freqs as $tag_id => $freq) {
                    if ($range == 0) {
                        $ct [$tag_id] ['frequency'] = 14;
                    } else {
                        $ct [$tag_id] ['frequency'] = 11 + ($freq - $min) / $range * 8;
                    }
                }
            }
            $tvars ['course_tags'] = $ct;
            $_metadata = getModel('metadata');
            $requiredFields = $_metadata->getAllMetadataListsWithFieldSpecs();
            //Reportinator::json_log($requiredFields);

            $types = $licr->getArray('ListTypes');
            $parsedTypes = array();
            foreach ($types as $k => $v) {
                $parsedTypes [$k] = $v ['name'];
            }
            unset ($types);

            $tvars ['branch_help_info'] = idboxCall('GetReservesContact', array(
                'branch_name' => $tvars ['course_info'] ['branch']
            ));
            $tvars ['branch_help_info'] ['name'] = $tvars ['course_info'] ['branch'];

            // $_utility = getModel ( 'utility' );
            $_bibdata = getModel('bibdata');
            $_utility = getModel('utility');
            $_ux = getModel('ux');

            $parsedItems = array();

            $tvars['loanperiods'] = $_utility->getList('ListLoanPeriods', 'ListLoanPeriods');

            $parsedItems = $licr->getArray('CRInstructorHome', array('course_id' => $course_id));
            foreach ($parsedItems as $item_id => $item) {
                //error_log("parsing itemID $item_id");
                $bibdata = $_bibdata->getBibdata($item['bibdata'], $item['format'], $item_id);
                $parsedItems[$item_id]['bibdata'] = $bibdata['bibdata'];
                $parsedItems[$item_id]['fieldmap'] = $bibdata['fieldmap'];
                $parsedItems[$item_id]['fieldtitles'] = $bibdata['fieldtitles'];
                $parsedItems[$item_id]['requiredfields'] = $requiredFields[$item['format'] === NULL ? 'physical_general' : $item['format']];
                $parsedItems[$item_id]['taglist'] = '';
                foreach ($item['tags'] as $tag_id => $tag_data) {
                    $__t = 't' . md5($tag_data ['name']);
                    $parsedItems[$item_id]['taglist'] .= ',' . $__t;
                    $item['tags'][$tag_id]['hash'] = $__t;
                    $item['tags'][$tag_id]['shorturl'] = $tagLookup[$tag_id]['shorturl'];
                }
                $parsedItems[$item_id]['tags'] = $item['tags'];
                $parsedItems[$item_id]['additional_access'] = $item['additional_access'];
                $parsedItems[$item_id]['taglist'] = ltrim($parsedItems[$item_id]['hash'], ',');
                $parsedItems[$item_id]['format'] = $item['format'];
                $parsedItems[$item_id]['format_icon'] = $_ux->getIcon($item['format']);
                $parsedItems[$item_id]['active'] = $item['request_start'];
                $parsedItems[$item_id]['inactive'] = $item['request_end'];
                $parsedItems [$item_id] ['previous_notes'] = array();
                $parsedItems [$item_id] ['student_note'] = array();
                if (count($item['note']) > 0) {
                    foreach ($item['note'] as $note_id => $note) {
                        $roles = explode(',', $note['roles']);
                        //7 is staff only
                        foreach (array(8, 9, 10) as $role_id) {
                            if (in_array($role_id, $roles)) {
                                if ($role_id === 9) {
                                    $parsedItems [$item_id] ['student_note'][$note_id] = $note;
                                } else {
                                    $parsedItems [$item_id] ['previous_notes'][$note_id] = $note;
                                }
                            }
                        }
                    }
                    ksort($parsedItems [$item_id] ['previous_notes']);
                }
            }
            $tvars ['readings'] = count($parsedItems) > 0 ? $parsedItems : 'No Readings Found';

            // get previous courses
            $enrolment = $licr->getArray('Enrolment');
            if($enrolment == FALSE){
                // happens in testing
                $enrolment=array();
            }
            $previousCourses = array();
            foreach ($enrolment as $k => $v) {
                if ($v ['role_name'] == 'Instructor' || $v ['role_name'] == 'TA') {
                    $res = $licr->getArray('ListCIs', array(
                        'course'  => $k,
                        'visible' => 1
                    ));

                    $res2 = $licr->getArray('ListCIs', array(
                        'course'  => $k,
                        'visible' => 0
                    ));

                    $list = array();
                    foreach ($res as $key => $val) {
                        $list [$key] = $val ['title'];
                    }

                    foreach ($res2 as $key => $val) {
                        $list [$key] = $val ['title'];
                    }

                    $count = count($res) + count($res2);

                    $previousCourses [] = array(
                        'course_id'  => $k,
                        'title'      => $v ['title'],
                        'item_count' => $count,
                        'list'       => $list
                    );
                }
            }
            ((count($previousCourses)) ? $tvars ['previouscourses'] = $previousCourses : $tvars ['previouscourses'] = FALSE);

            $tvars ['puid'] = $puid;
            $tvars ['course_id'] = $course_id;
            $tvars ['tut_help'] = getFromWiki('http://wiki.ubc.ca/Library:How_to_Use_Library_Course_Reserves_in_Connect/Faculty?action=render');
            $tvars ['vid_help'] = getFromWiki('http://wiki.ubc.ca/Library:How_to_Use_Library_Course_Reserves_in_Connect/Video_Tutorials:_Faculty?action=render');

            if (isset ($enrolment [$course_id])) {
                $tvars ['enrolled'] = TRUE;
                $tvars ['subscription'] = $licr->getArray('IsSubscribed', array(
                    'course' => $course_id
                )) ? 1 : 0;
            } else {
                $tvars ['enrolled'] = FALSE;
                $tvars ['subscription'] = FALSE;
            }

            preg_match('/MSIE (.*?);/', $_SERVER ['HTTP_USER_AGENT'], $matches);

            if (count($matches) > 1) {
                $version = $matches [1];
                switch (TRUE) {
                    case ($version <= 8) :
                        // $tvars['template']='ie8';
                    break;
                    default :
                        // You get the idea
                }
            }

            $tvars ['docstore'] = Config::get('docstore_location');
            $tvars['print'] = gv('print', '0');
            return $tvars;
        }
    }
