<?php
class Controller_addreserves
{

    function create() {

    }

    function getForm(){
        $_addreserves = getModel('addreserves');
        $form_type      = pv('form_type'); //good
        $forced_manual  = !!pv('forced_manual'); 
        $data           = pv('data'); //good
        $template_vars = array();
        $template_vars['template'] = 'json';
        $template_vars['json'] = $_addreserves->getForm($form_type, $forced_manual, $data);
        return $template_vars;
    }

    function createItem () {
        /* Le Constant Stuff*/
        $template_vars             = array();
        $template_vars['template'] = 'json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $form            = pv('bibdata'); //good
        $author          = pv('author'); //good
        $title           = pv('title'); //good
        $callnumber      = pv('callnumber'); //good
        $uri             = pv('uri'); //good
        $type            = pv('type');//good
        $physical_format = pv('physical_format');//good


        /* Le Condtional Stuff */
        $licr = getModel('licr'); //add in checks to only load if you got stuff from pv?

        $template_vars['json'] = $licr->getJSON('CreateItem', array(
              'title'           => $title
            , 'callnumber'      => $callnumber
            , 'bibdata'         => serialize($this->fixEncoding($form))
            , 'uri'             => $uri
            , 'type'            => $type
            , 'filelocation'    => $uri
            , 'author'          => $author
            , 'citation'        => ""
            , 'external_store'  => ""
            , 'physical_format' => $physical_format
        ));

        return $template_vars;
    }
    private function fixEncoding($form)
    {
        $ret = @json_encode($form);
        $e   = json_last_error();
        if ($e == JSON_ERROR_UTF8 && isset($form)) {
            if ($e) {
                $form = $this->array_recode('Latin1..UTF8', $form);
                $ret = @json_encode($form);
                $e   = json_last_error();
            }
        }
        if ($e && ($e != JSON_ERROR_UTF8)) {
            error_log('JSON error not utf8 related: ' . $e);
        }
        unset($ret);

        return $form;
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
                $e   = json_last_error();
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