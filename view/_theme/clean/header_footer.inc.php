<?php

require_once(Config::get('approot').'/core/utility.inc.php');

/* If the view has a style.css or script-header.js file, it will be included between parts 1 and 2 */
function theme_header($part=0){
    switch($part){
        case 1:
            // include first part of library CLF header, before close of head tag
            //_readfile('https://clf.library.ubc.ca/7.0.2/library-header-part1.php');
            ?>
                <!DOCTYPE html>

                <!--[if IEMobile 7]>
                <html class="iem7 oldie" lang="en">
                <![endif]-->
                <!--[if (IE 7)&!(IEMobile)]>
                <html class="ie7 oldie" lang="en">
                <![endif]-->
                <!--[if (IE 8)&!(IEMobile)]>
                <html class="ie8 oldie" lang="en">
                <![endif]-->
                <!--[if (IE 9)&!(IEMobile)]>
                <html class="ie9" lang="en">
                <![endif]-->
                <!--[[if (gt IE 9)|(gt IEMobile 7)]>
                <!-->
                <html lang="en">
                <!--<![endif]-->

                <!--
                * UBC CLF (Common Look and Feel) v7.0.2
                * Copyright 2012 The University of British Columbia
                * UBC Communications and Marketing
                * http://brand.ubc.ca/clf
                */
                -->

                <head>
                <meta http-equiv="X-UA-Compatible" content="IE=10" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1">
                <meta name="description" content="Learning, knowledge, research, insight: welcome to the world of UBC Library, the second-largest academic research library in Canada.">
                <meta name="author" content="">

                <!-- CLF Stylesheets -->
                <link href="https://cdn.ubc.ca/clf/7.0.2/css/ubc-clf-full.min.css" rel="stylesheet">
                <link href="https://clf.library.ubc.ca/7.0.2/colorbox/colorbox.css" type="text/css" media="screen" rel="stylesheet" />
                <link href="https://clf.library.ubc.ca/7.0.2/css/unit.css" rel="stylesheet">

                <!--[if lte IE 7]>
                <link href="https://cdn.ubc.ca/clf/7.0.2/css/font-awesome-ie7.css" rel="stylesheet">
                <![endif]-->
                <!-- HTML5 shim, for IE6-8 support of HTML5 elements .. always load for IE because bbcom forces ie 8 mode in all versions -->
                <!--[if IE]>
                <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
                <![endif]-->
            <link rel="shortcut icon" href="https://cdn.ubc.ca/clf/7.0.2/img/favicon.ico">
                <link rel="apple-touch-icon-precomposed" sizes="144x144" href="https://cdn.ubc.ca/clf/7.0.2/img/apple-touch-icon-144-precomposed.png">
                <link rel="apple-touch-icon-precomposed" sizes="114x114" href="https://cdn.ubc.ca/clf/7.0.2/img/apple-touch-icon-114-precomposed.png">
                <link rel="apple-touch-icon-precomposed" sizes="72x72" href="https://cdn.ubc.ca/clf/7.0.2/img/apple-touch-icon-72-precomposed.png">
                <link rel="apple-touch-icon-precomposed" href="https://cdn.ubc.ca/clf/7.0.2/img/apple-touch-icon-57-precomposed.png">

                <!-- jQuery -->
                <script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
                <script src="https://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

                <!-- CLF JavaScript -->
                <script src="https://clf.library.ubc.ca/7.0.2/colorbox/jquery.colorbox.js" type="text/javascript"></script>
                <script src="https://clf.library.ubc.ca/7.0.2/modernizr/modernizr.js" type="text/javascript"></script>
                <script src="https://cdn.ubc.ca/clf/7.0.2/js/ubc-clf.min.js"></script>
                <script src="https://clf.library.ubc.ca/7.0.2/js/library-ui.js" type="text/javascript"></script>

                <!-- Google Fonts (I think) -->
                <script src="https://www.google.com/jsapi?key=ABQIAAAAoRs91XgpKw60K4liNrOHoBStNMhZCa0lqZKLUDgzjZGRsKl38xSnSmVmaulnWVdBLItzW4KsddHCzA" type="text/javascript"></script>

                <!-- IE Console Fix -->
                <script src="/js/console.js" type="text/javascript"></script>

                <!-- ?? -->
                <script> if (!$.curCSS) $.curCSS = $.css; </script>

                <!-- Font Awesome -->
                <link rel="stylesheet" href="/css/vendor/font-awesome/css/font-awesome.min.css">

                <!-- Todo sean: remove -->
                <script src="//cdnjs.cloudflare.com/ajax/libs/foundation/3.2.5/javascripts/jquery.foundation.reveal.js"></script>

                <!-- DataTables Legacy -->
                <script src="/js/vendor/datatables-legacy/jquery.dataTables.min.js"></script>
                <script src="/js/vendor/datatables-legacy/jquery.dataTables.rowReordering.js"></script>

                <!-- Qtip -->
                <script src="/js/vendor/qtip/jquery.qtip.min.js"></script>

                <!-- jQuery UI -->
                <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
                <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

                <!-- Perfect Scrollbar -->
                <link rel="stylesheet" href="/js/vendor/perfect-scrollbar/css/perfect-scrollbar.min.css">
                <script src="/js/vendor/perfect-scrollbar/js/perfect-scrollbar.jquery.min.js"></script>

                <!-- jQuery UI Touch Punch -->
                <script src="/js/vendor/jquery-touch-punch/jquery.ui.touch-punch.min.js"></script>

                <!-- Main CSS -->
                <link rel="stylesheet" href="/css/style.css" />

                <script src="//cdn.rawgit.com/julmot/mark.js/master/dist/jquery.mark.min.js"></script>

                <style>mark {padding: 0;background: #f1c40f;} table {margin:0;} div.search span {display: block;}</style>


                <?php


            break;
        case 2:
            ?>
            </head>
            <body>
            <?php
            break;
        default:
            echo '<p class="alert alert-error">There is no header part '.$part.'</p>';
    }
}

function theme_footer($part=0){
    switch($part){
        case 1:
            break;
        case 2:
            ?>
                </body>
                <script>
                    $(function(){
                    $("html,body").css({
                        width:"auto",
                        margin:0,
                        padding:0
                    });
                    });
                </script>
                </html>
            <?php
            break;
        default:
            echo '<p class="alert alert-error">There is no footer part '.$part.'</p>';
    }
}
