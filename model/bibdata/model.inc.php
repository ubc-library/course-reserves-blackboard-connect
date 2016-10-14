<?php

class Model_bibdata
{

    function getBibdata($bibdata, $type = null, $itemid = null)
    {
        return $this->findBibdata($bibdata, $type, $itemid);
    }

    public function updateBibdata($itemid, $newBibdata)
    {
        $licr = getModel('licr');

        //0 - no change, 1 - succeed, -1 - fail, -2 - fatal error
        $updateResult = array(
            'bibdata' => 0,
            'uri' => 0,
            'title' => 0,
            'author' => 0,
            'message' => ''
        );

        $updateUri = false;
        $updateTitle = false;
        $updateAuthor = false;

        $iinfo = $licr->getArray('GetItemInfo', array('item_id' => $itemid));
        $_original = unserialize($iinfo['bibdata']);
        Reportinator::json_log($_original);

        //get fields that have been changed
        $bibdata = json_decode($newBibdata, true);

        if (!isset($_original['item_uri']) && isset($bibdata['item_uri'])) {
            $updateUri = true;
        } elseif (isset($bibdata['item_uri']) && $bibdata['item_uri'] !== $_original['item_uri']) {
            $updateUri = true;
        }

        if (!isset($_original['item_title']) && isset($bibdata['item_title'])) {
            $updateTitle = true;
        } else if (isset($bibdata['item_title']) && $bibdata['item_title'] !== $_original['item_title']) {
            $updateTitle = true;
        }

        if (!isset($_original['item_author']) && isset($bibdata['item_author'])) {
            $updateAuthor = true;
        } else if (isset($bibdata['item_author']) && $bibdata['item_author'] !== $_original['item_author']) {
            $updateAuthor = true;
        }

        foreach ($bibdata as $k => $v) {
            error_log("Bibdata Model (updating values): Key $k|(Old: [" . $_original[$k] . "], New: [$v])");
            $_original[$k] = $v;
        }

        $ret = @json_encode($_original);
        $e = json_last_error();
        if ($e == JSON_ERROR_UTF8 && isset($_original)) {
            //      $ret=@json_encode($val,JSON_UNESCAPED_UNICODE);
            //      $e=json_last_error();
            if ($e) {
                $_original = $this->array_recode('Latin1..UTF8', $_original);
                $ret = @json_encode($_original);
                $e = json_last_error();
            }
        }
        if ($e && ($e != JSON_ERROR_UTF8)) {
            error_log('JSON error not utf8 related: ' . $e);
        }
        unset($ret); // because I wholesale copied and pasted from licr::api.php

        if ($updateAuthor) {
            $resultAu = json_decode($licr->getJSON('SetItemAuthor', array('item_id' => $itemid, 'author' => $_original['item_author'], 'puid' => sv('puid'))));
            $updateResult['author'] = isset($resultAu->success) ? ($resultAu->success === true ? 1 : -1) : -2;
        }
        if ($updateTitle) {
            $resultTi = json_decode($licr->getJSON('SetItemTitle', array('item_id' => $itemid, 'title' => $_original['item_title'], 'puid' => sv('puid'))));
            $updateResult['title'] = isset($resultTi->success) ? ($resultTi->success === true ? 1 : -1) : -2;
        }
        if ($updateUri) {
            $resultURI = json_decode($licr->getJSON('SetItemURI', array('item_id' => $itemid, 'uri' => $bibdata ['item_uri'], 'puid' => sv('puid'))));
            $resultFL = json_decode($licr->getJSON('SetItemFileLocation', array('item_id' => $itemid, 'filelocation' => $bibdata ['item_uri'], 'puid' => sv('puid'))));
            $temp1 = isset($resultURI->success) ? ($resultURI->success === true ? 1 : -1) : -2;
            $temp2 = isset($resultFL->success) ? ($resultFL->success === true ? 1 : -1) : -2;
            $updateResult['uri'] = ($temp1 == -2 || $temp2 == -2 ? -2 : $temp1);
        }
        $_original = serialize($_original);


        $result = json_decode($licr->getJSON('SetItemBibData', array('item_id' => $itemid, 'bibdata' => $_original, 'puid' => sv('puid'))));

        $updateResult['bibdata'] = isset($result->success) ? ($result->success === true ? 1 : -1) : -2;

        $report = false;
        $ticket = false;

        if ($updateResult['bibdata'] == 1) {
            $updateResult['message'] .= 'Bibdata Updated.';
        }

        $fails = array_keys($updateResult, -1);
        if (isset($fails) && count($fails) > 0) {
            $updateResult['message'] .= "\n\r The following keys failed to update: " . implode(',', $fails);
            $report = true;
            $ticket = false;
        }

        $fatals = array_keys($updateResult, -2);
        if (isset($fails) && count($fails) > 0) {
            $updateResult['message'] .= "\n\rThe following keys threw fatal errors: " . implode(',', $fatals);
            $report = true;
            $ticket = false;
        }

        if ($report) {
            Reportinator::alertDevelopers("Bibdata Model: attempting to update item$itemid", $updateResult['message']);
        }
        if ($ticket) {
            Reportinator::createTicket("Bibdata Model: attempting to update item$itemid", $updateResult['message']);
        }

        return $updateResult;

    }

