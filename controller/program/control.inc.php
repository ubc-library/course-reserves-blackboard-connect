<?php
    
    class Controller_program {
        
        var $useCache = 0;
        
        function program ()
        {
            // init
            $program_id = gv ('id');
            $licr = getController ('licr');
            $tvars = [];
            
            if (!sv ('puid')) {
                //not logged in, we have now finally said no access unless we know who you are
                $redirect = $licr->getArray (
                    'PrependedURL',
                    ['url' => '/webapps/ubc-library-module-BBLEARN/ubclibrary-static/get.jsp?hash=p.' . $program_id]);
                redirect ($redirect);
            }
            
            // determine if program exists
            $program_info = $licr->getArray ('GetProgramInfo', [
                'program' => $program_id
            ], $this->useCache);
            
            if (!$program_info) {
                return [
                    'controller_error' => 'Program could not be found. Please contact your instructor.'
                ];
            } else {
                // init template return array
                $tvars ['program_info'] = $program_info;
            }
            
            $lpc = $licr->getArray ('ListProgramCourses', [
                'program' => $program_id
            ], $this->useCache);
            $courses = $lpc ['courses'];
            
            // if no courses, return
            if (!$courses) {
                unset ($tvars);
                
                return [
                    'controller_error' => 'Program has no courses'
                ]; // might want to change this to an alert div within the tvars return though
            }
            if (!gv ('open')) {
                $storedvars = MC::get ('prg' . $program_id);
                if ($storedvars) {
                    error_log ('Using stored program data');
                    
                    return $storedvars;
                }
                error_log ('Generating new program data for program ' . $program_id);
            }
            
            // found courses
            
            // init return arrays
            $supplied_tags = $licr->getArray ('ListProgramTags', [
                'program' => $program_id
            ], $this->useCache);
            $shortcodeof = [];
            $tags = [];
            $course_items = [];
            $items = [];
            
            $iinforeq = [];
            $cinforeq = [];
            
            // populate return arrays
            $shortcodes = $lpc ['shortcodes'];
            ksort ($shortcodes);
            foreach ($shortcodes as $short => $course_ids) {
                foreach ($course_ids as $course_id) {
                    $shortcodeof [$course_id] = $short;
                }
            }
            $tags_courses = [];
            foreach ($supplied_tags as $tag_name => $tag_ids) {
                $hash = 't' . md5 ($tag_name);
                if (!isset($tags[$hash])) {
                    $tags[$hash] = [];
                    $tags[$hash]['tag_name'] = $tag_name;
                    $tags[$hash]['tag_ids'] = $tag_ids;
                    $tags[$hash]['tag_course_ids'] = [];
                }
                foreach ($tag_ids as $tag_id) {
                    $tag_info = $licr->getArray ('GetTagInfo', ['tag' => $tag_id], true);
                    $tags[$hash]['tag_course_ids'][] = $tag_info['course_id'];
                    if (!isset($tags_courses[$hash])) {
                        $tags_courses[$hash] = [$tag_info['course_id']];
                    } else {
                        $tags_courses[$hash][] = $tag_info['course_id'];
                    }
                }
            }
            //var_dump($supplied_tags);
            
            $tvars['showtag'] = false;
            if ($showtag = gv ('tag_id', false)) {
                $show_tag_info = $licr->getArray ('GetTagInfo', ['tag' => $showtag], true);
                $show_tag_hash = 't' . md5 ($show_tag_info['name']);
                if (isset($tags[$show_tag_hash])) {
                    $tvars['showtag'] = $show_tag_hash;
                }
            }
            
            foreach ($courses as $course_id => $title) {
                $course_items [$course_id] = $licr->getArray ('ListCIs', [
                    'course'  => $course_id,
                    'visible' => 1
                ]);
                // $course_items[$course_id]=$licr->getArray('ListProgramCIs',array('course'=>$course_id));
            }
            
            foreach ($course_items as $course_id => $cis) {
                $temp = $licr->getArray ('GetCIInfo', [
                    'course' => $course_id
                ]);
                if ($temp) {
                    foreach ($temp as $id => $row) {
                        $cinforeq [$id] = $row;
                        //error_log(var_export($row,true));
                    }
                    unset ($temp);
                    foreach ($cis as $item_id => $item_info) {
                        if ($courses [$course_id] ['historical'] && ($cinforeq [$item_id] ['fairdealing'] || $cinforeq [$item_id] ['transactional'])) {
                            // do not show encumbered items for expired courses
                            continue;
                        }
                        //LOCRSUPP-453 exclude readings from the future
                        if (strtotime ($item_info['item_start']) > date ('U')) {
                            //error_log('future!');
                            continue;
                        }
                        $iinforeq [] = $item_id;
                        if (!isset ($items [$item_id])) {
                            $items [$item_id] = $item_info;
                            $items [$item_id] ['course_ids'] = [];
                        } else { // merge in tags from other course if present
                            $items [$item_id] ['tags'] = array_unique (array_merge ($items [$item_id] ['tags'], $item_info ['tags']));
                        }
                        if (isset ($cinforeq [$item_id])) {
                            $items [$item_id] ['loanperiod'] = $cinforeq [$item_id] ['loanperiod'];
                            $items [$item_id] ['purl'] = $cinforeq [$item_id] ['shorturl'];
                            $items [$item_id] ['url'] = $cinforeq [$item_id] ['uri'];
                            $items [$item_id] ['required'] = $cinforeq [$item_id] ['required'];
                        }
                        $items [$item_id] ['course_ids'] [] = $course_id;
                    }
                }
            }
            
            
            if ($iinforeq) {
                $multi_iinfo = $licr->getArray ('GetItemInfo', [
                    'item_id' => implode (',', $iinforeq)
                ]);
                foreach ($multi_iinfo as $item_id => $data) {
                    if (isset ($data ['callnumber']) && $data ['callnumber'] != '') {
                        $items [$item_id] ['callnumber'] = $data ['callnumber'];
                    }
                    $items [$item_id] ['purl'] = $data ['shorturl'];
                    $items [$item_id] ['url'] = $data ['uri'];
                    $items [$item_id] ['additional_access'] = $data ['additional access'];
                }
            } else {
                $items = [];
            }
            /* TEMPORARY: TODO lightbox in program view */
            //error_log("items: ".var_export($items,true));
            if ($open = gv ('open')) {
                // WEDNESDAY:
                //find course that is in program with this item and puid is enrolled in
                // if one exists: go to url
                // if not: check if encumbered
                //   if not encumbered: go to url
                //   if encumbered: error
                $programs = $licr->getArray ('GetEnrolledPrograms', []);
                if (isset($programs[$program_id])) {
                    //is enrolled
                    if (isset($items[$open])) {
                        redirect ($items[$open]['url']);
                    } else {
                        //crap. Probably came from a stale link
                        $itemInfo = $licr->getArray ('GetItemInfo', ['item_id' => $open]);
                        if (!$itemInfo) {
                            return ['controller_error' => 'The item you have requested has not been found in the Course Reserves system.'];
                        }
                        redirect ($itemInfo['uri']); // godspeed
                        exit();
                    }
                } else {
                    //find course containing item
                    $program_courses = $licr->getArray ('ListProgramCourses', [
                        'program' => $program_id
                    ]);
                    $program_course_ids = array_keys ($program_courses['courses']);
                    $item_course_ids = array_keys ($licr->getArray ('GetCoursesByItem', [
                        'item' => $open
                    ]));
                    $course_id = false;
                    foreach ($program_course_ids as $program_course_id) {
                        if (in_array ($program_course_id, $item_course_ids)) {
                            $course_id = $program_course_id;
                            break;
                        }
                    }
                    if (!$course_id) {
                        error_log ('program/open: cannot find course for item ' . $open);
                        
                        return ['controller_error' => 'Error: orphan item ' . $open];
                    }
                    $instance = $licr->getArray ('GetCIInfo', [
                        'course'  => $course_id,
                        'item_id' => $open
                    ]);
                    if (!$instance['visible_to_student']) {
                        return ['controller_error' => 'This item has not yet been processed for release.'];
                    }
                    $start = strtotime ($instance ['dates'] ['course_item_start']);
                    $end = strtotime ($instance ['dates'] ['course_item_end']);
                    $now = date ('U');
                    $current = (($start < $now) && ($end > $now));
                    $encumbered = false;
                    if ($current) {
                        $encumbered |= ($instance ['fairdealing'] | $instance ['transactional']);
                    }
                    if ($encumbered) {
			require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php'); 
                        $isStaff = idboxCall ( 'InGroup', array (
                             'group_name' => Config::get ( 'idbox_group_access' ),
                             'puid' => sv ( 'puid' )
                        ) );
	                if($isStaff){
                            error_log ('redirect (staff) to [' . $items[$open]['url'] . ']');
                            redirect ($items[$open]['url']);
	                }
                        return ['controller_error' => 'This item is restricted to currently enrolled students in this program.'];
                    } else {
                        error_log ('redirect to [' . $items[$open]['url'] . ']');
                        redirect ($items[$open]['url']);
                    }
                }
            }
            
            foreach ($items as $item_id => $data) {
                foreach ($data ['tags'] as $tag_id => $tag) {
                    $items [$item_id] ['tags'] [$tag_id] = [
                        'hash' => 't' . md5 ($tag),
                        'name' => $tag
                    ];
                }
            }
            
            // return
            $tvars ['courses'] = $courses;
            $tvars ['shortcodeof'] = $shortcodeof;
            $tvars ['coursetags'] = $shortcodes;
            $tvars ['tags'] = $tags;
            $tvars ['items'] = $items;
            $tvars ['tags_courses'] = $tags_courses;
            $tvars['open'] = false;
            if (gv ('open')) {
                $tvars['open'] = gv ('open');
            } else {
                MC::set ('prg' . $program_id, $tvars, 7200);
            }
            
            //var_dump($tvars);
            return $tvars;
        }
    }
