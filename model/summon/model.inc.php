<?php

    /**
     * Class Model_summon
     */
    class Model_summon
    {

        /**
         * @param $summonDocumentsUnDeDuped
         * @param $type
         * @return array
         */
        function convertToLocrMetadata($summonDocumentsUnDeDuped, $type)
        {

            //type - 'article', 'book', 'chapter', 'stream'

            $result = array();
            $_index = 0;

            $data = [];

            foreach ($summonDocumentsUnDeDuped as $entry) {

                $tmp = [];

                if (!empty($entry['peerDocuments'])) {
                    $tmp = $entry['peerDocuments'];
                    unset($entry['peerDocuments']);
                }

                array_push($data, $entry);

                foreach ($tmp as $td) {
                    array_push($data, $td);
                }

            }


            foreach ($data as $entry) {
                //convert the type to a submission "physical_format"
                $processingFormat = $this->getProcessingFormat($entry, $type);

                // -- start stuff within bibdata

                //can always exist
                $result[$_index]['item_title'] = htmlspecialchars($this->getTitle($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['item_author'] = htmlspecialchars($this->getAuthor($entry, $processingFormat), ENT_QUOTES);

                //almost always
                $result[$_index]['item_incpages'] = htmlspecialchars($this->getInclusivePages($entry, $processingFormat), ENT_QUOTES);

                //little less
                $result[$_index]['item_publisher'] = htmlspecialchars($this->getPublisher($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['item_pubplace'] = htmlspecialchars($this->getPublicationPlace($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['item_pubdate'] = htmlspecialchars($this->getPublicationDate($entry, $processingFormat), ENT_QUOTES);

                //expecting a miracle
                $result[$_index]['item_edition'] = htmlspecialchars($this->getEdition($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['item_editor'] = htmlspecialchars($this->getEditor($entry, $processingFormat), ENT_QUOTES);

                $result[$_index]['item_isxn'] = htmlspecialchars($this->getISXN($entry, $processingFormat), ENT_QUOTES);

                //conditional - based on type
                $result[$_index]['item_doi'] = htmlspecialchars($this->getDOI($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['item_callnumber'] = htmlspecialchars($this->getCallNumber($entry, $processingFormat), ENT_QUOTES);

                //conditional - so far, if chapter
                $result[$_index]['collection_title'] = htmlspecialchars($this->getCollectionTitle($entry, $processingFormat), ENT_QUOTES);

                //conditional - if electronic article
                $result[$_index]['journal_title'] = htmlspecialchars($this->getJournalTitle($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['journal_volume'] = htmlspecialchars($this->getJournalVolume($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['journal_issue'] = htmlspecialchars($this->getJournalIssue($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['journal_month'] = htmlspecialchars($this->getJournalMonth($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['journal_year'] = htmlspecialchars($this->getJournalYear($entry, $processingFormat), ENT_QUOTES);
                // -- end stuff within bibdata

                $result[$_index]['initial_uri'] = $this->getURI($entry, $processingFormat, $result[$_index]);//we need this object for restricted objects
                $result[$_index]['form_type'] = htmlspecialchars(str_replace('-', '_', $this->getProcessingFormat($entry, $type, $result[$_index]['item_callnumber'])), ENT_QUOTES);
                $result[$_index]['form_type_display'] = htmlspecialchars($this->getFormat($entry, $processingFormat), ENT_QUOTES);

                //only on search results page
                $result[$_index]['subject_terms'] = $this->getSubjectTerms($entry, $processingFormat);
                $result[$_index]['abstract'] = htmlspecialchars($this->getAbstract($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['availability_id'] = htmlspecialchars($this->getAvailabilityId($entry, $processingFormat), ENT_QUOTES);
                $result[$_index]['summon'] = $entry['ID'];

                ++$_index;
            }

            /*
             *   convert to UTF-8
             */

            $ret = @json_encode($result);
            $e = json_last_error();
            if ($e == JSON_ERROR_UTF8 && isset($result)) {
                //	$ret=@json_encode($val,JSON_UNESCAPED_UNICODE);
                //	$e=json_last_error();
                if ($e) {
                    $result = $this->array_recode('Latin1..UTF8', $result);
                    //echo $val['data'];
                    $ret = @json_encode($result);
                    $e = json_last_error();
                }
            }
            if ($e && ($e != JSON_ERROR_UTF8)) {
                error_log('JSON error not utf8 related: ' . $e);
            }
            unset($ret);// because I wholesale copied and pasted from licr::api.php

            return $result;
        }


        /**
         * @param $entry
         * @param $type
         * @param null $callnumberCheck
         * @return string
         */
        private function getProcessingFormat($entry, $type, $callnumberCheck = NULL)
        {
            if ($type == 'stream') {
                if (isset($entry['ContentType'])) {
                    if (strpos(strtolower($entry['ContentType'][0]), 'video') !== FALSE) {
                        if (isset($callnumberCheck) && $callnumberCheck !== NULL && $callnumberCheck !== "") {
                            return "stream-video--switch-type-physical";
                        } else {
                            return "stream-video";
                        }
                    } else {
                        if (strpos(strtolower($entry['ContentType'][0]), 'music') !== FALSE) {
                            if (isset($callnumberCheck) && $callnumberCheck !== NULL && $callnumberCheck !== "") {
                                return "stream-music--switch-type-physical";
                            } else {
                                return "stream-music";
                            }
                        }
                    }
                } else {
                    return 'stream-general';
                }
            } else {
                if ($type == 'article') {
                    return "electronic-article";
                } else {
                    if ($type == 'chapter') {
                        if (isset($entry['ContentType'])) {
                            for ($i = 0; $i < count($entry['ContentType']); $i++) {
                                if (strtolower($entry['ContentType'][$i]) == 'ebook') {
                                    return "ebook-chapter";
                                }
                            }
                            return "book-chapter";
                        } else {
                            return "book-chapter";
                        }
                    } else {
                        if ($type == 'book') {
                            if (isset($entry['ContentType'])) {
                                # the Content Type is duplicated, but hey, if ebook isn't in there, it aint an ebook
                                # to fix dupes, 99% of times, $entry['hasFullText'] means that e-book is fulltext, and $entry['hasFullText'] == false means a physical book
                                for ($i = 0; $i < count($entry['ContentType']); $i++) {
                                    if (strtolower($entry['ContentType'][$i]) == 'ebook' && $entry['hasFullText']) {
                                        return "ebook-general";
                                    }
                                }

                                # something can now be a 'book' Content Type (vs. eBook), but be 'digital' copy, and this is the only way found so far to determine this
                                if ($entry['hasFullText'] && $entry['inHoldings']) {
                                    return "ebook-general";
                                }

                                return "book-general";
                            } else {
                                if ($entry['hasFullText'] && $entry['inHoldings']) {
                                    return "ebook-general";
                                }
                                if (!$entry['hasFullText'] && $entry['inHoldings']) {
                                    return "book-general";
                                }
                            }
                        }
                    }
                }
            }
            return 'undetermined';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getCollectionTitle($entry, $processingFormat)
        {
            if (!in_array($processingFormat, array('ebook-chapter', 'book-chapter', 'pdf-chapter'))) {
                return "";
            }

            if (isset($entry['Title'])) {
                if (isset($entry['Subtitle'])) {
                    return $entry['Title'][0] . ": " . $entry['Subtitle'][0];
                }
                return $entry['Title'][0];
            } else {
                return "";
            }
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getTitle($entry, $processingFormat)
        {
            if (in_array($processingFormat, array('ebook-chapter', 'book-chapter'))) {
                return "";
            }

            if (isset($entry['Title'])) {
                if (isset($entry['Subtitle'])) {
                    return $entry['Title'][0] . ": " . $entry['Subtitle'][0];
                }
                return $entry['Title'][0];
            } else {
                return "";
            }
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getAuthor($entry, $processingFormat)
        {
            if (isset($entry['Author'])) {
                return implode(';', $entry['Author']);
            } else {
                if (isset($entry['CorporateAuthor'])) {
                    return implode(';', $entry['CorporateAuthor']);
                }
            }

            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getInclusivePages($entry, $processingFormat)
        {
            if (isset($entry['StartPage'][0])) {
                if (isset($entry['EndPage'][0])) {
                    return $entry['StartPage'][0] . " - " . $entry['EndPage'][0];
                }
                return $entry['StartPage'][0];
            }

            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getJournalTitle($entry, $processingFormat)
        {
            return ($processingFormat === 'electronic-article' ? (isset($entry['PublicationTitle']) ? $entry['PublicationTitle'][0] : '') : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return bool|string
         */
        private function getJournalMonth($entry, $processingFormat)
        {
            return ($processingFormat === 'electronic-article' ? (isset($entry['PublicationDate_xml']) ? (isset($entry['PublicationDate_xml'][0]['month']) ? isset($entry['PublicationDate_xml'][0]['month']) : '') : '') : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return bool|string
         */
        private function getJournalYear($entry, $processingFormat)
        {
            return ($processingFormat === 'electronic-article' ? (isset($entry['PublicationDate_xml']) ? (isset($entry['PublicationDate_xml'][0]['year']) ? isset($entry['PublicationDate_xml'][0]['year']) : '') : '') : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getJournalVolume($entry, $processingFormat)
        {
            return ($processingFormat === 'electronic-article' ? (isset($entry['Volume']) ? $entry['Volume'][0] : '') : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getJournalIssue($entry, $processingFormat)
        {
            return ($processingFormat === 'electronic-article' ? (isset($entry['Issue']) ? $entry['Issue'][0] : '') : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getPublisher($entry, $processingFormat)
        {
            if (isset($entry['Publisher'])) {
                if (count($entry['Publisher']) > 1) {
                    //find the longest entry (hopefully the most detailed?)
                    $publisher = '';
                    foreach ($entry['Publisher'] as $temp) {
                        if (strlen($temp) > strlen($publisher)) {
                            $publisher = $temp;
                        }
                    }
                    return $publisher;
                } else {
                    return $entry['Publisher'][0];
                }
            }
            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getPublicationPlace($entry, $processingFormat)
        {
            if (isset($entry['PublicationPlace'])) {
                if (count($entry['PublicationPlace']) > 1) {
                    //find the longest entry (hopefully the most detailed?)
                    $place = '';
                    foreach ($entry['PublicationPlace'] as $temp) {
                        if (strlen($temp) > strlen($place)) {
                            $place = $temp;
                        }
                    }
                    return $place;
                } else {
                    return $entry['PublicationPlace'][0];
                }
            }
            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getPublicationDate($entry, $processingFormat)
        {
            if (isset($entry['PublicationDate'])) {
                if (count($entry['PublicationDate']) > 1) {
                    //find the longest entry (hopefully the most detailed?)
                    $date = '';
                    foreach ($entry['PublicationDate'] as $temp) {
                        if (strlen($temp) > strlen($date)) {
                            $date = $temp;
                        }
                    }
                    return $date;
                } else {
                    return $entry['PublicationDate'][0];
                }
            }
            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getEdition($entry, $processingFormat)
        {
            return (isset($entry['Edition']) ? $entry['Edition'][0] : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getEditor($entry, $processingFormat)
        {
            //does not exists in summon...
            return (isset($entry['Editor']) ? $entry['Editor'][0] : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getSubjectTerms($entry, $processingFormat)
        {
            if (isset($entry['SubjectTerms'])) {
                return implode(';', $entry['SubjectTerms']);
            }
            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getAbstract($entry, $processingFormat)
        {
            return (isset($entry['Abstract']) ? $entry['Abstract'][0] : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getFormat($entry, $processingFormat)
        {
            return ucwords(str_replace('-', ' ', $processingFormat)) . (isset($entry['ContentType']) ? " (" . $entry['ContentType'][0] . ")" : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getISXN($entry, $processingFormat)
        {
            if ($processingFormat == 'electronic-article') {
                return (isset($entry['EISSN'])) ? 'e__' . $entry['EISSN'][0] : ((isset($entry['ISSN'])) ? $entry['ISSN'][0] : '');
            } else {
                return (isset($entry['EISBN'])) ? 'e__' . $entry['EISBN'][0] : ((isset($entry['ISBN'])) ? $entry['ISBN'][0] : '');
            }
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getDOI($entry, $processingFormat)
        {
            if (isset($entry['DOI'])) {
                return $entry['DOI'][0];
            }

            $doi = '';

            if (isset($entry['openUrl'])) {
                $re = '/info:doi\/(.+)&?/';
                $str = strtolower($entry['openUrl']);
                preg_match($re, $str, $matches);
                if ($matches && $matches > 0) {
                    $doi = $matches[1];
                }
            }
            if ($doi !== '') {
                return $doi;
            }


            if (isset($entry['URI'])) {
                $re = '/doi=(.+)&?/';
                $str = strtolower($entry['URI'][0]);
                preg_match($re, $str, $matches);
                if ($matches && $matches > 0) {
                    $doi = $matches[1];
                }
            }
            if ($doi !== '') {
                return $doi;
            }

            return '';
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @param $constructedBibdata
         * @return string
         */
        private function getURI($entry, $processingFormat, $constructedBibdata)
        {
            $uri = (isset($entry['link']) ? $entry['link'] : '');
            $uri = $this->isHBR($uri, $constructedBibdata);

            return $uri;
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getCallNumber($entry, $processingFormat)
        {
            return (isset($entry['LCCallNum']) ? $entry['LCCallNum'][0] : '');
        }

        /**
         * @param $entry
         * @param $processingFormat
         * @return string
         */
        private function getAvailabilityId($entry, $processingFormat)
        {
            return (isset($entry['availabilityId']) ? $entry['availabilityId'] : '');
        }

        /**
         * @param $url
         * @param $obj
         * @return string
         */
        private function isHBR($url, $obj)
        {
            $test = $this->getHBRTest();//should define anything that must be tested against

            $objTitles = array(strtolower($obj['item_title']), strtolower($obj['journal_title']), strtolower($obj['collection_title']));
            $objISXN = strtolower($obj['item_isxn']);

            if ($objISXN == $test['isxn']) {
                return 'http://webcat.library.ubc.ca/vwebv/holdingsInfo?bibId=1197975';
            } else {
                foreach ($test['titles'] as $title) {
                    if (in_array($title, $objTitles)) {
                        return 'http://webcat.library.ubc.ca/vwebv/holdingsInfo?bibId=1197975';
                    }
                }
            }

            return $url;
        }

        /**
         * @return array
         */
        private function getHBRTest()
        {
            $harvardTitles = array('harvard business review', 'hbr');
            $harvardISSN = '0017-8012';

            return array('titles' => $harvardTitles, 'isxn' => $harvardISSN);
        }


        /**
         * @param $fromto
         * @param $input
         * @return array|string|void
         */
        private function array_recode($fromto, $input)
        {
            if (!is_array($input)) {
                $uns = @unserialize($input);
                if (is_array($uns)) {
                    $uns = array_recode($fromto, $uns);

                    return serialize($uns);
                } else {
                    $tmp = @json_encode($input);
                    $e = json_last_error();
                    if ($e) {
                        $fix = recode($fromto, $input);

                        // error_log("UTF8 fix from [$input] to [$fix]");
                        return $fix;
                    } else {
                        return $input;
                    }
                }
            } else {
                foreach ($input as $i => $v) {
                    $input [$i] = array_recode($fromto, $v);
                }

                return $input;
            }
        }

    }
