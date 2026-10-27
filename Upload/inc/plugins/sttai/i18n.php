<?php
if (!defined('IN_MYBB')) { die('Direct access denied.'); }

function sttai_normalize_language($name)
{
    return preg_match('~^(polish|polski|pl)(?:[/_-]|$)~i', (string)$name) ? 'polish' : 'english';
}

function sttai_language()
{
    global $lang, $mybb;
    $name = (string)($lang->language ?? '');
    if ($name === '') { $name = (string)($mybb->settings['bblanguage'] ?? 'english'); }
    return sttai_normalize_language($name);
}

function sttai_reply_language(array $source)
{
    global $mybb;
    $choice = $mybb->settings['sttai_reply_language'] ?? 'auto';
    if (in_array($choice, ['english', 'polish'], true)) { return $choice; }
    $name = (string)($source['user']['language'] ?? '');
    return sttai_normalize_language($name !== '' ? $name : ($mybb->settings['bblanguage'] ?? 'english'));
}

function sttai_dictionary($language = null)
{
    static $cache = [];
    $language = $language ?? sttai_language();
    $language = $language === 'polish' ? 'polish' : 'english';
    if (!isset($cache[$language])) {
        $l = [];
        $base = dirname(__DIR__, 2).'/languages/';
        if (is_file($base.'english/sttai.lang.php')) { require $base.'english/sttai.lang.php'; }
        if ($language === 'polish' && is_file($base.'polish/sttai.lang.php')) { require $base.'polish/sttai.lang.php'; }
        $cache[$language] = $l;
    }
    return $cache[$language];
}

function sttai_t($key, array $values = [], $language = null)
{
    $dictionary = sttai_dictionary($language);
    $text = $dictionary[$key] ?? $key;
    $replace = [];
    foreach ($values as $name => $value) { $replace['{'.$name.'}'] = (string)$value; }
    return strtr($text, $replace);
}

function sttai_admin_language()
{
    global $lang;
    if (!defined('IN_ADMINCP') || !is_object($lang)) { return; }
    foreach (sttai_dictionary() as $key => $value) {
        if (strpos($key, 'setting_') === 0) { $lang->$key = $value; }
    }
}
