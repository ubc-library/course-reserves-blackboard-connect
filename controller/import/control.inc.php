<?php class Controller_import{
    public function files(){

        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $from   = gv('from');
        $to     = gv('to');


        /* Le Condtional Stuff */
        $licr=getModel('licr');

        $results = $licr->getArray('CopyCourse', array(
            'from'      => $from
            ,'to'       => $to
        ));

        /* Le Processing Stuff */
        if(isset($results) && count($results)){
            $template_vars['json'] = json_encode($results);
        }
        else {
            $template_vars['json'] = json_encode("No Items Found");
        }

        return $template_vars;
    }

}