<?php

    class Model_addreserves
    {

        private function getInitJSON($file){

            if (!($res = unserialize(MC::get(md5($file))))) {
                if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                    $json = file_get_contents(Config::get('approot') . "/www/js/addreserves/$file.json");
                    $json = str_replace(array("\n", "\r"), "", $json);
                    //$json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
                    //$json = preg_replace('/(,)\s*}$/', '}', $json);
                    $res = (json_decode($json, true));
                    MC::set(md5($file), serialize($res), MC::getDuration('short'));
                } else {
                    error_log('Unaccountable Memcached error ' . MC::getResultCode(). ' for file: '.$file);
                }
            }
            return $res;
        }

        private function hasLoanPeriod($formType){
            //the array below is items that do not have a loan period,
            //so we ! the answer for the sake of not having a negative named function?
            return !in_array($formType, array(
                'electronic_article',
                'ebook_general',
                'ebook_chapter',
                'pdf_general',
                'pdf_article',
                'pdf_chapter',
                'pdf_other',
                'stream_general',
                'stream_video',
                'stream_music',
                'book_chapter',
                'web_general'
            ));
        }

        private function enableURI($formType,$manualOnly)
        {
            //the array of items that have uris that are allowed to edit only on a forced manual entry (missing search result)
            if($manualOnly){
                return in_array($formType, array(
                    'electronic_article',
                    'stream_general',
                    'web_general'
                ));
            }
            //the array below is items that allow uri to be edited at all times
            return in_array($formType, array(
                'web_general'
            ));
        }

        private function getFieldWarning($field,$value,$flags) {
            $warnings = $this->getInitJSON("submit_field_value_warnings");
            foreach($warnings as $warning){
                if($warning['field'] == $field && $warning['value'] == $value) {
                    return array('flags' => array_merge($flags, array($warning['flag'] => true)), 'notice' => $warning['notice']);
                }
            }
        }

        private function itemExists($data){
            $checkExactDOI = false;
            $checkExactCallnumber = false;
            if (isset($data['item_doi']) && trim($data['item_doi']) != "") {
                $searchString = 'DOI:'.$data['item_doi'];
            } elseif (isset($data['item_callnumber']) && trim($data['item_callnumber']) != "") {
                $searchString = $data['item_callnumber'];
                $checkExactCallnumber = true;
            } elseif (isset($data['initial_uri']) && trim($data['initial_uri']) != "") {
                $searchString = $data['initial_uri'];
            }  else {
                error_log("could not create a search string");
                return false;
            }

            //error_log("search string: ".$searchString);

            $_licr  = getModel('licr');
            $res    = $_licr->getArray('SearchItems',array('search_string' => $searchString));
            if(isset($res) && count($res)>0){
                if($checkExactCallnumber){
                    foreach ($res as $iid => &$valsToCheck) {
                        if ($valsToCheck['callnumber'] === $searchString && $valsToCheck['physical_format'] === $data['form_type']){
                            return $iid;
                        }
                    }
                } else {
                    reset($res);
                    return key($res);
                }
            }
            return false;
        }

        private function getTitle($format,$next_status){
            $title = strtolower($format);
            $title = str_replace('-', ' ', $title);
            $title = str_replace('_', ' ', $title);  //what are you
            $title = str_replace('_', ' ', $title);  //doing here
            $title = str_replace('stream', 'media - ', $title);
            $title = str_replace('pdf', 'PDF', $title);
            $title = ucwords($title);
            if($next_status === 12){
                $title .= " (Physical)";
            }
            return $title;
        }

        private function checkExists($form_type, $forced_manual, $data){
            $unique = $this->getInitJSON("submit_unique");
            $skipck = $this->getInitJSON("submit_field_value_skipcheck");

            $check = true;

            if($unique[$form_type] === 1){
                $check = false;
            }
            if($form_type == 'electronic_article' && $forced_manual){
                $check = false;
            }
            if ($forced_manual) {//overrides above though
                $check = false;
            }

            if($data == null) {
                $check = false;
            } else {
                foreach ($data as $k => $v) {
                    if (isset($skipck[$k]) && $skipck[$k] === $v) {
                        $check = false;
                    }
                }
            }

            if($check){error_log("will check for item");} else {error_log("will not check for item");}
            return $check;
        }

        function getForm($form_type, $forced_manual = false, $data = null ){
            if($forced_manual){
                error_log("this is forced");
            } else {
                error_log("unforced entry");
            }
            /*
             * $forced_manual - used to check if the user was forced to manually enter item
             *                  needed, for example, if you started with book_chapter, but
             *                  then could not find the item in summon and so you made the item
             *                  manually. this is because this will be a docstore item, but we
             *                  need a check to ensure we don't show the upload field, as this
             *                  wasn't starting off as a file upload workflow and the instructor
             *                  may or may not have the pdf
             */

            //MC::flush();

            //$types        = $this->getInitJSON("submit_types");
            $types        = json_decode('{
                "pdf_general": {
                    "item_title":     1,
                    "item_author":    1,
                    "item_publisher": 1,
                    "item_pubplace":  1,
                    "item_pubdate":   1,
                    "item_incpages":  1,
                    "item_isxn":      1
                },
                "pdf_article": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_incpages": 1,
                    "journal_title": 1,
                    "journal_volume": 0,
                    "journal_issue": 0,
                    "journal_month": 0,
                    "journal_year": 0,
                    "item_isxn": 0,
                    "item_doi": 0
                },
                "pdf_chapter": {
                    "collection_title": 1,
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 1,
                    "item_edition": 0,
                    "item_incpages": 1,
                    "item_isxn": 0,
                    "item_callnumber": 0
                },
                "pdf_other": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 1,
                    "item_incpages": 1,
                    "item_isxn": 0
                },
            
                "book_general": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_isxn": 0,
                    "item_callnumber": 0
                },
                "book_chapter": {
                    "collection_title": 1,
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_incpages": 1,
                    "item_isxn": 0,
                    "item_callnumber": 0
                },
                "ebook_general": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_isxn": 0,
                    "item_uri": 0
                },
                "ebook_chapter": {
                    "collection_title": 1,
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_incpages": 1,
                    "item_isxn": 0,
                    "item_callnumber": 0,
                    "item_uri": 0
                },
            
                "web_general": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_uri": 1,
                    "item_pubdate": 0
                },
            
                "electronic_article": {
                    "journal_title": 1,
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_incpages": 1,
                    "journal_volume": 0,
                    "journal_issue": 0,
                    "journal_month": 0,
                    "journal_year": 0,
                    "item_isxn": 0,
                    "item_doi": 0,
                    "item_uri": 0
                },
            
                "stream_general": {
                    "item_title": 1,
                    "item_author": 0,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_uri": 0
                },
                "stream_video": {
                    "item_title": 1,
                    "item_author": 0,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_uri": 0
                },
                "stream_music": {
                    "item_title": 1,
                    "item_author": 0,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_uri": 0
                },
                "stream_online": {
                    "item_title": 1,
                    "item_author": 0,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_uri": 0
                },
            
                "physical_general": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0
                },
                
                "physical_unknown_type": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_incpages": 0,
                    "item_isxn": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_callnumber": 0
                },
            
                "undetermined": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_incpages": 0,
                    "journal_title": 0,
                    "journal_volume": 0,
                    "journal_issue": 0,
                    "journal_month": 0,
                    "journal_year": 0,
                    "item_isxn": 0,
                    "item_doi": 0,
                    "item_edition": 0,
                    "item_editor": 0,
                    "item_callnumber": 0,
                    "item_uri": 0
                },
                
                "request_general": {
                    "item_title": 1,
                    "item_author": 1,
                    "item_publisher": 0,
                    "item_pubplace": 0,
                    "item_pubdate": 0,
                    "item_edition": 0
                }
                
            }',true);
            
            //$defaults     = $this->getInitJSON("submit_defaults");
            
            $defaults     = json_decode('{
    "collection_title": {
        "display_name": "Book Title",
        "machine_name": "collection_title",
        "placeholder": "Full Collection/Unit/Work/Book Title",
        "tooltip": "Please do not abbreviate unless if your citation is abbreviated"
    },
    "item_title": {
        "display_name": "Title",
        "machine_name": "item_title",
        "placeholder": "Full Title: e.g. Oscar Wilde: An Illustrated Anthology",
        "tooltip": "Please do not abbreviate unless if your citation is abbreviated"
    },
    "item_author": {
        "display_name": "Author(s)",
        "machine_name": "item_author",
        "placeholder": "Author: e.g. Wilde, Oscar; Jabbari, Nasrollah; Smith, Leili",
        "tooltip": "Last Name, First Name; Last Name, First Name..."
    },
    "journal_title": {
        "display_name": "Journal Title",
        "machine_name": "journal_title",
        "placeholder": "Journal Title: e.g. Journal of Economic Literature",
        "tooltip": "Please do not abbreviate unless if your citation is abbreviated"
    },
    "journal_volume": {
        "display_name": "Journal Volume",
        "machine_name": "journal_volume",
        "placeholder": "Journal Volume: e.g. 44",
        "tooltip": "Please enter the journal volume number e . g 12"
    },
    "journal_issue": {
        "display_name": "Journal Issue",
        "machine_name": "journal_issue",
        "placeholder": "Journal Issue: e.g. 4",
        "tooltip": "Please enter the journal issue number e.g 4"
    },
    "journal_month": {
        "display_name": "Journal Month",
        "machine_name": "journal_month",
        "placeholder": "Month: e.g. 02",
        "tooltip": "Month e.g. 02"
    },
    "journal_year": {
        "display_name": "Journal Year",
        "machine_name": "journal_year",
        "placeholder": "Year: e.g. 2012",
        "tooltip": "Year e.g 2012"
    },
    "item_incpages": {
        "display_name": "Page(s)",
        "machine_name": "item_incpages",
        "placeholder": "Pages: e.g. 12-15",
        "tooltip": "e.g. 12-15"
    },
    "item_isxn": {
        "display_name": "ISXN",
        "machine_name": "item_isxn",
        "placeholder": "ISXN: e.g 1949-4998",
        "tooltip": "Please enter an (E)ISXN if available, e.g 1949-4998"
    },
    "item_doi": {
        "display_name": "DOI",
        "machine_name": "item_doi",
        "placeholder": "DOI: e.g 10.2165.123",
        "tooltip": "Please enter the DOI this article can use to be resolved e.g 10.2165.123"
    },
    "item_edition": {
        "display_name": "Edition",
        "machine_name": "item_edition",
        "placeholder": "Edition: e.g. i, II, first ed.",
        "tooltip": "Please enter the edition of the item in question, if available"
    },
    "item_editor": {
        "display_name": "Editor",
        "machine_name": "item_editor",
        "placeholder": "Editor: e.g. Goodman, Clive",
        "tooltip": "Please enter the editor\'s information, if available"
    },
    "item_publisher": {
        "display_name": "Publisher",
        "machine_name": "item_publisher",
        "placeholder": "Publisher: e.g. Prentice Hall",
        "tooltip": "Prentice Hall"
    },
    "item_pubplace": {
        "display_name": "Publication Place",
        "machine_name": "item_pubplace",
        "placeholder": "Place of Publication: e.g . New York",
        "tooltip": "Place of Publication e . g . New York"
    },
    "item_pubdate": {
        "display_name": "Publication Date",
        "machine_name": "item_pubdate",
        "placeholder": "Date(yyyy) of Publication: e.g. 1990",
        "tooltip": "Date(yyyy) of Publication"
    },
    "item_callnumber": {
        "display_name": "Call Number",
        "machine_name": "item_callnumber",
        "placeholder": "Call Number: e.g. QT 6.235.12.2012",
        "tooltip": "Call Number"
    },
    "item_uri": {
        "display_name": "URI",
        "machine_name": "item_uri",
        "placeholder": "URI e.g. http://www.youtube.com/jbrary",
        "tooltip": "Please enter the full URL to the resource, if available, e.g. http://www.youtube.com/jbrary"
    }
    }', true);
            //$overrides    = $this->getInitJSON("submit_overrides");
            $overrides    = json_decode('{
    "pdf_general": {

    },
    "pdf_article": {
        "item_title": "Article Title",
        "item_isxn": "ISSN"
    },
    "pdf_chapter": {
        "collection_title": "Title",
        "item_title": "Chapter / Section Title"
    },

    "pdf_other": {

    },

    "book_general": {
        "item_isxn": "ISBN",
        "notice": "<p><strong>Please note that this form is for requests to place books on physical reserves. If you wish to have the library create a PDF, please use the Book Chapter/Excerpt request.</strong></p>"
    },

    "book_chapter": {
        "collection_title": "Title",
        "item_title": "Chapter / Section Title",
        "item_isxn": "ISBN",
        "notice": "<p><strong>Please note that the library will investigate creating PDFs of requested materials if possible. If you already have a PDF, please consider using the PDF Upload feature.</strong></p>"
    },

    "ebook_general": {
        "item_isxn": "ISBN"
    },

    "ebook_chapter": {
        "collection_title": "Title",
        "item_title": "Chapter / Section Title",
        "item_isxn": "ISBN",
        "notice": "<p><strong>Please note that the library will investigate creating PDFs of requested materials if possible. If you already have a PDF, please consider using the PDF Upload feature.</strong></p>"
    },

    "web_general": {
        "item_author": "Author / Organization"
    },

    "electronic_article": {
        "item_title": "Article Title",
        "item_isxn": "ISSN",
        "item_uri": "Article URL"
    },

    "stream_general": {

    },

    "stream_video": {
        "item_author": "Director(s)",
        "item_publisher": "Distributor",
        "item_editor": "Producer(s)",
        "item_pubdate": "Year"
    },

    "stream_music": {
        "item_author": "Performer(s)",
        "item_publisher": "Distributor",
        "item_editor": "Composer(s)",
        "item_pubdate": "Year"
    },
    "physical_general": {
        "item_title": "Item / Object Name"
    },
    "physical_unknown_type": {
        "item_title": "Object Name"
    },
    "undetermined": {
        "item_title": "Object Name / Label"
    }
}', true);
            //$addons       = $this->getInitJSON("submit_addons");
            $addons       = json_decode('{
    "file": {
        "display": 0,
        "display_name": "File Upload",
        "machine_name": "uploadfile",
        "placeholder": "Select file to upload..."
    },
    "notes": {
        "display": 1,
        "display_name": "Note to Students",
        "machine_name": "note_student",
        "placeholder": "Please complete this reading before midterm exams.",
        "tooltip": "If you would like to send a note to students e.g. \'Please read before Week 5\', you can enter it here"
    },
    "staffnotes": {
        "display": 1,
        "display_name": "Processing Notes",
        "machine_name": "note_staff",
        "placeholder": "please insert any processing notes for staff here",
        "tooltip": "Put any information here that may help us find the item as well as an other pertinent information."
    },
    "summon": {
        "display": 0,
        "machine_name": "summon"
    },
    "tags": {
        "display": 1,
        "display_name": "Tags",
        "machine_name": "tags",
        "placeholder": "semicolon; separated; tags",
        "tooltip": "Enter a semicolon-separated list of tags to be assigned to this item"
    }
}', true);
            //$keyups       = $this->getInitJSON("submit_keyups");
            $keyups       = json_decode('{
    "collection_title": "",
    "item_title": "validateItemTitle(this)",
    "item_author": "",
    "journal_title": "",
    "journal_volume": "",
    "journal_issue": "",
    "journal_month": "",
    "journal_year": "",
    "item_incpages": "",
    "item_isxn": "",
    "item_doi": "",
    "item_edition": "",
    "item_editor": "",
    "item_publisher": "",
    "item_pubplace": "",
    "item_pubdate": "",
    "item_callnumber": "",
    "item_uri": "validateUrl(this)"
}', true);
            //$format_map   = $this->getInitJSON("physical_format_map");
            $format_map   = json_decode('{"pdf_general": 1, "pdf_article": 1, "pdf_chapter": 1, "pdf_other": 1,"book_general": 2,"book_chapter": 2,"ebook_general": 5,"ebook_chapter": 5, "web_general": 3,    "electronic_article": 4, "stream_general": 6,"stream_video": 6,"stream_music": 6, "physical_general": 2, "request_general": 2, "physical_unknown_type": 7, "undetermined": 7}', true);

            $force_physical_typeid = false;
            if(strpos($form_type,'__')> -1){
                $parts = explode('__',$form_type);
                $form_type = $parts[0];
                if($parts[1] === 'switch_type_physical'){
                    $force_physical_typeid = true;
                }
            }

            if($this->checkExists($form_type, $forced_manual, $data)){
                if ($itemid = $this->itemExists($data)) {
                    $isNew = false;
                } else {
                    $isNew = true;
                }
            } else {
                $isNew = true;
                $itemid = false;//can be anything, will not be used as this is a new item request
            }

            if (!isset($data) || $data === null) {
                $submit_object = array(
                    'abstract'          => "",
                    'availability_id'   => "",
                    'collection_title'  => "",
                    'form_type'         => "",
                    'form_type_display' => "",
                    'initial_uri'       => "",
                    'item_author'       => "",
                    'item_callnumber'   => "",
                    'item_doi'          => "",
                    'item_edition'      => "",
                    'item_editor'       => "",
                    'item_incpages'     => "",
                    'item_isxn'         => "",
                    'item_pubdate'      => "",
                    'item_publisher'    => "",
                    'item_pubplace'     => "",
                    'item_title'        => "",
                    'item_uri'          => "",
                    'journal_issue'     => "",
                    'journal_month'     => "",
                    'journal_title'     => "",
                    'journal_volume'    => "",
                    'journal_year'      => "",
                    'subject_terms'     => "",
                    'summon'            => "This item was not from summon."
                );
            } else {
                $submit_object = $data;
            }

            $system_flags = array();
            $area_notices = '';
            $area_notices .= isset($overrides[$form_type]['notice']) ? $overrides[$form_type]['notice'] : '';

            $form = '<div class="content"><div class="row-fluid">';

            $left  = '<div class="span8">';
            $right = '<div class="span4">';

            $area_required  = '<fieldset><legend><i class="fa fa-chevron-right lsit-caret" style="display: none;"></i><i class="fa fa-chevron-down lsit-caret"></i>&nbsp;&nbsp;Required Information</legend><div class="fieldset-content">';
            $area_optional  = '<fieldset><legend><i class="fa fa-chevron-right lsit-caret"></i><i class="fa fa-chevron-down lsit-caret" style="display: none;"></i>&nbsp;&nbsp;Optional Information</legend><div class="fieldset-content" style="display: none">';
            $area_uris      = '<fieldset><legend><i class="fa fa-chevron-right lsit-caret"></i><i class="fa fa-chevron-down lsit-caret" style="display: none;"></i>&nbsp;&nbsp;Access URI</legend><div class="fieldset-content" style="display: none">';
            $area_dates     = '<fieldset><legend><i class="fa fa-chevron-right lsit-caret" style="display: none;"></i><i class="fa fa-chevron-down lsit-caret"></i>&nbsp;&nbsp;Request Options</legend><div class="fieldset-content">';
            $area_addons    = '<fieldset><legend><i class="fa fa-chevron-right lsit-caret"></i><i class="fa fa-chevron-down lsit-caret" style="display: none;"></i>&nbsp;&nbsp;Notes &amp; Tags</legend><div class="fieldset-content" style="display: none">';

            //$required = $data_req = '';

            foreach ($types[$form_type] as $k => &$v) {
                $isRequired = ($types[$form_type][$k] === 1);
                $data_req = $isRequired ? 'true' : 'false';

                $k = $k === 'item_uri' ? 'initial_uri' : $k;

                $res = $this->getFieldWarning($k, (isset($data)) ? preg_replace('/\\x22/', '\'', $data[$k]) : "", $system_flags);
                $area_notices .= $res['notice'];
                $system_flags  = (isset($res['flags']) && $res['flags'] !== null) ? $res['flags'] :  $system_flags;
                $val = (isset($data)) ? preg_replace('/\\x22/','\'',$data[$k]) : "";

                $k = $k === 'initial_uri' ? 'item_uri' : $k;


                if ($k === 'item_uri'){
                    if($this->enableURI($form_type, $forced_manual)) {
                        $disabled = '';
                    } else {
                        $disabled = ' disabled = "disabled"';
                    }
                } elseif (!$isNew) {
                    $disabled = ' readonly';
                } else {
                    $disabled = '';
                }

                $field = '
                    <div class="row-fluid">
                        <div class="span12">
                            <label>' .
	                            (isset($overrides[$form_type][$k]) ? $overrides[$form_type][$k] : $defaults[$k]["display_name"]) .
	                            ($k === 'item_uri'?'<a href="javascript: false;" id="visit-item-url">&nbsp;&nbsp;Visit&nbsp;<i class="fa fa-external-link-square"></i></a>':'').
	                        '</label>
                            <input class="stored span12" type="text" id="ubc_id_' . $defaults[$k]["machine_name"] . '" name="' . $defaults[$k]["machine_name"] . '" value="' . $val . '" placeholder="' . (isset($defaults[$k]["placeholder"]) ? $defaults[$k]["placeholder"] : "") . '" title="' . (isset($defaults[$k]["tooltip"]) ? $defaults[$k]["tooltip"] : "") . '" onkeyup="' . $keyups[$k] . '" data-required="' . $data_req . '"'. $disabled .'>
                        </div>
                    </div>
                ';

                if($isRequired && $k !== 'item_uri'){
                    $area_required .= $field;
                } else if (!$isRequired && $k !== 'item_uri') {
                    $area_optional .= $field;
                } else if($k === 'item_uri') {
                    $area_uris .= $field;
                    $area_required .= $field;
                    //$area_uris .= '<a target="_blank" href="" id="visit-item-url">Visit <i class="fa fa-external-link"></i></a></span><input class="span10 stored" type="text" id="ubc_id_' + this . defaults[key] . machine_name + '" name="' + this . defaults[key] . machine_name + '" value="' + (this . submit_object . initial_uri || "") . toString() . replace(/\\x22 / g, '\'') +'" placeholder="' + (this . defaults[key] . placeholder || "") +'" title="' + (this . defaults[key] . tooltip || "") +'" onkeyup="' + this . keyups[key] + '" data-required="' + data_req + '"></div></div></div>';
                }
            }

            if (!$isNew) {
                $area_notices .= '<strong>This item only requires request specific information. Metadata fields are readonly</strong>';
            }

            //request and/or course related stuff e.g. notes etc.
            // 1- is there a file that needs to be uploaded
            if(in_array($form_type, array(
                'pdf_general',
                'pdf_article',
                'pdf_chapter',
                'pdf_other'
            ))){
                $area_required .= '
                <div class="row-fluid">
                    <div class="span12">
                        <label>PDF to Upload</label>
                        <iframe name="upload_file_docstore" id="upload_file_docstore" width="100%" height="30" frameborder="0" border="0" style="frameborder: 0; border: 0; overflow: hidden">
                            <html>
                                <body style="margin: 0; padding: 0;">
                                </body>
                            </html>
                        </iframe>
                    </div>
                </div>';
            }


            //this . html = this . html . concat('<div id="request-related-inputs">');
            // 2- start and end dates of request
            $area_dates .= '<div class="row-fluid">
                                <label class="">Item Available From</label>
                                <div class="date-span">
                                    <input class="stored date-span-input" type="text" id="ubc_id_start_date" name="start_date" value="">
                                </div>
                                <br><span class="date-span-input-span"><em>Cannot be earlier than the start of the course</em></span>
                            </div>';

            $area_dates .= '<div class="row-fluid">
                                <label class="">Item Available Until</label>
                                <div class="date-span">
                                    <input class="stored date-span-input" type="text" id="ubc_id_end_date" name="end_date" value="">
                                </div>
                                <br><span class="date-span-input-span"><em>Cannot be later than the end of the course</em></span>
                            </div>';

            // 3- if is a required reading
            $area_dates .= '
            <div class="row-fluid">
                <label class="">Required Reading</label>
                <div class="span12">
                    <!-- <div class="btn-group required-buttons-group" id="flag-required-reading" style="width: 100%">
                        <button type="button" class="btn btn-inverse span2 required-buttons" value="1">Yes</button>
                        <button type="button" class="btn btn-success selected span2 required-buttons" value="0">No</button>
                    </div>-->
                    <label class="radio">
                        <input type="radio" name="flag-required-reading" id="required-reading-yes" value="1">
                            Yes
                    </label>
                    <label class="radio">
                        <input type="radio" name="flag-required-reading" id="required-reading-no"  value="0" checked>
                            No
                    </label>
                </div>
            </div>';

            foreach ($addons as $k => &$v) {
                if ($v['display'] === 1) {
                    $area_addons .= '
                        <div class="row-fluid">
                            <div class="span12">
                                <label class="">' . $v['display_name'] . '</label>
                                <input class="span12 stored" type="text" id="ubc_id_' . $v['machine_name'].  '" name="' . $v['machine_name'] . '" value="" placeholder="' . (isset($v['placeholder']) ? $v['placeholder'] : "") . '" title="' . (isset($v['tooltip']) ? $v['tooltip'] : "") . '">
                            </div>
                    ';
                    if($v['display_name']=='Tags'){
                      $area_addons.='<div id="tag_suggestions_ubc_id_tags"></div>';
                    }
                    $area_addons.='
                        </div>';
                }
            }

            //rules to change format and type
            $type_id = $format_map[$form_type];
            $next_status = -1;
            if(isset($system_flags['hbr']) && $system_flags['hbr']){
                $form_type  = 'pdf_article';
                $type_id    = $format_map[$form_type];
            }
            if($force_physical_typeid) {
                $type_id    = $format_map['physical_general'];
                $next_status = 12;
            }
            if($form_type === 'book_chapter'){
                $form_type =  'pdf_chapter';
                $type_id = $format_map[$form_type];
            }
            if(in_array($form_type,array('stream_general', 'stream_video', 'stream_music')) && (isset($data['item_callnumber']) && $data['item_callnumber'] !== "")){
                $force_physical_typeid = true;
                $next_status = 12;
            }
            //error_log(serialize($data));
            error_log('[BLOB]');

            if(($form_type == 'electronic_article' || $form_type == 'stream_general') && $forced_manual){
                $next_status = 12;
            }

            if($form_type == 'request_general') {
                $area_required .= '
                     <div class="row-fluid">
                            <div class="span12">
                                <label class="">Format</label>
                                <select name="format" id="purchase_request_format">
                                    <option value="ebook_general">eBook</option>
                                    <option value="book_general">Book (physical)</option>
                                    <option value="stream_general">Audio/Visual Resource</option>
                                    <option value="electronic_article">Article</option>
                                    <option value="book_chapter">Chapter/Excerpt</option>
                                </select>
                            </div>
                     </div>
                ';
            }

            $_utility = getModel('utility');

            $licrloanperiods = $_utility->getList(md5('ListLoanPeriods'), 'ListLoanPeriods');

            $options = '';
            foreach ($licrloanperiods as $k => $v) {
                if ($this->hasLoanPeriod($form_type) || $force_physical_typeid) {
                    if (in_array($form_type, array('stream_general', 'stream_video', 'stream_music'))){
                        if($force_physical_typeid){
                            if ($v !== 'N/A') {
                                $options .= "<option value=\"$k\">$v</option>";
                            }
                        }
                    } else {
                        if ($v !== 'N/A') {
                            $options .= "<option value=\"$k\">$v</option>";
                        }
                    }
                } else {
                    if ($v == 'N/A') {
                        $options .= "<option value=\"$k\" selected>$v</option>";
                    }
                }
            }

            $area_dates .= '
                <div class="row-fluid">
                    <div class="span12">
                        <label class="">Loan Period</label>
                        <select class="span4 stored" id="ubc_id_loan_period" name="loan_period">
                        ' . $options . '
                        </select>
                    </div>
                </div>';



            $area_addons .= '
                <div class="row-fluid" style="visibility: hidden; height: 0px; overflow: hidden;">
                    <div class="span12">
                        <input id="ubc_id_final_submit_typeid" value="' . $type_id . '">
                    </div>
                </div>
                <div class="row-fluid" style="visibility: hidden; height: 0px; overflow: hidden;">
                    <div class="span12">
                        <input id="ubc_id_final_submit_format" value="' . $form_type . '">
                    </div>
                </div>
                <div class="row-fluid" style="visibility: hidden; height: 0px; overflow: hidden;">
                    <div class="span12">
                        <input id="ubc_id_final_status" value="' . $next_status . '">
                    </div>
                </div>';

            $area_required .= '</div></fieldset>';
            $area_optional .= '</div></fieldset>';
            //$area_uris     .= '</div></fieldset>';
            $area_dates    .= '</div></fieldset>';
            $area_addons   .= '</div></fieldset>';

            $left  .= $area_required . $area_optional . $area_addons . '</div>';
            $right .= $area_dates . '</div>';

            //$form .= $area_required . $area_optional . $area_uris . $area_dates . $area_addons;
            $form .= $left . $right . '</div><!-- ./row-fluid --></div><!-- ./content -->';

            if(!empty($area_notices)) {
                $content = "<div class='alert alert-info' style='width: 93%; margin-bottom: 5px;'> " . $area_notices . "</div>" . $form;
            } else {
                $content=  $form;
            }
            return json_encode(array(
                '_new'   => $isNew,
                'data'  => array(
                    'notices'       => $area_notices,
                    'form'          => $content,
                    'flags'         => $system_flags,
                    'itemid'        => $itemid,
                    'title'         => $this->getTitle($form_type, $next_status),
                    'submit_object' => $submit_object
                )
            ));
        }

    }
