<?php
    function _v(&$a, $k, $default)
    {
        if (isset ($a [$k])) {
            return $a [$k];
        }
        return $default;
    }

    function s_v(&$a, $k, $v)
    {
        $a [$k] = $v;
    }

    function gv($k, $default = NULL)
    {
        return _v($_GET, $k, $default);
    }

    function pv($k, $default = NULL)
    {
        return _v($_POST, $k, $default);
    }

    function sv($k, $default = NULL)
    {
        return _v($_SESSION, $k, $default);
    }

    function ssv($k, $v)
    {
        $_SESSION [$k] = $v;
    }

    function redirect($url = NULL)
    {
        if (empty ($url)) {
            $url = Config::get('baseurl');
        }
        if (isset ($_GET ['framed'])) {
            $url .= '?framed=1';
        }
        @ini_set('zlib.output_compression',0);
        @ini_set('implicit_flush',1);
        @ob_end_clean();
        if ($url [0] !== '/') {
            $parsed_url = parse_url($url);
            if (
                (
                    !isset ($parsed_url ['scheme'])
                    || $parsed_url ['scheme'] === 'http'
                    || stripos($parsed_url['host'], 'vimeo') !== FALSE
                )
                && $_SERVER ['HTTPS']
            ) {
                echo '
<!DOCTYPE html>
<html>
<head>
<title>Redirecting</title>
</head>
<body>
<p id="clickme" style="visibility:hidden">Please click <a target="_top" href="' . $url . '">here</a> if you are not automatically redirected.</p>
<script>
    window.top.location="' . $url . '";
    setTimeout(function(){document.getElementById("clickme").style.visibility="visible";},2000);
</script>
</body>
</html>
            ';
                exit ();
            }
        }
        header('Location: ' . $url);
        exit ();
    }

    function redirect_get($get = NULL)
    {
        redirect(buildurl_get($get));
    }

    function buildurl_get($get = NULL)
    {
        if (empty ($get)) {
            $get = &$_GET;
        }
        if (empty ($get ['action'])) {
            return (Config::get('baseurl'));
        }
        $action = $get ['action'];
        unset ($get ['action']);
        $url = rtrim(Config::get('baseurl'), '/') . "/$action";
        foreach ($get as $k => $v) {
            $url .= '/' . urlencode($k) . '/' . urlencode($v);
        }
        return $url;
    }

    /*
     * Use this instead of readfile if you need to pull content off of another server (e.g. CLF parts) and the server this app is on has fopen_wrappers disabled
     */
    function _readfile($url)
    {
        if (!@readfile($url)) {
            $defaults = array(
                CURLOPT_URL            => $url,
                CURLOPT_HEADER         => 0,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_TIMEOUT        => 4
            );

            $ch = curl_init();
            curl_setopt_array($ch, $defaults);
            if (!$result = curl_exec($ch)) {
                trigger_error(curl_error($ch));
                error_log($url);
            }
            curl_close($ch);
            echo $result;
        }
    }

    /*
     * First part of query string is the action; after that are key value pairs, e.g. example.library.ubc.ca/action/key/value/key/value
     */
    function parse_query_string()
    {
        if (isset ($_GET ['q'])) {
            $qs = rtrim($_GET ['q']);
            unset ($_GET ['q']);
            $_GET ['action'] = strtok($qs, '/');
            while ($k = strtok('/')) {
                $v = strtok('/');
                $_GET [$k] = $v;
            }
        }
    }

    function render_normal($action, $template = 'view', $template_data = NULL, $forcetheme = NULL)
    {
        // note, $template may contain directory separator
        if ($template === 'json') {
            echo $template_data ['json'];
        } else {
            if (!$template) {
                $template = 'view';
            }
            if (!is_null($forcetheme)) {
                require(Config::get('approot') . '/view/_theme/' . $forcetheme . '/header_footer.inc.php');
            } else {
                require(Config::get('approot') . '/view/_theme/' . Config::get('theme') . '/header_footer.inc.php');
            }
            $view_dir = Config::get('approot') . '/view/' . $action . '/';
            $includes_dir = Config::get('approot') . '/view/_includes/';
            $view_file = $view_dir . $template . '.twig.html';
            theme_header(1);
            $title = Config::get('app_display_name');
            $subtitle = ucfirst($action);
            if (isset ($template_data ['_titletag'])) {
                $subtitle = $template_data ['_titletag'];
            }
            echo "<title>$subtitle :: $title</title>";
            echo '<meta http-equiv="X-UA-Compatible" content="IE-11,chrome=1">';
            if (file_exists($view_dir . '/style.css')) {
                $mtime=filemtime($view_dir . '/style.css');
                echo '<link rel="stylesheet" href="' . Config::get('baseurl') . '/staticfile/m/'.$mtime.'/view/' . $action . '/res/style.css" />';
            }
            if (file_exists($view_dir . '/script-head.js')) {
                $mtime=filemtime($view_dir . '/script-head.js');
                echo '<script src="' . Config::get('baseurl') . '/staticfile/m/'.$mtime.'/view/' . $action . '/res/script-head.js/sessionid/' . session_id() . '" /></script>';
            }

            theme_header(2);

            if ($message = sv('message')) {
                ssv('message', NULL);
                echo '<div class="alert alert-info">' . $message . '</div>';
            }
            Twig_Autoloader::register();

            if (file_exists($view_file)) {
                $loader = new Twig_Loader_Filesystem (array(
                    $view_dir,
                    $includes_dir
                ));
                $twig = new Twig_Environment ($loader, array(
                    'cache' => FALSE, // Config::get ( 'approot' ) . '/cache',
                    'debug' => TRUE
                ));
                $twig->addExtension(new Twig_Extension_Debug ());
                $template = $twig->loadTemplate($template . '.twig.html');
		if(!is_array($template_data)) $template_data=array();
                $template->display($template_data);
            } else {
                echo '<p class="alert alert-error">Missing view &ldquo;' . htmlspecialchars($view_file) . '&rdquo;.</p>';
                echo '<pre>';
                var_dump($template_data);
                echo '</pre>';
            }
            theme_footer(1);
            if (file_exists($view_dir . '/script-foot.js')) {
                $mtime=filemtime($view_dir . '/script-foot.js');
                echo '<script src="' . Config::get('baseurl') . '/staticfile/m/'.$mtime.'/view/' . $action . '/res/script-foot.js/sessionid/' . session_id() . '" /></script>';
            }
            theme_footer(2);

            // hard and fast session variable of browser (pass = modern, fail <= ie
            $browser = 'pass';
            preg_match('/MSIE (.*?);/', $_SERVER ['HTTP_USER_AGENT'], $matches);
            if (count($matches) > 1) {
                $version = $matches [1];
                $version <= 8 ? $browser = 'fail' : $browser = 'pass';
            }
            ssv('browser', $browser);
        }
    }

    function getController($controller_name)
    {
        if (preg_match('/\.\//', $controller_name)) {
            die ();
        } // prevent directory traversal
        $file = Config::get('approot') . '/controller/' . $controller_name . '/control.inc.php';
        if (file_exists($file)) {
            include_once($file);
            $className = 'Controller_' . $controller_name;
            return new $className ();
        } else {
            return FALSE;
        }
    }

    function getModel($model_name)
    {
        if (preg_match('/\.\//', $model_name)) {
            die ();
        }
        $file = Config::get('approot') . '/model/' . $model_name . '/model.inc.php';
        if (file_exists($file)) {
            include_once($file);
            $className = 'Model_' . $model_name;
            return new $className ();
        } else {
            return FALSE;
        }
    }

    function getFromWiki($url)
    {
        checkCacheExists();

        $hash = 'wiki' . md5($url);
        $cachefile = Config::get('approot') . '/cache/' . $hash;
        if (file_exists($cachefile) && rand(1, 100) != 17) {
            return file_get_contents($cachefile);
        }
        error_log(">> retrieving wiki page $url");
        $t = microtime(TRUE);
        $content = file_get_contents($url);
        // comes in as a blob of javascript
        $content = str_replace('document.write(unescape(decodeURIComponent("', '', $content);
        $content = str_replace('")));', '', $content);
        $content = urldecode($content);
        file_put_contents($cachefile, $content);
        error_log(">> retrieved in " . (microtime(TRUE) - $t) . "s.");
        return $content;
    }

    function checkCacheExists() {
        if(!is_dir(Config::get('approot') . '/cache/')) {
            mkdir(Config::get('approot') . '/cache/');
        }
    }

    class MC
    {
        private static $memcache = FALSE;
        private static $initialized = FALSE;

        public static function set($key, $val, $timeout = NULL)
        {
            if (!self::$memcache) {
                self::_init();
            }
            //error_log("MC::set $key");
            return self::$memcache->set($key, $val, $timeout);
        }

        public static function get($key)
        {
            if (!self::$memcache) {
                self::_init();
            }
            //error_log("MC::get $key");
            return self::$memcache->get($key);
        }

        public static function getDuration($length = '')
        {
            if (Config::get('environment') == 'development' || Config::get('environment') == 'staging' || Config::get('environment') == 'stg') {
                $short = 1;
                $medium = 25;
                $long = 25;
                error_log("Using dev memcache times");
            } else {
                $short = 300;
                $medium = 1800;
                $long = 7200;
            }

            switch ($length) {
                case 'short':
                    return $short;
                break;
                case 'medium':
                    return $medium;
                break;
                case 'long':
                    return $long;
                break;
                default:
                    return $short;
            }
        }

        public static function getResultCode()
        {
            //error_log("MC::getResultCode");
            return self::$memcache->getResultCode();
        }

        public static function flush()
        {
            //error_log("MC::flush $key");
            if (!self::$memcache) {
                self::_init();
            }
            return self::$memcache->flush();
        }

        private static function _init()
        {
            if (self::$memcache) {
                return;
            }
            self::$memcache = new Memcached('CR' . Config::get('env'));
            $sl = self::$memcache->getServerList();
            if (empty ($sl)) {
                self::$memcache->addServer('localhost', 11211);
            }
            //error_log("MC::_init");
        }
    }

    /*
         *  Static class to create alerts
         */

    class Reportinator
    {
        private static $developers = array(
            'stefan.khan-kernahan@ubc.ca'
        );

        private static $jiraEmails = array(
            'locr-support@library.ubc.ca'
        );

        private static $copyrightEmails = array(
            'permissions.office@ubc.ca'
        );

        public static function alertDevelopers($subject, $message)
        {
            $emails = rtrim(implode(',', self::$developers), ',');
            self::sendmail($emails, $subject, $message);
        }

        public static function alertCopyright($subject, $message)
        {
            $emails = rtrim(implode(',', self::$copyrightEmails), ',');
            self::sendmail($emails, $subject, $message);
        }

        public static function createTicket($subject, $message)
        {
            $emails = rtrim(implode(',', self::$jiraEmails), ',');
            self::sendmail($emails, $subject, $message);
        }

        public static function json_log($subject)
        {
            error_log(json_encode($subject));
        }


        private static function sendmail($emails, $subject, $message)
        {
            $headers = 'From: stefan.khan-kernahan@ubc.ca' . "\r\n" . 'Reply-To: stefan.khan-kernahan@ubc.ca' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
            mail($emails, $subject . ' - ' . date('dS m,Y H:i:s', time()), $message, $headers);
        }
    }
