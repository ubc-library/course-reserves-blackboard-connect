<?php
class Controller_askus{
  function askus(){
    $content=file_get_contents('http://help.library.ubc.ca/ask-colorbox');
    $content=str_replace('http://cdn','https://cdn',$content);
    $content=preg_replace('@<div class="span4".*?</div>.*?</div>@ims','',$content);
    echo $content;
    exit();
  }
}
