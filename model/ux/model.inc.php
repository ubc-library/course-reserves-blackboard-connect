<?php

    class Model_ux
    {

        function __construct()
        {

        }

        function getIcon($format)
        {
            if(sv('browser') == 'pass'){
                return $this->getModernIcons($format);
            }
            else {
                return $this->getLegacyIcons($format);
            }
        }

        private function getModernIcons($format){
            switch ($format) {
                case 'pdf_general':
                case 'pdf_article':
                case 'pdf_chapter':
                case 'pdf_other':
                    return 'cloud-upload';
                case 'book_general':
                case 'ebook_general':
                    return 'book';
                case 'book_chapter':
                case 'ebook_chapter':
                    return 'bookmark-o';
                case 'web_general':
                    return 'globe';
                case 'electronic_article':
                    return 'file-text-o';
                case 'stream_general':
                case 'stream_music':
                case 'stream_video':
                    return 'film';
                case 'physical_general':
                case 'physical_unknown_type':
                    return 'archive';
                case 'undetermined':
                    return 'question';
                default:
                    return 'question';
            }
        }

        private function getLegacyIcons($format){
            switch ($format) {
                case 'pdf_general':
                case 'pdf_article':
                case 'pdf_chapter':
                case 'pdf_other':
                    return 'cloud-upload';
                case 'book_general':
                case 'ebook_general':
                    return 'book';
                case 'book_chapter':
                case 'ebook_chapter':
                    return 'bookmark-o';
                case 'web_general':
                    return 'globe';
                case 'electronic_article':
                    return 'file-text-o';
                case 'stream_general':
                case 'stream_music':
                case 'stream_video':
                    return 'film';
                case 'physical_general':
                case 'physical_unknown_type':
                    return 'archive';
                case 'undetermined':
                    return 'question';
                default:
                    return 'question';
            }
        }
    }