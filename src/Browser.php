<?php

namespace Ecpanda\Util;

class Browser
{
    /**
     * Get client language
     * @param  string $default default lang code
     * @return string
     */
    public static function getClientLang($default = 'en')
    {
        $available_langs = ['en', 'zh'];

        $accept_lang = I('server.HTTP_ACCEPT_LANGUAGE');

        if (empty($accept_lang)) {
            return $default;
        }

        $langs = explode(',', $accept_lang);

        foreach ( $langs as $lang ) {
            $lang = substr($lang, 0, 2);

            if( in_array($lang, $available_langs ) ) {
                return $lang;
            }
        }

        return $default;
    }
}