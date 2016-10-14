<?php

    class Controller_staticfile
    {
        public function staticfile()
        {
            ob_end_clean();
            ob_start();
            $view = gv('view');
            $resource = gv('res');
            $file = Config::get('approot') . '/view/' . $view . '/' . $resource;
            $sessionline = '';
            if (file_exists($file)) {
                if (preg_match('/\.css$/', $resource)) {
                    header('Content-type: text/css');
                }
                if (preg_match('/\.js$/', $resource)) {
                    header('Content-type: text/javascript');
                    $sessionline = '';
                    if ($_GET) {
                        foreach ($_GET as $k => $v) {
                            if ($k != 'action' && $k != 'view' && $k != 'res') {
                                $sessionline .= "var $k='$v';\n";
                            }
                        }
                    }
                    $sessionline .= "var baseurl='" . Config::get('baseurl') . "'\n";
                }
                header('Content-Disposition: inline;filename="' . $resource . '"');
                header('Content-Length: ' . (filesize($file) + strlen($sessionline)));
                echo $sessionline;
                readfile($file);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo "Resource $resource not found";
            }
            ob_end_flush();
            exit ();
        }
    }
