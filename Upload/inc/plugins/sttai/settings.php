<?php
if (!defined('IN_MYBB')) { die('Direct access denied.'); }

/** Metadata is translated at display time; upgrades never replace saved values. */
function sttai_setting_definitions()
{
    return [
        'enabled' => ['yesno', '0', true],
        'provider' => ['select
groq=Groq
gemini=Google Gemini', 'groq', true],
        'api_key' => ['passwordbox', '', true],
        'gemini_api_key' => ['passwordbox', '', true],
        'bot_uid' => ['numeric', '0', true],
        'shout' => ['yesno', '1', true],
        'threads' => ['yesno', '1', true],
        'forums' => ['forumselect', '', true],
        'forum_access' => ['select
public=Public forums only
members=Also ordinary member forums', 'public', true],
        'reply_language' => ['select
auto=Member language
english=English
polish=Polish', 'auto', true],
        'groups' => ['groupselect', '2,4,6', false],
        'model' => ['text', '', false],
        'gemini_model' => ['text', '', false],
        'cooldown' => ['numeric', '60', false],
        'user_daily' => ['numeric', '10', false],
        'global_daily' => ['numeric', '100', false],
        'api_daily' => ['numeric', '150', false],
        'queue_limit' => ['numeric', '30', false],
        'instructions' => ['textarea', sttai_t('default_instructions'), false],
    ];
}
