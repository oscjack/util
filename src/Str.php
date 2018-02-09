<?php

namespace Ecpanda\Util;

class Str 
{
    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * The cache of camel-cased words.
     *
     * @var array
     */
    protected static $camelCache = [];

    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];

    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    public static function camel($value)
    {
        if (isset(static::$camelCache[$value]))
        {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ($needle != '' && strpos($haystack, $needle) !== false) return true;
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ((string) $needle === substr($haystack, -strlen($needle))) return true;
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:'.$quoted.')+$/', '', $value).$cap;
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    public static function is($pattern, $value)
    {
        if ($pattern == $value) return true;

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern).'\z';

        return (bool) preg_match('#^'.$pattern.'#', $value);
    }

    /**
     * Return the length of the given string.
     *
     * @param  string  $value
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value);
    }

    /**
     * Limit the number of characters in a string.
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strlen($value) <= $limit) return $value;

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')).$end;
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value);
    }

    /**
     * Limit the number of words in a string.
     *
     * @param  string  $value
     * @param  int     $words
     * @param  string  $end
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if ( ! isset($matches[0]) || strlen($value) === strlen($matches[0])) return $value;

        return rtrim($matches[0]).$end;
    }

    /**
     * Parse a Class@method style callback into class and method.
     *
     * @param  string  $callback
     * @param  string  $default
     * @return array
     */
    public static function parseCallback($callback, $default)
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : array($callback, $default);
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length)
        {
            $size = $length - $len;
            $bytes = static::randomBytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Generate a more truly "random" bytes.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function randomBytes($length = 16)
    {
        if (function_exists('random_bytes'))
        {
            $bytes = random_bytes($length);
        }
        elseif (function_exists('openssl_random_pseudo_bytes'))
        {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes === false || $strong === false)
            {
                throw new RuntimeException('Unable to generate random string.');
            }
        }
        else
        {
            throw new RuntimeException('OpenSSL extension is required for PHP 5 users.');
        }

        return $bytes;
    }

    /**
     * Generate a "random" alpha-numeric string.
     *
     * Should not be considered sufficient for cryptography, etc.
     *
     * @param  int  $length
     * @return string
     */
    public static function quickRandom($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value);
    }

    /**
     * Convert the given string to title case.
     *
     * @param  string  $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value.$delimiter;

        if (isset(static::$snakeCache[$key]))
        {
            return static::$snakeCache[$key];
        }

        if ( ! ctype_lower($value))
        {
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
        }

        return static::$snakeCache[$key] = $value;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key]))
        {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(array('-', '_'), ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    /**
     * Wraps text to a specific width, can optionally wrap at word breaks.
     *
     * ### Options
     *
     * - `width` The width to wrap to. Defaults to 72.
     * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
     * - `indent` String to indent with. Defaults to null.
     * - `indentAt` 0 based index to start indenting at. Defaults to 0.
     *
     * @param string $text The text to format.
     * @param array|int $options Array of options to use, or an integer to wrap the text to.
     * @return string Formatted text.
     */
    public static function wrap($text, $options = [])
    {
        if (is_numeric($options)) {
            $options = ['width' => $options];
        }
        $options += ['width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0];
        if ($options['wordWrap']) {
            $wrapped = self::wordWrap($text, $options['width'], "\n");
        } else {
            $wrapped = trim(chunk_split($text, $options['width'] - 1, "\n"));
        }
        if (!empty($options['indent'])) {
            $chunks = explode("\n", $wrapped);
            for ($i = $options['indentAt'], $len = count($chunks); $i < $len; $i++) {
                $chunks[$i] = $options['indent'] . $chunks[$i];
            }
            $wrapped = implode("\n", $chunks);
        }

        return $wrapped;
    }

    /**
     * Unicode and newline aware version of wordwrap.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    public static function wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        $paragraphs = explode($break, $text);
        foreach ($paragraphs as &$paragraph) {
            $paragraph = static::_wordWrap($paragraph, $width, $break, $cut);
        }

        return implode($break, $paragraphs);
    }
    
    /**
     * 移除字符串的BOM
     *
     * @param  string $str 输入字符串
     * @return string 输出字符串
     */
    public static function removeBOM($str)
    {
        $str_3 = substr($str, 0, 3);
        if ($str_3 == pack('CCC',0xef,0xbb,0xbf)) //utf-8
            return substr($str, 3);
        return $str;
    }

    /**
     * 按UTF-8分隔为数组，效率比MB_Substr高
     * 0xxxxxxx
     * 110xxxxx 10xxxxxx
     * 1110xxxx 10xxxxxx 10xxxxxx
     * 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
     * 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
     * 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
     *
     * @param string $str 输入utf-8字符串
     * @return array 返回成一段数组
     */
    public static function str_split_utf8($str)
    {
        return preg_match_all(
            '/./u', static::removeBOM($str), $out
        ) ? $out[0] : FALSE;
    }

    /**
     * 按非ascii字符占有几个字宽的方式切分字符串，并且不会将汉字切成半个
     * 所谓字宽是指,使用默认字体显示时，非ascii字符相比英文字符所占大小，比如：宋体、微软雅黑中，汉字占两个宽度
     * @example $ansi_width = 2 表示汉字等非英文字符按照两个字宽长度
     * @example $ansi_width = 1 表示所有字符按一个字宽长度
     *
     * @param string $string 原始字符
     * @param integer $offset 开始偏移,使用方法和substr一样,可以为负数
     * @param integer $length 长度,使用方法和substr一样,可以为负数
     * @param integer $ansi_width 汉字等非英文字符按照几个字符来处理
     * @return string 返回裁减的字符串
     */
    public static function substr_ansi($string, $offset, $length = 0, $ansi_width = 1)
    {
        if (empty($string)) return $string;;

        $data = static::str_split_utf8($string);

        if (empty($data)) return $string;
     
        $as = $_as = array();
        $_start = $_end = 0;
     
        foreach($data as $k => $v)
            $as[$k] = strlen($v) > 1 ? $ansi_width : 1;
     
        $_as_rev = array_reverse($as,true);
        $_as = $offset < 0 ? $_as_rev : $as; 
        $n = 0; $_offset = abs($offset);
        foreach($_as as $k => $v) {
            if ($n >= $_offset) {
                $_start = $k;
                break;
            }
            $n += $v;
        }
        //echo $_start,',';
        $_as = $length <= 0 ? $_as_rev : $as;
        end($_as); list($_end) = each($_as); reset($_as);//给$_end 设定默认值，一直到结尾
        $n = 0; $_length = abs($length);
        foreach($_as as $k => $v) {
            if ($k >= $_start) {
                if ($n >= $_length) {
                    $_end = $k + ($length <= 0 ? 1 : 0);
                    break;
                }
                $n += $v;
            }
        }
        //echo $_end,'|||||';
        if ($_end <= $_start)
            return '';
     
        $_data = array_slice($data, $_start, $_end - $_start);
     
        return implode('',$_data);
    }

    /**
     * 按非ascii字符占有几个字宽的方式计算字符串长度
     * @example $ansi_width = 2 表示汉字等非英文字符按照两个字宽长度
     * @example $ansi_width = 1 表示所有字符按一个字节长度
     *
     * @param string $string 原始字符
     * @param integer $ansi_width 汉字等非英文字符按照几个字宽来处理
     * @return string 返回字符串长度
     */
    public static function strlen_ansi($string, $ansi_width = 1)
    {
        if (empty($string)) return 0;
        $data = static::str_split_utf8($string);
        if (empty($data)) return 0;
     
        $as = 0;
        foreach($data as $k => $v)
            $as += strlen($v) > 1 ? $ansi_width : 1;
        unset($data);
        return $as;
    }

    /**
     * smarty truncate 代码算法来自于Smarty
     * @param string
     * @param integer
     * @param string
     * @param boolean
     * @param boolean
     * @return string
     */
    public static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
    {
        if ($length == 0)
            return '';
        $ansi_as = 2;
        if (static::strlen_ansi($string, $ansi_as) > $length) {
            $length -= min($length, static::strlen_ansi($etc, $ansi_as));
            if (!$break_words && !$middle) {
                $string = preg_replace('/\s+?(\S+)?$/u', '', static::substr_ansi($string, 0, $length+1, $ansi_as));
            }
            if(!$middle) {
               return static::substr_ansi($string, 0, $length, $ansi_as) . $etc;
            } else {
                return static::substr_ansi($string, 0, $length/2, $ansi_as) . $etc . static::substr_ansi($string, -$length/2, 0,  $ansi_as);
            }
        } else {
            return $string;
        }
    }
    
    /**
     * Unicode aware version of wordwrap as helper method.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    protected static function _wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        if ($cut) {
            $parts = [];
            while (mb_strlen($text) > 0) {
                $part = mb_substr($text, 0, $width);
                $parts[] = trim($part);
                $text = trim(mb_substr($text, mb_strlen($part)));
            }

            return implode($break, $parts);
        }

        $parts = [];
        while (mb_strlen($text) > 0) {
            if ($width >= mb_strlen($text)) {
                $parts[] = trim($text);
                break;
            }

            $part = mb_substr($text, 0, $width);
            $nextChar = mb_substr($text, $width, 1);
            if ($nextChar !== ' ') {
                $breakAt = mb_strrpos($part, ' ');
                if ($breakAt === false) {
                    $breakAt = mb_strpos($text, ' ', $width);
                }
                if ($breakAt === false) {
                    $parts[] = trim($text);
                    break;
                }
                $part = mb_substr($text, 0, $breakAt);
            }

            $part = trim($part);
            $parts[] = $part;
            $text = trim(mb_substr($text, mb_strlen($part)));
        }

        return implode($break, $parts);
    }
}
