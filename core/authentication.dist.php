<?php
// LDAP
    CONST SECRET = 'REPLACE_ME';
    
    function authenticate_ldap()
    {
        if (sv('puid')) {
            return TRUE;
        }
        $user = pv('user');
        $pass = pv('pass');
        if (!$user || !$pass) {
            return FALSE;
        }
        ssv('user', NULL);
        $user = preg_replace('/^STAFF\\\/', '', $user);
        $suser = "STAFF\\$user";
        $ldap = FALSE;
        $ldaphosts = explode(';', Config::get('ldaphosts'));
        $ldaphost = current($ldaphosts);
        do {
            $ldap = @ldap_connect($ldaphost);
        } while (!$ldap && $ldaphost = next($ldaphosts));

        if (!$ldap) {
            ssv('message', 'No LDAP servers available.');
            redirect();
        }
        $bind = @ldap_bind($ldap, $suser, $pass);
        if ($bind) {
            require_once(Config::get('approot') . '/core/idboxapi.inc.php');
            $puid = idboxCall('GetPuid', array(
                'xp' => $user
            ));
            if (!$puid) {
                ssv('message', 'You do not have a PUID registered in the Staff Directory.');
                return FALSE;
            }
            $groups = idboxCall('ListGroups', array(
                'puid' => $puid
            ));
            if ($group = Config::get('idbox_allow_group')) {
                if (!in_array($group, $groups)) {
                    ssv('message', 'You are not authorized to use this site.');
                    return FALSE;
                }
            }
            $userinfo = idboxCall('PersonInfo', array(
                'puid' => $puid
            ));
            ssv('puid', $puid);
            ssv('userinfo', $userinfo);
            ssv('user', $user);
            ssv('groups', $groups);
            ssv('message', 'Welcome, ' . $userinfo ['firstname'] . '!');
            return TRUE;
        }
        ssv('message', 'Incorrect username or password');
        return FALSE;
    }

    function authenticate_shibboleth()
    {
        if (sv('puid')) {
            return TRUE;
        }
        if (gv('token')) {
            $user = gv('user');
            $token = gv('token');
            $instant = gv('token');
            if ($user && $token && $instant) {
                $user = base64_decode($user);
                $instant = base64_decode($instant);
                $token = base64_decode($token);
                $ip = $_SERVER ['REMOTE_ADDR'];
                $hash = md5($user . SECRET . $ip);
                if ($token == $hash) {
                    require_once(Config::get('approot') . '/core/idboxapi.inc.php');
                    $puid = idboxCall('GetPuid', array(
                        'cwl' => $user
                    ));
                    if (!$puid) {
                        ssv('message', 'You do not have a PUID registered in the Staff Directory.');
                        return FALSE;
                    }
                    if ($group = Config::get('idbox_allow_group')) {
                        $groups = idboxCall('ListGroups', array(
                            'puid' => $puid
                        ));
                        if (!in_array($group, $groups)) {
                            ssv('message', 'You are not authorized to use this site.');
                            return FALSE;
                        }
                    }
                    $userinfo = idboxCall('PersonInfo', array(
                        'puid' => $puid
                    ));
                    ssv('puid', $puid);
                    ssv('userinfo', $userinfo);
                    ssv('user', $user);
                    ssv('message', 'Welcome, ' . $userinfo ['firstname'] . '!');
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    function logged_in()
    {
        if (sv('authentication') || Config::get('authentication') !== 'none') {
            return !is_null(sv('user'));
        }
        return FALSE;
    }

    function require_login($type = 'ldap')
    {
        ssv('authentication', $type);
        if (is_null(sv('user'))) {
            ssv('message', 'You must log in to access this feature.');
            redirect();
        }
    }

    function logout()
    {
        ssv('user', NULL);
        redirect();
    }
