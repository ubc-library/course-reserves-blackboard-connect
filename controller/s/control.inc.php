<?php
/** short links */
class Controller_s{
    public function s(){
        $licr = new Model_licr();
    }
    public function go(){
        $url = $licr->getArray(gv('link'));
        if($url){
            redirect($url);
        }else{
            redirect('blocked.notfound/ft/clean');
        }

    }
}
