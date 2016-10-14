<?php
    define('SUMMON_API_ID', '');
    define('SUMMON_API_KEY', '');
    define('SUMMON_SERVER', '');

    /**
     * @param $value
     * @param bool $types
     * @return array|mixed
     */
    function findInSummonByTitle($value, $types = FALSE)
    {
        if (!$types) {
            $types = array(
                'Book',
                'eBook',
                'Microform'
            );
        }
        return findInSummon(array(
            array(
                'Title',
                $value
            )
        ), $types);
    }
    
    /**
     * @param $value
     * @param bool $types
     * @return array|mixed
     */
    function findInSummonByAuthor($value, $types = FALSE)
    {
        if (!$types) {
            $types = array(
                'Book',
                'eBook',
                'Microform'
            );
        }
        $value = str_replace(' ', '%20',$value);
        #$value = "({$value})";
        
        return findInSummon(array(
                                array(
                                    'AuthorCombined',
                                    $value
                                )
                            ), $types);
    }

    /**
     * @param $value
     * @param bool $types
     * @return array|mixed
     */
    function findInSummonByIsbn($value, $types = FALSE)
    {
        if (!$types) {
            $types = array(
                'Book',
                'eBook',
                'Microform'
            );
        }
        return findInSummon(array(
            array(
                'ISBN',
                $value
            )
        ), $types);
    }

    /**
     * @param $value
     * @param bool $types
     * @return array|mixed
     */
    function findInSummonByCallno($value, $types = FALSE)
    {
        return findInSummon(array(
            array(
                'LCCallNum',
                $value
            )
        ));
    }

    /**
     * @param $value
     * @return array|mixed|string
     */
    function findInSummonByWebcat($value)
    {
        return findInVoyagerByWebcat($value);
        // return array();
    }

    /**
     * @param $idtype
     * @param $value
     * @return array|mixed
     */
    function findInSummonById($idtype, $value)
    {
        // just journals -- doi,pmid
        $field = strtoupper($idtype);
        return findInSummon(array(
//      array (
//          $field,
//          $value
//      ) ,
array('', $value)
        ), array(
            'Journal Article',
            'Newspaper Article'
        ));
    }

    /**
     * @param $journal
     * @param $article
     * @return array|mixed
     */
    function findJAInSummon($journal, $article)
    {
        return findInSummon(array(
            array(
                'PublicationTitle',
                $journal
            ),
            array(
                'Title',
                $article
            )
        ), array(
            'Journal Article',
            'Magazine Article',
            'Newspaper Article',
            'Book Review'
        ));
    }

    /**
     * @param $str
     * @return mixed
     */
    function summonEscape($str)
    {
        // $special='+-&|!(){}[]^"~*?:\\';
        $special = str_split(':');
        foreach ($special as $s) {
            $str = str_replace($s, "\\$s", $str);
        }
        return $str;
    }

    /**
     * @param $queries
     * @param array $contentTypes
     * @param bool $returnRaw
     * @return array|mixed
     */
    function findInSummon($queries, $contentTypes = array(), $returnRaw = TRUE)
    {
        $queryParams = array(
            's.ho=true', // in holdings
            's.hl=false', // no highlight
            's.ps=50'
        );
        foreach ($queries as $q) { // $q=array(field,value)
            if ($q [0]) {
                $queryParams [] = 's.q=' . $q [0] . ':' . summonEscape($q [1]);
            } else {
                $queryParams [] = 's.q=' . summonEscape($q [1]);
            }
            /*
             * if($q[0]){ $queryParams[]='s.q='.$q[0].':'.str_replace(':','\:',$q[1]); }else{ $queryParams[]='s.q='.str_replace(':','\:',$q[1]); }
             */
        }
        foreach ($contentTypes as $ct) {
            $queryParams [] = 's.fvf=ContentType,' . $ct;
        }
        error_log(json_encode($queryParams));
        $res = summonRequest($queryParams);
        if (!$res) {
            return array();
        }
        
        //TODO SKK remove this error log
        error_log(json_encode($res));
        
        $res = json_decode($res, TRUE);
        if (!$res ['documents']) {
            return array();
        }

        return $res;
    }

    /**
     * @param $queryParams
     * @return mixed
     */
    function summonRequest($queryParams)
    {
        /*
         * WARNING: very sensitive code see http://api.summon.serialssolutions.com/help/api/search Note that the sample PHP code given there is terrible and doesn't even work
         */
        sort($queryParams);
        $queryStringAuth = implode('&', $queryParams);
        $queryString = array();
        foreach ($queryParams as $qp) {
            list ($k, $v) = explode('=', $qp);
            $queryString [] = $k . '=' . urlencode($v);
        }
        $queryString = implode('&', $queryString);
        $headers = array(
            'Accept'        => 'application/json',
            'x-summon-date' => gmdate('D, d M Y H:i:s ') . 'GMT',
            'Host'          => 'api.summon.serialssolutions.com'
        );
        $authstring = implode($headers, "\n") . "\n" . "/2.0.0/search\n" . urldecode($queryStringAuth) . "\n";

        $headers ['Authorization'] = 'Summon ' . SUMMON_API_ID . ';' . hmacsha1(SUMMON_API_KEY, $authstring);
        error_log("Authorization: " . $headers ['Authorization']);
        error_log("x-summon-date: " . gmdate('D, d M Y H:i:s ') . 'GMT');
        $curlheaders = array();
        foreach ($headers as $headername => $headerval) {
            $curlheaders [] = "$headername: $headerval";
        }
        $ch = curl_init(SUMMON_SERVER . '/2.0.0/search?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        return $res;
    }

    /**
     * @param $key
     * @param $data
     * @return string
     */
    function hmacsha1($key, $data)
    {
        $blocksize = 64;
        $hashfunc = 'sha1';
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hashfunc ($key));
        }
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack('H*', $hashfunc (($key ^ $opad) . pack('H*', $hashfunc (($key ^ $ipad) . $data))));
        return base64_encode($hmac);
    }

