<?php

require_once (Config::get ('approot') . '/core/db.inc.php');

class Model_docstore
{

    protected $status = false;
    protected $message = '';
    protected $docstoredirectory = '';

    const ERROR_ITEM_NOT_FOUND = -1;

    public function __construct ()
    {
        // find the config folder or default to the dev folder
        $this->docstoredirectory = (Config::get ('docstore_docs') ? Config::get ('docstore_docs') : '/usr/local/dev/docstore-docs/');
    }


    //adds a new file. cannot have existing hash
    function addFile ($file, $puid, $itemid)
    {
        $this->status = false;

        // 1 - check that the system is up
        //TODO - should move to constructor but need global listener to know to display fail event sent by model
        $isWriteable = $this->verifyDocstoreWriteable ($this->docstoredirectory);
        if (!$isWriteable) {
            Reportinator::alertDevelopers ('DcoStore System is not Writeable', 'The system failed to be able to write to the docstore file save path');

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        // 2 - check to see we have a legit file
        $isFile = $this->verifyFileUpload ($file, $itemid);
        if (!$isFile) {
            Reportinator::alertDevelopers ('Docstore File Verification Failed', 'A ticket will be coming in shortly with the details');

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        // 3 - establish existence
        $filename = $this->getFilenameByItemId ($itemid);
        $isAdd = ($filename === '');

        // 4 - add or replace file
        if ($isAdd) {
            //4 a - add file
            $filename = $file['name'];
            $savefile = $this->createSaveName ($filename);
            $hash = $this->uniqueHash ();

            $isStored = $this->storeFile ($file['tmp_name'], $savefile, $puid, $itemid);

            // 4b - try to write file record to database
            $write = $this->addDocstoreRecord ($itemid, $hash, $savefile);
        } else {
            //4 a - replace file
            $hash = $this->getHashFromId ($itemid);
            $isStored = $this->storeFile ($file['tmp_name'], $filename, $puid, $itemid);
            $write = ['status' => true, 'action' => 'REPLACED DocStore File'];
        }
        if (!$isStored) {
            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        // 5 - log action (this is whether or not writing database record succeeded)
        $this->logHistory ($itemid, $hash, $write['action'], $puid);

        if (!$write['status']) {
            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        /*Reportinator::alertDevelopers(
            'Dev - Licr-DocStore - Connect - Created DocStore Record - '
            , 'A file has been created in DocStore with hash ' . $hash . ' Uploader PUID: ' . $puid
        ); */

        $this->status = true;

        //return the hash to the program that invoked this method
        return ['status' => $this->status, 'data' => $hash, 'isAdd' => $isAdd];
    }

    function createURL ($hash)
    {
        return "https://docstore.library.ubc.ca/download.get/$hash";
    }

    function deleteFilesAlert ()
    {
        //TODO - need to decide if we are pushing pending deletes to the users
        $now = time ();
    }


    function deleteFiles ()
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $now = time ();

        $sql = $dbh->prepare ("
                SELECT p.`item_id`, `hash`, `filename`
                FROM `docstore_licr` d,
                (SELECT `item_id` FROM `docstore_licr_request` WHERE `purge` <= :now) as p
                WHERE d.`item_id` = p.`item_id`;
            "
        );
        $sql->bindValue (':now', $now, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute ();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers ('Could not start automatically deleting files', 'The system was unable to query the list of files to delete');
        }

        $result = "Item\tHash" . str_repeat (' ', 105) . "\tFilename\n";
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $this->deleteCopyrightFile ($row['filename'], false);
            $this->derequestFileById ($row['item_id']);
            $result .= implode ("\t", $row) . "\n\r";
        }
        $dbh = null;
        //Reportinator::alertDevelopers('Expired Items Purged', "The following items were expired and have been deleted from the repository:\n" . $result);
        Reportinator::createTicket ('Expired Items Purged', "The following items were expired and have been deleted from the repository:\n" . $result);

        //while file exists, new cached item could be made, so delete cache after files are deleted
        $this->deleteCache ();
    }

    function deleteCache ()
    {
        $cachedFiles = '';
        $dir = new DirectoryIterator($this->docstoredirectory);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot ()) {
                if (strpos ($fileinfo->getFilename (), '--c.pdf') !== false) {
                    $this->deleteFileFromServer ($fileinfo->getPathname (), false);
                    $cachedFiles .= $fileinfo->getFilename () . "\n";
                }
            }
        }
        Reportinator::alertDevelopers ('Cache Cleared', "The following items were cleared from the cache\n\r" . $cachedFiles);
    }

