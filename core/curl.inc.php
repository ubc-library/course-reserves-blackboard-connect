<?php
function curlIt($url, $options = [])
{
    $defaults = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15
    ];

    $ch = curl_init();
// TODO remove this safety check once it's no longer necessary
// when that is, only time will tell
    foreach($options[CURLOPT_POSTFIELDS] as $v){
        if(is_array($v)){
            die("<div style=\"background-color:pink\">curlIt called with multidimensional array! url: $url post: ".serialize($options[CURLOPT_POSTFIELDS])."</div>");
        }
    }
//end safety check
    curl_setopt_array($ch, ($options + $defaults));

    if (!$result = curl_exec($ch)) {
        error_log(curl_error($ch));
    }

    curl_close($ch);
    return $result;
}

function curlPost($url, $fields)
{
    if ($fields) {
        $response = curlIt($url, [
            CURLOPT_POST => count($fields),
            CURLOPT_POSTFIELDS => $fields
        ]);
    }else{
        $response=curlIt($url);
    }
    return $response;
}
