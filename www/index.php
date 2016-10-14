<?php
    $pagetime = microtime(TRUE);
    require('../core/init.php');
    require('../core/curl.inc.php');
    require('../core/db.inc.php');
    require('../core/utility.inc.php');
    require('../core/authentication.php');

    require(__DIR__ . "/../vendor/autoload.php");

    parse_query_string();

    $action = gv('action', 'home');
    $method = FALSE;
    if (strpos($action, '.') !== FALSE) {
        list ($action, $method) = explode('.', $action, 2);
    }
    if ($action == 'staticfile') {
        $static_server = getController('staticfile');
        $static_server->staticfile();
        exit();
    }

// This escapes the evil clutches of BB.
// BB basically proxies the content, so we can't start a session
// because the initial request comes from bbcomm rather than from here
// so we tack on a time-sensitive hash and redirect back here.
// We don't have to do this for instructorhome and studenthome
// because those are accessed either via LTI or from librarytab, both of which
// will have set up the session.
    if (isset($_SERVER['HTTP_REFERER'])) {
        if ($action == 'librarytab' && strpos($_SERVER['HTTP_REFERER'], 'librarytab') && !$method) {
            error_log("1 - Redirecting to: " . Config::get('bburl') . Config::get('lastbit'));
            redirect(Config::get('bburl') . Config::get('lastbit'));
        }
    }
    session_start();
    
    if ($action !== 'staticfile' && !logged_in()) {
        if (Config::get('authentication') || sv('authentication')) {
            if ($action != 'login') {
                ssv('pending_request', $_GET);
                redirect('/login.form');
            }
        }
    }
    if (logged_in() && $g = sv('pending_request')) {
        ssv('pending_request', FALSE);
        redirect_get($g);
    }
    if (!sv('userinfo') && sv('puid')) {
        $licr = getModel('licr');
        $userinfo = $licr->getArray('GetUserInfo', array('puid' => sv('puid')), TRUE);
        ssv('userinfo', $userinfo);
    }
    switch ($action) {
        case 'licr' :
        case 'voyager' :
            $controller = getController($action);
            $params = $_GET;
            unset ($params ['action']);
            $result = $controller->getJSON($method, $params);
            header('Content-type:text/javascript');
            echo $result;
        break;
        default :
            $template_data = array();
            $controller = getController($action);
            if ($controller) {
                $controlfn = $method ? $method : $action;
                if (!method_exists($controller, $controlfn)) {
                    if (method_exists($controller, 'call')) {
                        $params = $_GET;
                        unset ($params ['action']);
                        $template_data = $controller->call($controlfn, $params);
                    } else {
                        die ("No method exists Control_$action::$controlfn");
                    }
                } else {
                    $template_data = $controller->$controlfn ();
                }
            } else {
                error_log("no controller for $action");
            }
            $forcetheme = empty ($template_data ['forcetheme']) ? NULL : $template_data ['forcetheme'];
            if (!empty ($template_data ['controller_error'])) {
                render_normal('error', NULL, $template_data, $forcetheme);
            } else {
                $template = empty ($template_data ['template']) ? NULL : $template_data ['template'];
                render_normal($action, $template, $template_data, $forcetheme);
            }
    }
    $pgt = floor((microtime(TRUE) - $pagetime) * 100) / 100;
    if ($pgt > 2) {
        error_log(sv('puid') . ' page generation time: ' . $pgt . ' s');
    }