    function deleteCacheById ($itemid)
    {
        $cachedFiles = '';
        $dir = new DirectoryIterator($this->docstoredirectory);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot ()) {
                if (strpos ($fileinfo->getFilename (), "$itemid--") !== false) {
                    if (strpos ($fileinfo->getFilename (), '--c.pdf') !== false) {
                        $this->deleteFileFromServer ($fileinfo->getPathname (), false);
                        $cachedFiles .= $fileinfo->getFilename () . "\n";
                    }
                }
            }
        }
        Reportinator::alertDevelopers ('Cached Copy Cleared', "The following items were cleared from the cache\n\r" . $cachedFiles);
    }

    function deleteCopyrightFile ($filename, $report = true)
    {
        if ($this->originalFileExists ($filename)) {
            $this->deleteFileFromServer ($this->docstoredirectory . $filename, $report);
            if ($report) {
                Reportinator::alertDevelopers ('Original file deleted as it has expired', 'Applies to file: ' . $filename);
            }
        }
    }

    function deleteCachedFile ($itemid)
    {
        if ($res = $this->getMetadata ($itemid)) {
            $this->deleteCachedFileByMetadata ($res);
        }
    }

    function getField ($field)
    {

    }

    function getCopyrightAddenda ($itemid, $isCoversheet = 0)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("SELECT `addendum` FROM `docstore_licr_copyright_addenda` WHERE `item_id` = :item_id " . ($isCoversheet ? ' AND `coversheet` = 1;' : ' AND `coversheet` = 0;'));
        $sql->bindValue (':item_id', $itemid, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute ();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }

        if (!$this->status) {
            $this->alert ('DocStore::getCopyrightAddendum() - The System was Unable to access Copyright Addenda.', $this->message);
            $dbh = null;

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        $result = '';
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $result = $row['addendum'];
        }
        $dbh = null;

        return ['status' => $this->status, 'data' => $result];
    }

    function getCopyrightDetails ($itemid)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("SELECT `page_count`,`work_count` FROM `docstore_licr_copyright_details` WHERE `item_id` = :item_id;");
        $sql->bindValue (':item_id', $itemid, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute ();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }
        if (!$this->status) {
            $this->alert ('DocStore::getCopyrightDetails() - The System was Unable to access Copyright Details.', $this->message);
            $dbh = null;

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        $result = ['page_count' => -9999, 'work_count' => -9999];
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $result = ['page_count' => $row['page_count'], 'work_count' => $row['work_count']];
        }
        $dbh = null;

        return ['status' => $this->status, 'data' => $result];
    }

    function getCopyrightStatus ($itemid)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("SELECT `copyright_id` as `id` FROM  `docstore_licr` WHERE item_id = ?");
        $bind = [$itemid];

        try {
            $this->status = true;
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }
        $result = '';
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $result = (int)$row['id'];
        }
        if ($result == '') {
            $result = self::ERROR_ITEM_NOT_FOUND;
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert ("The System was Unable to Request the Copyright Statuses from DocStore for ItemID:$itemid.", $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status, 'data' => $result];
    }

    function getCopyrightTypeList ()
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("SELECT `copyright_id` AS `key`, `determination_label` AS `value` FROM `docstore_licr_copyright`;");

        try {
            $this->status = true;
            $sql->execute ();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }
        $result = [];
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $result[$row['key']] = $row['value'];
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert ('The System was Unable to Request a list of Copyright Statuses from DocStore.', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status, 'json' => json_encode ($result)];
    }

    //return all the records for an item. this will be sorted in place chronologically
    //with other history records for the item using the timestamps from ds and licr
    function getHistory ($itemid)
    {
        $dbh = $this->getDB ();
        $stmt_get_dlh = $dbh->prepare ("SELECT * FROM `docstore_licr_history` WHERE `item_id` = ?;");
        $bind = [$itemid];

        try {
            $stmt_get_dlh->execute ($bind);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $this->status = false;
            $dbh = null;
        }
        if ($this->status) {
            $result = $stmt_get_dlh->fetchAll (PDO::FETCH_ASSOC);
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert ('The System was Unable to write a legit metadata record to the database. Investigate', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status, 'data' => $result];
    }

    function getFilenameByItemId ($itemid, $internal = true, $nocover = true)
    {
        $hash = $this->getHash ($itemid);
        if ($hash !== '') {
            return $this->getFilenameByHash ($hash);
        }

        return $hash;
    }

    function getFilenameByHash ($hash, $internal = true, $nocover = true)
    {
        return $this->getFilename ($hash);
    }

    function getHashFromUri ($uri)
    {
        $matches = null;
        preg_match ('/([bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ23456789=_.-]{60}?)/', $uri, $matches);
        if (isset($matches[1])) {
            error_log ("Hash is: " . $matches[1]);

            return $matches[1];
        } else {
            error_log ("Could not get a docstore has from: $uri");

            return false;
        }
    }

    function getHashFromId ($id)
    {
        return $this->getHash ($id);
    }

    function getPDF ($hash)
    {
        $itemid = $this->getId ($hash);

        if ($this->isExpired ($itemid)) {
            error_log("Item: {$itemid} is expired");
            return 'errorpdfs/expired.pdf';
        }
        error_log("Item: {$itemid} is valid, getting pdf");

        $metadata = $this->getMetadata ($itemid); //is $row or false

        if (!$metadata) {
            $this->setMetadataByItemID ($itemid);
            $metadata = $this->getMetadata ($itemid); //is $row
        }

        $pdfName = $this->createPDFName ($metadata);

        error_log("pdfName: {$pdfName}");

        if ($this->pdfExists ($pdfName)) {
            error_log("PDF Already Exists with a Cover Sheet, serving this PDF: {$pdfName}");
            return $pdfName;
        } else {
            
            $filename = $this->getFilenameByHash ($hash);
            $theactualfile = $this->docstoredirectory . $filename;

            # need to prep a decrypted file
            # files are uploaded locked sometimes, and we can't ask profs to do sumn about it because, you know, "customer service"
            $theactualfiledecrypted = $this->docstoredirectory . "_decrypted_" . $filename;
            $theactualfiletemp = $this->docstoredirectory . "_temp_" . $filename;

            if (!file_exists ($theactualfile)) {
                Reportinator::alertDevelopers ('Docstore - PDF Creation Failed - Missing File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but could not find a file in the docs folder with the name: ' . $filename . '. Error Code: 1');
                return 'errorpdfs/e1.pdf';
            }
            $thecoverpdf = $this->docstoredirectory . $this->generateCoversheet ($pdfName, $metadata);
            $thefinalpdf = $this->docstoredirectory . $pdfName;

            ## Decrypt and stuff
            $command = "/usr/bin/qpdf --decrypt $theactualfile $theactualfiledecrypted";
            $logentry = "\r\nCommand: $command \r\nAttempting to decrypt file: $theactualfile\r\n";
            exec ($command, $output);
            foreach ($output as $k => $line) {
                $logentry .= "\r\n" . $line . "\r\n";
            }
            $command = "/usr/bin/qpdf --linearize $theactualfiledecrypted $theactualfiletemp";
            $logentry = "\r\nCommand: $command \r\nAttempting to linearize file: $theactualfile\r\n";
            exec ($command, $output);
            foreach ($output as $k => $line) {
                $logentry .= "\r\n" . $line . "\r\n";
            }
            $command = "/usr/bin/qpdf -empty -pages $theactualfiletemp 1-z -- $theactualfile";
            $logentry = "\r\nCommand: $command \r\nAttempting to remove metadata: $theactualfiletemp\r\n";
            exec ($command, $output);
            foreach ($output as $k => $line) {
                $logentry .= "\r\n" . $line . "\r\n";
            }
            ## END

            $command = "/usr/bin/pdftk $thecoverpdf $theactualfile cat output $thefinalpdf verbose";
            $logentry = "\r\nCommand: $command \r\nAttempting to generate: $thefinalpdf \r\n\r\nSource file: $pdfName\r\n";
            exec ($command, $output);
            foreach ($output as $k => $line) {
                $logentry .= "\r\n" . $line . "\r\n";
            }

            $entry = "\r\n#####################################################################################\r\nTime: " . time () . "\r\n$logentry\r\n#####################################################################################\r\n";
            file_put_contents ($this->docstoredirectory . 'pdftk.log', $entry, FILE_APPEND | LOCK_EX);
            if ($this->pdfExists ($pdfName)) {
                $this->cleanup([$thecoverpdf, $theactualfiledecrypted, $theactualfiletemp]);
                return $pdfName;
            } else {
                error_log ("Could not generate PDF, attempting to fix and then generate");
                /* SKK TRY FIXING XREF */

                ## Decrypt and stuff
                $command = "/usr/bin/qpdf --decrypt $theactualfile $theactualfiledecrypted";
                $logentry = "\r\nCommand: $command \r\nAttempting to decrypt file: $theactualfile\r\n";
                exec ($command, $output);
                foreach ($output as $k => $line) {
                    $logentry .= "\r\n" . $line . "\r\n";
                }
                $command = "/usr/bin/qpdf --linearize $theactualfiledecrypted $theactualfiletemp";
                $logentry = "\r\nCommand: $command \r\nAttempting to linearize file: $theactualfile\r\n";
                exec ($command, $output);
                foreach ($output as $k => $line) {
                    $logentry .= "\r\n" . $line . "\r\n";
                }
                $command = "/usr/bin/qpdf -empty -pages $theactualfiletemp 1-z -- $theactualfile";
                $logentry = "\r\nCommand: $command \r\nAttempting to remove metadata: $theactualfiletemp\r\n";
                exec ($command, $output);
                foreach ($output as $k => $line) {
                    $logentry .= "\r\n" . $line . "\r\n";
                }
                ## END

                $theactualfilefixed = $theactualfile . "_fixed";
                $command = "/usr/bin/pdftk $theactualfile output $theactualfilefixed";
                $logentry = "\r\nCommand: $command \r\nAttempting to generate: $theactualfilefixed \r\n\r\nSource file: $pdfName\r\n";
                exec ($command, $output);
                if (file_exists ($theactualfilefixed)) {
                    $command = "/usr/bin/pdftk $thecoverpdf $theactualfilefixed cat output $thefinalpdf verbose";
                    $logentry .= "\r\nCommand: $command \r\nAttempting to generate: $thefinalpdf \r\n\r\nSource file: $pdfName\r\n";
                    exec ($command, $output);
                    foreach ($output as $k => $line) {
                        $logentry .= "\r\n" . $line . "\r\n";
                    }
                    $entry = "\r\n#####################################################################################\r\nTime: " . time () . "\r\n$logentry\r\n#####################################################################################\r\n";
                    file_put_contents ($this->docstoredirectory . 'pdftk.log', $entry, FILE_APPEND | LOCK_EX);
                    if ($this->pdfExists ($pdfName)) {
                        $this->cleanup([$thecoverpdf, $theactualfiledecrypted, $theactualfiletemp]);
                        return $pdfName;
                    } else {
                        Reportinator::alertCopyright ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');
                        Reportinator::alertDevelopers ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');
                        //Reportinator::createTicket ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');
                        return 'errorpdfs/e2.pdf';
                    }
                } else {
                    error_log ("Could not generate or fix PDF, create ticket");
                    Reportinator::alertCopyright ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');
                    //Reportinator::createTicket ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');
                    Reportinator::alertDevelopers ('Docstore - PDF Creation Failed - Check File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: ' . $filename . 'Error Code: 2');

                    return 'errorpdfs/e2.pdf';
                }
            }
        }
    }

    function requestFile ($courseid, $itemid)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("SELECT COUNT(*) AS count FROM `docstore_licr` WHERE  `item_id` = ?;");
        $bind = [$itemid];

        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }

        if (!$sql->fetchColumn () == 0) {
            $_licr = getModel ('licr');
            $cinfo = $_licr->getArray ('GetCourseInfo', ['course' => $courseid]);
            $stmt_insert_dlr = $dbh->prepare ("REPLACE INTO `docstore_licr_request` (`item_id`,`course_id`,`purge`) VALUES (?,?,?);");
            $bind = [$itemid, $courseid, $this->createEpoch ($cinfo['enddate'])];
            try {
                $stmt_insert_dlr->execute ($bind);
                $this->status = true;
            } catch (PDOException $e) {
                $this->message = $e->getMessage ();
                $this->status = false;
                $dbh = null;
            }
        } else {
            $this->status = false;
            $this->message = "The course $courseid attempted to request the DocStore File #$itemid, but this file is not available";
            Reportinator::alertDevelopers ('DocStore - Could not Request File', "The course $courseid attempted to request the DocStore File stored for Item: $itemid, but not filename/entry has been stored in the database for this item.");
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert ('The System was Unable to Request a legit DocStore Record. Investigate', $this->message);
            Reportinator::alertDevelopers ('DocStore - Could not Request File', "The System was Unable to Request a legit DocStore Record. Investigate\nMessage sent:\n" . $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status];
    }

    function requestFileByItemID ($itemid)
    {
        $this->status = false;

        $_licr = getModel ('licr');
        $cinfo = $_licr->getArray ('GetCoursesByItem', ['item' => $itemid]);

        $failed = [];

        foreach ($cinfo as $k => $v) {
            if (!$this->requestFile ($k, $itemid)['status']) {
                $failed[] = $k;
            }
        }

        if (isset($failed) && count ($failed) > 0) {
            //Reportinator::alertDevelopers ('The System was Unable to Request a legit DocStore Record. Investigate - error 4', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }
        $this->status = true;

        return ['status' => $this->status];
    }

    function resolveInstanceID ($instanceID)
    {
        $licr = getModel ('licr');

        return $licr->getArray ('ResolveInstanceID', ['instance_id' => $instanceID]);
    }

    function setCopyrightAddenda ($itemid, $addendum, $puid, $isCoversheet = 0)
    {
        $dbh = $this->getDB ();
        if ($isCoversheet == 0) {
            $message = "$puid (" . time () . "): " . json_decode ($addendum) . "--break--";
        } else {
            $message = json_decode ($addendum);
        }

        $this->status = false;

        $sql = "
            INSERT INTO `docstore_licr_copyright_addenda` (`item_id`,`addendum`,`coversheet`)
            VALUES (:item_id,:addendum,:coversheet)";
        if ($isCoversheet) {
            $sql .= " ON DUPLICATE KEY UPDATE `addendum` = :addendum , `coversheet` = 1;";
        } else {
            $sql .= " ON DUPLICATE KEY UPDATE `addendum` = CONCAT(`addendum`,:addendum);";
        }

        $stmt_set_dl_copyright = $dbh->prepare ($sql);
        $stmt_set_dl_copyright->bindValue (':addendum', $message, PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue (':item_id', $itemid, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue (':coversheet', $isCoversheet, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute ();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "Could not add addendum for item_id: $itemid";
            $this->alert ('Writing URI to Item Record Failed', $this->message);
            $this->logHistory ($itemid, $this->getHash ($itemid), "Failed to update page details for item_id: $itemid", $puid);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }
        $this->logHistory ($itemid, $this->getHash ($itemid), "Added addendum for item_id: $itemid", $puid);
        $this->deleteCachedFile ($itemid);

        return ['status' => $this->status];
    }

    function setCopyrightDetails ($itemid, $page_count, $work_count, $puid)
    {
        $dbh = $this->getDB ();
        $this->status = false;

        $stmt_set_dl_copyright = $dbh->prepare ("
            INSERT INTO `docstore_licr_copyright_details` (`item_id`,`page_count`,`work_count`)
            VALUES (:item_id, :page_count, :work_count)
            ON DUPLICATE KEY
            UPDATE `page_count` = :page_count, `work_count` = :work_count;"
        );
        $stmt_set_dl_copyright->bindValue (':page_count', $page_count, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue (':work_count', $work_count, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue (':item_id', $itemid, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute ();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $itemid\n\rURL: $itemid\n\rLiCR Error Message: ";
            $this->alert ('Writing URI to Item Record Failed', $this->message);
            $this->logHistory ($itemid, $this->getHash ($itemid), "Failed to update page details for item_id: $itemid", $puid);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }
        $this->logHistory ($itemid, $this->getHash ($itemid), "Updated page details of item_id: $itemid", $puid);
        $this->deleteCachedFile ($itemid);

        return ['status' => $this->status];
    }

    function setCopyrightStatus ($itemid, $copyright_id, $puid)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $stmt_set_dl_copyright = $dbh->prepare ("UPDATE `docstore_licr` SET `copyright_id` = :copyright_id WHERE `item_id` = :item_id;");
        $stmt_set_dl_copyright->bindValue (':copyright_id', $copyright_id, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue (':item_id', $itemid, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute ();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user tried to set a copyright status but it failed. The details are\n\rItemID: $itemid";
            $this->alert ('Setting Copyright Status Failed', $this->message);
            $this->logHistory ($itemid, $this->getHash ($itemid), "Failed to update Copyright to copyright_id: $copyright_id", $puid);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }
        $this->logHistory ($itemid, $this->getHash ($itemid), "Updated Copyright to copyright_id: $copyright_id", $puid);
        $this->deleteCachedFile ($itemid);

        return ['status' => $this->status];
    }

    function setMetadata ($itemid, $courseid = null)
    {
        $_licr = getModel ('licr');
        $iinfo = $_licr->getArray ('GetItemInfo', ['item_id' => $itemid]);
        if (!isset($courseid) || $courseid == '') {
            $courseid = intval (array_pop ($iinfo['course_ids']));
        }
        $cinfo = $_licr->getArray ('GetCourseInfo', ['course' => $courseid]);

        $bibdata = unserialize ($iinfo['bibdata']);

        $this->deleteCacheById ($itemid);

        $dbh = $this->getDB ();
        $stmt_insert_dlm = $dbh->prepare ("
            REPLACE INTO `docstore_licr_metadata` (
                 `item_id`
                ,`item_title`
                ,`item_author`
                ,`item_publisher`
                ,`item_pubdate`
                ,`item_incpages`
                ,`course_title`
                ,`course_code`
                ,`course_term`
                ,`course_dept`
                ,`external_id`
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?);"
        );
        $bind = [$itemid, $iinfo['title'], $iinfo['author'], $bibdata['item_publisher'], $bibdata['item_pubdate'], $bibdata['item_incpages'], $cinfo['title'], (isset($cinfo['coursenumber']) ? $cinfo['coursenumber'] : '' . ' ' . isset($cinfo['section']) ? $cinfo['section'] : ''), $this->getSemester ($cinfo['lmsid']), isset($cinfo['coursecode']) ? $cinfo['coursecode'] : '', $cinfo['lmsid']];

        try {
            $stmt_insert_dlm->execute ($bind);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            error_log ($e->getMessage ());
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert ('The System was Unable to write a legit metadata record to the database. Investigate', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status];
    }


    function setMetadataByItemID ($itemid)
    {
        $this->status = false;
        $_licr = getModel ('licr');
        $cinfo = $_licr->getArray ('GetCoursesByItem', ['item' => $itemid]);
        $failed = [];

        foreach ($cinfo as $k => &$v) {
            if (!$this->setMetadata ($itemid, $k)['status']) {
                $failed[] = $k;
            }
        }
        if (isset($failed) && count ($failed) > 0) {
            $this->alert ('The System was Unable to Request a legit DocStore Record. Investigate', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }
        $this->status = true;

        return ['status' => $this->status];
    }


    function setURL ($url, $item)
    {
        $this->status = false;
        $licr = getModel ('licr');
        $results = $licr->getJSON ('SetItemURI', [
                'item_id' => $item
                , 'uri'   => $url
            ]
        );

        $data = json_decode ($results, true);

        if ($data['success']) {
            if (isset($data['data']) && count ($data['data'])) {
                $this->status = true;
            }
        } else {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $item\n\rURL: $url\n\rLiCR Error Message: " . $data['message'];
            $this->alert ('Writing URI to Item Record Failed', $this->message);

            return ['status' => $this->status, 'json' => json_encode (['status' => $this->status, 'message' => $this->message])];
        }

        return ['status' => $this->status];
    }

    private function isExpired ($item_id)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $now = time ();

        error_log ("finding expiry for item_id = $item_id");
        $sql = $dbh->prepare ("SELECT `purge` FROM `docstore_licr_request` WHERE `item_id` = :item_id;");
        $sql->bindValue (':item_id', $item_id, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute ();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers ('Could not determine if file was expired', 'The system was unable to query the purge date of item: ' . $item_id);
        }
        $expiry_time = '';
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $expiry_time = $row['purge'];
        }
        $dbh = null;

        if ($expiry_time < $now) {
            error_log ("File is expired: items expires at $expiry_time which has passed (is less than $now)");

            return true;
        }

        return false;
    }


    private function alert ($subject, $message)
    {
        $time = time ();
        $subject = "Docstore - Alert - " . $subject . " - $time";
        $message = $message . "\n\rTime: " . time ();

        Reportinator::alertDevelopers ($subject, $message);
    }

    private function errorAlert ($s, $m)
    {
        $this->alert ($s, $m);
    }

    private function addDocstoreRecord ($itemid, $hash, $savefile)
    {
        $dbh = $this->getDB ();
        $sql = $dbh->prepare ("INSERT INTO docstore_licr (`item_id`,`hash`,`filename`) VALUES (?,?,?);");
        $bind = [$itemid, $hash, $savefile];

        try {
            $sql->execute ($bind);
            $success = true;
        } catch (PDOException $e) {
            echo $e->getMessage ();
            $dbh = null;
            $success = false;
        }
        $dbh = null;
        if ($success) {
            return ['status' => true, 'action' => 'CREATED DocStore File'];
        } else {
            Reportinator::alertDevelopers ('DocStore - Could not add to docstore_licr', "The System was unable to create an entry for Item $itemid with the Hash $hash that was to be saved as $savefile. Please ensure the file is on the server and check the database to troubleshoot why.");

            return ['status' => false, 'action' => 'FAILED to CREATE DocStore File'];
        }
    }

    private function cleanup ($files){
        foreach($files as $file){
            if (!unlink ($file)) {
                Reportinator::alertDevelopers ('Could not clean up after pdf creation', 'Could not delete  ' .  $file);
            }
        }
    }

    private function createSaveName ($filename)
    {
        return md5 ($filename . time () . rand (0, 123456789654987654321) . 'this is a salt value to thwart hackers') . '.file';
    }

    private function createPDFName ($metadata)
    {
        return strtolower (preg_replace ('/[^\\w-]|_+/', '', stripslashes ($metadata['item_id'] . '--' . $metadata['item_title'] . '--' . $metadata['course_title'] . '--C')) . '.pdf');
    }

    private function deleteFileFromServer ($path, $report = true)
    {
        if ($this->fileExists ($path)) {
            unlink ($path);
            if ($report) {
                Reportinator::alertDevelopers ('File deleted from server', 'Applies to file: ' . $path);
            }
        }
    }

    //create record adding instance_id to existing hash:file pair.
    //pair will be found by passing
    private function derequestFileById ($itemid)
    {
        $dbh = $this->getDB ();
        $this->status = false;
        $sql = $dbh->prepare ("DELETE FROM `docstore_licr_request` WHERE  `item_id` = ?;");
        $bind = [$itemid];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage ();
            $dbh = null;
            Reportinator::alertDevelopers ('Could not derequest item', 'Could not automatically derequest item: ' . $itemid . ' from the table docstore_licr_request, please do so manually');
        }
        $dbh = null;
    }

    private function deleteCachedFileByMetadata ($metadata)
    {
        $pdfName = $this->createPDFName ($metadata);
        error_log ('Try to unlink:' . $pdfName);
        if ($this->pdfExists ($pdfName)) {
            unlink ($this->docstoredirectory . $pdfName);
            //Reportinator::alertDevelopers('Cached PDF Deleted because of Information Update', 'Applies to file: ' . $pdfName);
        }
    }

    private function fileExists ($serverpath)
    {
        return file_exists ($serverpath);
    }

    private function originalFileExists ($filename)
    {
        return $this->fileExists ($this->docstoredirectory . $filename);
    }

    private function pdfExists ($pdfName)
    {
        return $this->fileExists ($this->docstoredirectory . $pdfName);
    }

    private function getCopyrightCoverInfo ($itemid)
    {
        $dbh = $this->getDB ();
        $sql = $dbh->prepare ("SELECT b.`determination_label`, b.`disclaimer` FROM `docstore_licr` a, `docstore_licr_copyright` b WHERE a.`item_id` = ? AND b.`copyright_id` = a.`copyright_id`;");

        $bind = [$itemid];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $dbh = null;

            return false;
        }
        $row = $sql->fetch (PDO::FETCH_ASSOC);
        $dbh = null;

        return $row;
    }

    private function generateCoversheet ($pdfName, $metadata)
    {

        require_once (Config::get ('approot') . '/core/pdf.inc.php');

        //remove the --c.pdf from the $pdfname and replace with -cover.file
        $pdfName = str_replace ('--c.pdf', '-cover.file', $pdfName);
        $copyright = $this->getCopyrightCoverInfo ($metadata['item_id']);
        $extranote = $this->getCopyrightAddenda ($metadata['item_id'], 1)['data'];

        // create new PDF document
        $pdf = new ubcPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator (PDF_CREATOR);
        $pdf->SetAuthor ('UBC Copyright Office');
        $pdf->SetTitle ('Copyright-Notice');
        $pdf->SetSubject ('Copyright Notice');

        // set header and footer fonts
        $pdf->setHeaderFont ([PDF_FONT_NAME_MAIN, '', 0]);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont (PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins (PDF_MARGIN_LEFT, 23, PDF_MARGIN_RIGHT, true);
        $pdf->SetHeaderMargin (0);
        $pdf->SetFooterMargin (0);

        // remove default footer
        $pdf->setPrintFooter (false);

        // set auto page breaks
        $pdf->SetAutoPageBreak (true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale (PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------

        // set font
        $pdf->SetMargins (PDF_MARGIN_LEFT, 24, PDF_MARGIN_RIGHT, true);
        $pdf->SetFont ('helvetica', '', 10);
        $pdf->setFontSubsetting (false);
        // add a page
        $pdf->AddPage ();
        $html = '<span style="color: rgb(255,255,255); letter-spacing: 8px; text-shadow: rgb(34, 34, 34) 1px 1px 0px;">' . $copyright['determination_label'] . '</span><br>';

        //Collection - Book, Object Etc
        if (isset($metadata['collection_title']) && $metadata['collection_title'] != "") {
            $html .= '<p>Reading: ' . htmlspecialchars ($metadata['item_title']) . '' . '&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars ($metadata['collection_title']) . ')</em></p>';
        } else if (isset($metadata['journal_title']) && $metadata['journal_title'] != "") {
            $html .= '<p>Journal: ' . htmlspecialchars ($metadata['journal_title']) . '</p>';
            if (isset($metadata['item_title'])) {
                $html .= '<p>Article: ' . htmlspecialchars ($metadata['item_title']) . '</p>';
            }
        } else {
            $html .= '<p>Title: ' . htmlspecialchars ($metadata['item_title']) . '</p>';
        }

        if (isset($metadata['item_author'])) {
            $html .= '<p>Author: ' . htmlspecialchars ($metadata['item_author']) . '</p>';
        }
        if (isset($metadata['item_editor']) && $metadata['item_editor'] != "") {
            $html .= '<p>Editor: ' . htmlspecialchars ($metadata['item_editor']) . '</p>';
        }
        if (isset($metadata['item_publisher']) || isset($metadata['item_pubdate']) || isset($metadata['item_incpages'])) {
            $html .= '<p>';
            if (isset($metadata['item_publisher']) && $metadata['item_publisher'] != "") {
                $html .= 'Publisher: ' . htmlspecialchars ($metadata['item_publisher']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if (isset($metadata['item_pubdate']) && $metadata['item_pubdate'] != "") {
                $html .= 'Publication Date: ' . htmlspecialchars ($metadata['item_pubdate']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if (isset($metadata['item_incpages']) && $metadata['item_incpages'] != "") {
                $html .= 'Pages: ' . htmlspecialchars ($metadata['item_incpages']);
            }
            $html .= '</p>';
        }


        if (isset($metadata['course_title']) || $metadata['course_code'] || isset($metadata['course_term']) || isset($metadata['course_dept'])) {
            $html .= '<p>';
            if (isset($metadata['course_title'])) {
                $html .= 'Course: ' . htmlspecialchars ($metadata['course_title']) . '<br>';
            }
            if (isset($metadata['course_code'])) {
                $html .= 'Course Code: ' . htmlspecialchars ($metadata['course_code']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';;
            }
            if (isset($metadata['course_term'])) {
                $html .= 'Term: ' . htmlspecialchars ($metadata['course_term']) . '<br>';
            }
            $html .= '</p>';
            if (isset($metadata['course_dept'])) {
                $html .= '<p>Department: ' . htmlspecialchars ($metadata['course_dept']) . '</p>';
            }

        }
        $html .= $copyright['disclaimer'];

        if (isset($extranote) && $extranote != '') {
            $html .= '<strong>Additional Copyright Information: </strong><br>' . $extranote;
        }

        $pdf->writeHTML ($html, true, false, true, false, 'L');
        //$pdf->writeHTML($html, true, false, true, false, 'L');

        //Close and output PDF document
        $pdf->Output ($this->docstoredirectory . $pdfName, 'F');

        return $pdfName;
    }

    private function getDB ()
    {

        try {
            $dbhandle = new DocsPDO();
        } catch (PDOException $e) {
            error_log ($e->getMessage ());
        }

        return $dbhandle;
        /*
        //TODO - sep out into cfg file
        //prob want to read from a separate config file?
        $dbname = 'docstore';
        $dbuser = 'docstore';
        $dbpass = 'd0k5t0r378ekn000d000d';

        return (new DBFinder($dbuser, $dbpass, $dbname))->getDB();
        */
    }

    //returns a hash from the itemid
    //is using the history table as this is the only table with an id and hash that does not get purged
    private function getHash ($itemid)
    {
        $dbh = $this->getDB ();
        $hash = '';
        $sql = $dbh->prepare ("SELECT DISTINCT `hash` FROM `docstore_licr_history` WHERE  `item_id` = ?;");

        $bind = [$itemid];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $dbh = null;

            return false;
        }
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $hash = $row['hash'];
        }
        $dbh = null;

        return $hash;
    }


    //returns a itemid from the hash
    //is using the history table as this is the only table with an id and hash that does not get purged
    private function getId ($hash)
    {
        error_log ("Get item_id where hash is $hash");
        $dbh = $this->getDB ();
        $id = '';
        $sql = $dbh->prepare ("SELECT DISTINCT `item_id` FROM `docstore_licr` WHERE `hash` = :hash");
        $sql->bindValue (':hash', $hash, PDO::PARAM_STR);
        try {
            $sql->execute ();
        } catch (PDOException $e) {
            error_log ("could not execute. message: " . $e->getMessage ());
            $this->message = $e->getMessage ();
            $dbh = null;

            return false;
        }
        while ($row = $sql->fetch (PDO::FETCH_ASSOC)) {
            $id = $row['item_id'];
            error_log ("Got item_id $id");
        }
        $dbh = null;

        return $id;
    }

    private function getFilename ($hash)
    {
        $dbh = $this->getDB ();
        $filename = '';
        $sql = $dbh->prepare ("SELECT DISTINCT `filename` FROM `docstore_licr` WHERE  `hash` = ?;");

        $bind = [$hash];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            $dbh = null;

            return false;
        }
        while (($row = $sql->fetch (PDO::FETCH_ASSOC)) !== false) {
            $filename = $row['filename'];
        }
        $dbh = null;

        return $filename;
    }

    private function getMetadata ($id)
    {
        $dbh = $this->getDB ();
        $sql = $dbh->prepare ("SELECT `title`, `author`,`bibdata` FROM `licr`.`item` WHERE  `item_id` = ?;");

        $bind = [$id];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            error_log ($e->getMessage ());
            $dbh = null;

            return false;
        }
        if (($row = $sql->fetch (PDO::FETCH_ASSOC)) == false) {
            Reportinator::alertDevelopers ('Tried to get metadata from item that has no metadata. Item:' . $id, 'see subject');
            $dbh = null;
            //$this->setMetadataByItemID($id);
            //$this->getMetadata($id);
            return false;
        }


        $metadataKeys = [
            "availability_id",
            "collection_title",
            "item_author",
            "item_doi",
            "item_edition",
            "item_editor",
            "item_incpages",
            "item_isxn",
            "item_pubdate",
            "item_publisher",
            "item_pubplace",
            "item_title",
            "journal_issue",
            "journal_month",
            "journal_title",
            "journal_volume",
            "journal_year",
            "subject_terms"
        ];

        $tempM = unserialize ($row['bibdata']);
        $metadata = [];
        foreach ($metadataKeys as $key) {
            if (isset($tempM[$key])) {
                $metadata[$key] = $tempM[$key];
            } else {
                $metadata[$key] = "";
            }
        }
        if (empty($metadata['item_title'])) {
            $metadata['item_title'] = $row['title'];
        }
        if (empty($metadata['item_author'])) {
            $metadata['item_author'] = $row['author'];
        }

        $sql = $dbh->prepare ("SELECT * FROM `docstore_licr_metadata` WHERE  `item_id` = ?;");

        $bind = [$id];
        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage ();
            error_log ($e->getMessage ());
            $dbh = null;

            return false;
        }
        if (($row = $sql->fetch (PDO::FETCH_ASSOC)) == false) {
            //Reportinator::alertDevelopers('Tried to get metadata from item that has no metadata','see subject');
            $dbh = null;
            $this->setMetadataByItemID ($id);
            $this->getMetadata ($id);

            return false;
        }
        $dbh = null;
        foreach ($row as $k => $v) {
            if (!isset($metadata[$k])) {
                $metadata[$k] = $row[$k];
            }
        }

        return $metadata;
    }

    private function nonsense ()
    {
        $chars = 'bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ23456789-=_.';
        $chars = str_split ($chars);
        //Note: rand() is inclusive
        $len = count ($chars) - 1;
        $nonsense = '';
        for ($i = 0; $i < 60; $i++) {
            $nonsense .= $chars[rand (0, $len)];
        }

        return $nonsense;
    }

    private function uniqueHash ()
    {
        $dbh = $this->getDB ();
        $unique = false;
        $hash = '';

        $sql = $dbh->prepare ("SELECT COUNT(*) AS count FROM `docstore_licr` WHERE  `hash` = ?;");

        while (!$unique) {
            $hash = $this->nonsense ();
            $bind = [$hash];
            try {
                $sql->execute ($bind);
            } catch (PDOException $e) {
                $this->message = $e->getMessage ();
                $dbh = null;
            }

            if ($sql->fetchColumn () == 0) {
                $unique = true;
            }
        }
        $dbh = null;

        return $hash;
    }

    private function logHistory ($itemid, $hash, $action, $puid)
    {
        $dbh = $this->getDB ();
        $sql = $dbh->prepare ("INSERT INTO docstore_licr_history (`item_id`, `hash`,`action`,`user`) VALUES (?,?,?,?);");
        $bind = [$itemid, $hash, $action, $puid];

        try {
            $sql->execute ($bind);
        } catch (PDOException $e) {
            echo $e->getMessage ();
            $dbh = null;
            $this->errorAlert ('Database Error Thrown', 'The user ' . $puid . ' has tried to upload a DocStore file. Please note the timestamp in the subject line. The action logged was: ' . $action);
        }
        $dbh = null;
    }

    private function storeFile ($file, $savefile, $puid, $itemid)
    {
        $path = $this->docstoredirectory . $savefile;
        if (move_uploaded_file ($file, $path)) {
            Reportinator::alertDevelopers ('Dev - Licr-DocStore - Connect - Uploaded File', "Received PDF:\nItem: $itemid\nWrote File $file as $path.\nUploader PUID: $puid");

            return true;
        } else {
            Reportinator::alertDevelopers ('Dev - Licr-DocStore - Connect - Upload Failed', "PDF Failed:\nItem: $itemid\nCould not write file $file as $path.\nUploader PUID: $puid");

            return false;
        }
    }

    private function verifyDocstoreWriteable ($folder)
    {
        if (!file_exists ($folder)) {
            $this->status = false;
            $this->message = 'Cannot write to the docstore system.';
            $this->errorAlert ("System Not Writable", $this->message);

            return false;
            /* if (!@mkdir($folder, 0777)) { $error = error_get_last(); return false;} */
        }

        return true;
    }

    private function verifyFileUpload ($file, $itemid)
    {

        if (is_uploaded_file ($file['tmp_name'])) {
        } else {
            $this->status = false;
            $this->message = 'Checking uploaded filename failed. Uploaded filename may have been manipulated.';
        }
        if ($file['error']) {
            $this->status = false;
            $this->message = "An error has occured";
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->message = "The uploaded file exceeds the maximum file size allowed (php.ini)";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $this->message = "The uploaded file exceeds the maximum file size allowed for uploads (Form MAX_SIZE field)";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->message = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->message = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->message = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->message = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->message = "File upload stopped by extension";
                    break;
                default:
                    $this->message = "Unknown Error";;
                    break;
            }
            //$this->errorAlert("File Verification Failed",$this->message);
            error_log ("File Verification Failed " . $this->message);
            Reportinator::createTicket ("DocStore Upload Error - Item $itemid", $this->message);

            return false;
        }

        return true;
    }

    private function updateField ($table, $field)
    {

    }

    private function createEpoch ($dateStr)
    {
        try {
            $date = new DateTime($dateStr, new DateTimeZone('America/Vancouver'));
        } catch (Exception $e) {
            $this->errorAlert ('Could not Create Date', "Could not read and create a purge date to insert into DocStore");

            return 0;
        }

        return $date->format ('U');
    }

    private function getSemester ($lmsid)
    {
        preg_match ("/(?<=\.)(20[1-3]{2}[W|S][1-3]{1}(-[1-3]{1})?)(?=\.)/", $lmsid, $output_array);

        return isset($output_array[0]) ? $output_array[0] : '';
    }
}

class DocsPDO extends PDO
{
    private $engine;
    private $host;
    private $database;
    private $user;
    private $pass;

    public function __construct ()
    {
        $dbname = 'docstore';
        $dbuser = 'docstore';
        $dbpass = 'h6hhwuuuaaas';

        $this->engine = 'mysql';
        $this->host = 'muskwa.library.ubc.ca';
        $this->database = $dbname;
        $this->user = $dbuser;
        $this->pass = $dbpass;
        $dns = $this->engine . ':dbname=' . $this->database . ";host=" . $this->host;
        parent::__construct ($dns, $this->user, $this->pass);
    }
}