    private function getMappedBibdata($data, $itemid)
    {

        $_metadata = getModel('metadata');

        if ($itemid == 20350) {
            error_log("Item $itemid type: " . $data['processingType']);
        }
        $metadata = $_metadata->getMetadata($data['processingType']);
        $metadataMap = $_metadata->getMetadataMap($data['engine'], $data['processingType']);

        $bibdataraw = $data['bibdata'];
        if (!isset($bibdataraw['availability_id'])) {
            $bibdataraw['availability_id'] = "[MISSING]";
        }

        $newbibdata = array();

        foreach ($metadata['fields'] as $field) {
            if (isset($bibdataraw[$metadataMap[$field]]) && trim($bibdataraw[$metadataMap[$field]]) != '') {
                $newbibdata[$field] = trim($bibdataraw[$metadataMap[$field]]);
            } else {
                $newbibdata[$field] = '';
            }
        }
        //slowly auto-fix existing data
        $data['engine'] = str_replace('-', '_', $data['engine']);
        if ($data['engine'] !== 'cr_system') {
            $res = $this->convert($itemid, $data);
            if ($res) {
                $licr = getModel('licr');
                $iinfo = $licr->getArray('GetItemInfo', array('item_id' => $itemid));

                return self::getBibdata($iinfo['bibdata'], $iinfo['physical_format']);
            } else {
                //Reportinator::createTicket("Error Converting Bibdata (Item $itemid", "Please check the logs concerning the conversion of this item, the item failed to have it's bibdata converted.");
                return array('bibdata' => $newbibdata, 'fieldmap' => $metadataMap, 'fieldtitles' => $metadata['titles'], 'availabilityId' => $bibdataraw['availability_id']);
            }
        } else {
            return array('bibdata' => $newbibdata, 'fieldmap' => $metadataMap, 'fieldtitles' => $metadata['titles'], 'availabilityId' => $bibdataraw['availability_id']);
        }
    }

