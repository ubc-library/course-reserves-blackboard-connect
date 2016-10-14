<?php
/**
 * Class Controller_search
 */
    class Controller_search
    {

        /**
         * @return array
         */
        public function search()
        {
            include(Config::get('approot') . '/core/summon.inc.php');
            include(Config::get('approot') . '/core/voyager.inc.php');
            include(Config::get('approot') . '/core/voyagerreserves.inc.php');
            include(Config::get('approot') . '/core/ezproxy.inc.php');

            $template_vars = array();
            $data = array();

            $field = pv('field');
            $type = pv('type');

            error_log("Searching: case $type, field $field, value " . pv($field));

            $value = trim(pv($field));

            switch ($type) {
                case 'article':
                    if ($field == 'title') {
                        $data = findJAInSummon(pv('jtitle'), pv('atitle'));
                        error_log("Searched article->title->findJAInSummon");
                    } else {
                        if ($field == 'doi' || $field == 'pmid') {
                            $data = findInSummonById($field, $value);
                            error_log("Searched article->title->findInSummonById");
                        } else { // PURL
                            //TODO ask Rod or Yvonne - when does this condition ever get met???????
                            $data = array(array(
                                              'source' => 'web',
                                              'id'     => base64_encode($value),
                                          ));
                            error_log("Searched article->title->else");
                        }
                    }
                break;
                case 'book':
                    error_log("Find in Summon by: $field");
                    $func = 'findInSummonBy' . ucfirst($field);

                    $data = $func($value, array('Book', 'eBook', 'Book Chapter', 'Dissertation'));
                break;
                case 'chapter':
                    $func = 'findInSummonBy' . ucfirst($field);
                    error_log("Find in Summon by: $field");
                    $data = $func($value, array('Book', 'eBook', 'Book Chapter', 'Dissertation'));
                break;
                case 'summon':
                    $data = findInSummon(
                        array(
                            array('', $field)
                        ),
                        array()
                    );
                break;
                case 'stream':
                    $func = 'findInSummonBy' . ucfirst($field);
                    $data = $func($value,
                        array(
                            'Video Recording',
                            'Audio Recording',
                            'Music Recording',
                            'Spoken Word Recording'
                        ));
                    //Not using Voyager now except for webcat lookup. $func='findInVoyagerBy'.ucfirst($field); $data2=$func($post[$field]); $data=array_merge($data,$data2);
                break;
                case 'web':
                    $url = trim(pv('url'));
                    $data = array(array(
                                      'source'      => 'web',
                                      'id'          => base64_encode($url) . '.' . base64_encode(pv('title')),
                                      'contenttype' => 'Web Resource'
                                  ));
                break;
                case 'voyager':
                    //course reserve import
                    $data = getVoyagerReserves(pv('coursecode'));
                break;
            }

            error_log("The datas:");
            error_log(json_encode($data));


            $raw = $data['documents'];

            //$template_vars['json'] = json_encode("hello");
            if (isset($data['documents']) && count($data['documents']) > 0) {
                $summon = getModel('summon');
                $data['documents'] = $summon->convertToLocrMetadata($data['documents'], $type);
            }

            $template_vars['template'] = 'json';
            header('Content-type:application/json');
            //restrict
            if ($field === 'doi') {
                foreach ($data['documents'] as $result) {
                    if (isset($result['item_doi']) && $result['item_doi'] == $value) {
                        unset($data['documents']);
                        $data['documents'][] = $result;
                        break;
                    }
                }
            }

            if ($field === 'pmid') {
                foreach ($data['documents'] as $result) {
                    if (isset($result['item_pmid']) && $result['item_pmid'] == $value) {
                        unset($data['documents']);
                        $data['documents'][] = $result;
                        break;
                    }
                }
            }

            $data['raw'] = $raw;


            $template_vars['json'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $template_vars;
        }


        /**
         * @return array
         */
        public function uriexists()
        {

            /* Le Constant Stuff*/
            $template_vars = array();
            $template_vars['template'] = 'json';
            header('Content-type:application/json');

            /* Le Dynamic Stuff */
            $uri = pv('uri');

            /* Le Condtional Stuff */
            $licr = getModel('licr'); //add in checks to only load if you got stuff from pv?
            $results = $licr->getArray('SearchItems', array('search_string' => $uri));

            /* Le Processing Stuff */
            if (isset($results) && count($results)) {
                foreach ($results as $k => $v) {
                    $template_vars['json'] = json_encode($k);
                }
            } else {
                $template_vars['json'] = json_encode("No Items Found");
            }

            return $template_vars;
        }


        /**
         * @return array
         */
        public function exists()
        {

            /* Le Constant Stuff*/
            $template_vars = array();
            $template_vars['template'] = 'json';
            header('Content-type:application/json');

            /* Le Dynamic Stuff */
            if (!(pv('ss')) || ctype_space(pv('ss')) || count(pv('ss')) < 1) {
                $template_vars['json'] = json_encode("No Items Found");
            } else {
                $ss = pv('ss');
                /* Le Condtional Stuff */
                $licr = getModel('licr'); //add in checks to only load if you got stuff from pv?
                $results = $licr->getArray('SearchItems', array('search_string' => $ss));

                /* Le Processing Stuff */
                if (isset($results) && count($results)) {
                    foreach ($results as $k => $v) {
                        $template_vars['json'] = json_encode($k);
                        break;
                    }
                } else {
                    $template_vars['json'] = json_encode("No Items Found");
                }
            }


            return $template_vars;
        }

    }
