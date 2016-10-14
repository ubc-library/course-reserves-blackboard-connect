<?php

    class Controller_studenthome
    {
        function studenthome()
        {
            $course_id = gv('id');
            if (!$course_id) {
                return array(
                    'controller_error' => 'No course id specified'
                );
            }
            $licr = getController('licr');
            if (!$puid = sv('puid')) { // not logged in
                $short = $licr->getArray('GetCourseInfo', array('course' => $course_id))['shorturl'];
                if ($short) {
                    redirect($short);
                } else {
                    return array(
                        'controller_error' => '<h2>Course not found</h2>'
                    );
                }
            }

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

            $ct = $licr->getArray('ListTags', array(
                'course'    => $course_id
                , 'student' => '1'
            ));
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
                        $ct [$tag_id] ['frequency'] = 15;
                    } else {
                        $ct [$tag_id] ['frequency'] = 10 + ($freq - $min) / $range * 10;
                    }
                }
            }
            $tvars ['course_tags'] = $ct;

            $tvars['showtag'] = FALSE;
            if ($showtag = gv('tag_id', FALSE)) {
                $show_tag_info = $licr->getArray('GetTagInfo', array('tag' => $showtag));
                $show_tag_hash = 't' . md5($show_tag_info['name']);
                $tvars['showtag'] = $show_tag_hash;
            }


            require_once(Config::get('approot') . '/core/idboxapi.inc.php');


            $isStaff = idboxCall('InGroup', array(
                'puid'       => $puid,
                'group_name' => Config::get('idbox_group_access')
            ));
            ssv('isStaff', ($isStaff ? 'yes' : 'no'));
            $isStaff = (sv('isStaff') === 'yes');
            $tvars ['instructormode'] = buildurl_get(array(
                'action' => 'instructorhome',
                'id'     => $course_id
            ));

            if ($isStaff) {
                $tvars ['user_info'] = array(
                    'puid' => $puid
                ); // idboxCall ( 'PersonInfo', array ('puid' => $puid ) );
            } else {
                $role = $licr->getArray('GetRole', array(
                    'puid'   => $puid,
                    'course' => $course_id
                ));
                $tvars ['user_info'] = array(
                    'puid' => $puid
                ); // $licr->getArray('GetUserInfo',array('puid'=>$puid));
                if ($role['role'] == 'None') {
                    //check for program enrolment
                    $programs = $licr->getArray('GetProgramsByCourse', array('course' => $course_id));
                    if (!$programs) {
                        redirect('/blocked.notregistered/ft/clean');
                    }
                    if ($gv('open')) {
                        redirect('/program/id/' . $programs[0] . '/open/' . gv('open'));
                    } else {
                        redirect('/program/id/' . $programs[0]);
                    }
                }
                if ($role ['role'] == 'Student') {
                    $tvars ['instructormode'] = '';
                }
            }

            // -------------------------------------------------------------------------------------------------------------
            // Data Processing Starts Here

            $_utility = getModel('utility');
            $_bibdata = getModel('bibdata');
            $_ux = getModel('ux');

            $parsedItems = array();
            $parsedTypes = $_utility->getParsedItemTypes();
            $parsedItems = $licr->getArray('CRStudentHome', array('course_id' => $course_id, 'puid' => $puid));
            foreach ($parsedItems as $item_id => $item) {
                $bibdata = $_bibdata->getBibdata($item['bibdata'], $item['format']);
                $parsedItems[$item_id]['bibdata'] = $bibdata['bibdata'];
                $parsedItems[$item_id]['fieldmap'] = $bibdata['fieldmap'];
                $parsedItems[$item_id]['fieldtitles'] = $bibdata['fieldtitles'];
                $parsedItems[$item_id]['taglist'] = '';
                foreach ($item['tags'] as $tag_id => $tag_data) {
                    $__t = 't' . md5($tag_data ['name']);
                    $parsedItems[$item_id]['taglist'] .= ',' . $__t;
                    $item['tags'][$tag_id]['hash'] = $__t;
                }
                $parsedItems[$item_id]['tags'] = $item['tags'];
                $parsedItems[$item_id]['taglist'] = ltrim($parsedItems[$item_id]['hash'], ',');
                $parsedItems[$item_id]['format_icon'] = $_ux->getIcon($item['format']);
                $parsedItems[$item_id]['inactive'] = $item['request_end'];
                foreach ($item['additional_access'] as $urlid => $addurl) {
                    $parsedItems[$item_id]['addurls'][$urlid] = $addurl;
                }
            }
            $tvars ['readings'] = $parsedItems;
            $tvars ['course_id'] = $course_id;

            $enrolment = $licr->getArray('Enrolment');

            if (isset ($enrolment [$course_id])) {
                $tvars ['enrolled'] = TRUE;
                $tvars ['subscription'] = $licr->getArray('IsSubscribed', array(
                    'puid'   => $puid,
                    'course' => $course_id
                )) ? 1 : 0;
            } else {
                $tvars ['enrolled'] = FALSE;
                $tvars ['subscription'] = FALSE;
            }
            $tvars['print'] = gv('print', '0');
            return $tvars;
        }
    }