    private function convert($itemid, $data)
    {
        $licr = getModel('licr');
        $_metadata = getModel('metadata');
        $_utility = getModel('utility');

        $iinfo = $licr->getArray('GetItemInfo', array('item_id' => $itemid));

        $types = $_utility->getParsedItemTypes();

        error_log("Item is: $itemid");

        $type = isset($types[$iinfo['type_id']])
            ? $types[$iinfo['type_id']]
            : array('name' => 'format_unknown');

        $convertTypes = array(
            "pdf" => "pdf_chapter",
            "physical" => "book_general",
            "web_page" => "web_general",
            "electronic_article" => "electronic_article",
            "ebook" => "ebook_general",
            "streaming_media" => "stream_general",
            "format_unknown" => "undetermined",
        );

        $format = $convertTypes[$type['name']];

        $metadata = $_metadata->getMetadata($format);
        $metadataMap = $_metadata->getMetadataMap($data['engine'], $format);

        $bibdataraw = $data['bibdata'];

        $newbibdata = array();

        foreach ($metadata['fields'] as $field) {
            if (isset($metadataMap[$field])
                && isset($bibdataraw[$metadataMap[$field]])
                && trim($bibdataraw[$metadataMap[$field]]) != ''
            ) {
                $newbibdata[$field] = trim($bibdataraw[$metadataMap[$field]]);
            } else {
                $newbibdata[$field] = '';
            }
        }

        if ($_metadata->exists($format)) {
            $entireBibdataObject = array(
                'item_title' => '',
                'item_author' => '',
                'item_incpages' => '',
                'item_publisher' => '',
                'item_pubplace' => '',
                'item_pubdate' => '',
                'item_edition' => '',
                'item_editor' => '',
                'item_isxn' => '',
                'item_doi' => '',
                'item_callnumber' => '',
                'collection_title' => '',
                'journal_title' => '',
                'journal_volume' => '',
                'journal_issue' => '',
                'journal_month' => '',
                'journal_year' => '',
                'initial_uri' => '',
                'form_type' => '',
                'form_type_display' => '',
                'subject_terms' => '',
                'abstract' => '',
                'availability_id' => '',
                'summon' => $data['bibdata'],
                'type' => $format,
                'interstitial' => 'converted_from_ares',
                'request_interstitial' => 'converted_from_ares',
                'start_date' => '',
                'end_date' => '',
                'request_note_student' => '',
                'request_note_staff' => '',
                'tags' => '',
                'request_required_reading' => false,
                'item_uri' => '',
                'request_start_date' => '',
                'request_end_date' => '',
                'request_tags' => '',
                'request_loan_period' => '',
                'engine' => 'cr-system',
            );

            $returnbibdata = array_merge($entireBibdataObject, $newbibdata);

            $_original = $returnbibdata;
            $ret = @json_encode($_original);
            $e = json_last_error();
            if ($e == JSON_ERROR_UTF8 && isset($_original)) {
                if ($e) {
                    $_original = $this->array_recode('Latin1..UTF8', $_original);
                    $ret = @json_encode($_original);
                    $e = json_last_error();
                }
            }
            if ($e && ($e != JSON_ERROR_UTF8)) {
                error_log('JSON error not utf8 related: ' . $e);
            }
            unset($ret);

            $sf = json_decode($licr->getJSON('UpdateItem', array('item_id' => $itemid, 'physical_format' => $format)), true);
            if ($sf["success"]) {
                return json_decode($licr->getJSON('SetItemBibData', array('item_id' => $itemid, 'bibdata' => serialize($_original), 'puid' => sv('puid'))), true)["success"];
            } else {
                error_log("Failed to set item physical_format to $format. LICR error message: " . $sf["message"]);

                return false;
            }
        } else {
            //error_log("Format $format does not exist");

            return false;
        }
    }


    private function findBibdata($bibdata, $type, $itemid)
    {

        //$bibdata = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $bibdata);
        $bibdataraw = unserialize($bibdata);

        /*
         * in database ----------------------------------------
         *
         * type is written to       item::physical_format
         * processingType to        item::bibdata['processing_type']
         * they are one and the same
         *
         * engine to                item::bibdata['engine']
         *
        */

        if (isset($type) && isset($bibdataraw['engine'])) {
            // $bibdataraw['engine'] should be 'cr_system'

            //error_log("bibdata model: engine => " . $bibdataraw['engine'] . "     processingType => " . $type);

            return $this->getMappedBibdata(array('bibdata' => $bibdataraw, 'engine' => $bibdataraw['engine'], 'processingType' => $type), $itemid);
        }

        if (isset($type)) {
            //error_log('Item was created with a type aka physical_format but bibdata[\'engine\'] was not written!');
            $processingType = $type;
        } else if (isset($bibdataraw['processingType'])) {
            //error_log('Item was created with a type aka physical_format in bibdata instead of physical_format table column!');
            $processingType = $bibdataraw['processingType'];
        } else if (!isset($bibdataraw['processingType']) || $type === null) {
            $processingType = 'undetermined';
        } else {
            //error_log('Unknown type of item found!');
            $processingType = null;
        }

        $engine = 'cr_system';

        //we do not want to reach here

        if ($res = $this->isAres($bibdataraw)) {
            //error_log("Model_bibdata::getBibdataContent(): Engine: " . $res['engine'] . " || Processing Type: " . $res['processingType']);

            return $this->getMappedBibdata(array('bibdata' => $res['bibdata'], 'engine' => $res['engine'], 'processingType' => $res['processingType']), $itemid);
        } else if ($res = $this->isDevelopment($bibdataraw)) {
            //error_log("Model_bibdata::getBibdataContent(): Engine: " . $res['engine'] . " || Processing Type: " . $res['processingType']);

            return $this->getMappedBibdata(array('bibdata' => $res['bibdata'], 'engine' => $res['engine'], 'processingType' => $res['processingType']), $itemid);
        } else {
            //error_log("Model_bibdata::getBibdataContent(): Engine: $engine || Processing Type: $processingType");

            return $this->getMappedBibdata(array('bibdata' => $bibdataraw, 'engine' => $engine, 'processingType' => $processingType), $itemid);
        }
    }

