<?php

    class Controller_download {

        function download ()
        {
            return [];
        }

        function get ()
        {

            $uri = $_SERVER['REQUEST_URI'];
            error_log ("Download URI: " . $uri);
            $_docstore = getModel ('docstore');
            $hash = $_docstore->getHashFromUri ($uri);
            $filename = $_docstore->getPDF ($hash);

            if ($filename === 'errorpdfs/expired.pdf') {
                return [
                    'forcetheme'       => 'default',
                    'controller_error' => '
                    <h2>Access Expired</h2><p>This PDF was made available only for the active time period of your course. It has now been removed in keeping with Canadian copyright.<br> Please consult your course notes or look for the item in the library’s book collections.</p>
                '
                ];
            }

            if (isset ($filename)) {
                $file = Config::get ('docstore_docs') . $filename;

                @ini_set('zlib.output_compression',0);
                @ini_set('implicit_flush',1);
                @ob_end_clean();
                set_time_limit(0);

                if (file_exists ($file)) {
                    header ('Content-Encoding: none;');
                    header ('X-Accel-Buffering: no');
                    header ('Content-Description: DocStore File Access');
                    header ('Content-Type: application/pdf');
                    header ('Content-Disposition: attachment; filename=' . basename ($filename));
                    header ("Cache-Control:  max-age=1, must-revalidate");
                    header ("Pragma: public");
                    ob_end_flush ();
                    flush ();
                    readfile ($file);
                    exit ();
                }
            } else {
                // you should never reach here, as there is an error controller
                // this is here if some random error occurs
                echo "<h1>File Doesn't Exist</h1><br /><p>Please consult with your lecturer/administration and let them know this URL is broken.</p>";
                exit ();
            }
        }
    }