    private function isAres($bibdataraw)
    {
        if (array_key_exists('AresItem', $bibdataraw)) {
            return array('bibdata' => $bibdataraw['AresItem'], 'engine' => 'ares', 'processingType' => 'undetermined');
        }

        return false;
    }

    private function isDevelopment($bibdataraw)
    {
        $keys = array_keys($bibdataraw);
        $devOnlyFields = array('note_staff', 'note_student', 'locr_type', 'creator', 'submit_type', 'articletitle', 'articleauthors', 'journaltitle', 'journalmonth', 'journalyear', 'chaptertitle'); //these fields would only exist in items we added whilst under development

        $processingType = 'undetermined';

        foreach ($devOnlyFields as $field) {
            if (in_array($field, $keys)) {
                if (array_key_exists('locr_type', $bibdataraw) || array_key_exists('submit_type', $bibdataraw)) {
                    $processingType = $this->findExternalProcessingType($bibdataraw, strtolower(preg_replace('/[^\\w]+/', '_', $bibdataraw ['locr_type'])), strtolower(preg_replace('/[^\\w]+/', '_', $bibdataraw ['submit_type'])));
                } else if (isset($bibdataraw['callnumber'])) {
                    $processingType = 'book';
                    if (isset($bibdataraw['incpages'])) {
                        $processingType .= '_chapter';
                    } else {
                        $processingType .= '_general';
                    }
                }

                return array('bibdata' => $bibdataraw, 'engine' => 'development_one', 'processingType' => $processingType);
            }
        }

        return false;
    }

    private function findExternalProcessingType($data, $type, $qualifier)
    {

        //error_log(implode($data));

        if (in_array($type, array("pdf", "book", "ebook"))) {
            $type = rtrim($type . '_' . $qualifier, '_');

            //if no qualifier
            if (in_array($type, array("pdf", "book", "ebook", "physical"))) {
                if (in_array($type, array("pdf"))) {
                    if (array_key_exists('chaptertitle', $data)) {
                        $type .= '_chapter';
                    } else {
                        $type .= '_general';
                    }
                } else if (in_array($type, array("book", "ebook"))) {
                    if (array_key_exists('incpages', $data)) {
                        $type .= '_chapter';
                    } else {
                        $type .= '_general';
                    }
                } else if (in_array($type, array("physical"))) {
                    if (array_key_exists('isxn', $data)) {
                        error_log("Converting Physical-->Book (General)");
                        $type .= 'book_general';
                    } else {
                        $type .= 'physical_unknown_type';
                    }
                }
            } else {
                switch ($type) {
                    case 'ebook_book':
                        $type = "ebook_general";
                        break;
                    case 'pdf_docstore':
                        $type = "pdf_article";
                        break;
                    case 'physical_book':
                        $type = "book_general";
                        break;
                }
            }

            return $type;
        } else {
            switch ($type) {
                case 'web_page':
                    $res = "web_general";
                    break;
                case 'streaming_media':
                case 'streaming_media_stream':
                    $res = "stream_general";
                    break;
                case 'physical':
                    $res = "physical_unknown_type";
                    break;
                default:
                    $res = $type;
            }
        }

        return $res;
    }

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