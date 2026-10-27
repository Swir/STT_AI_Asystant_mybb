<?php
if (!defined('IN_MYBB')) { die('Direct access denied.'); }

function task_sttai($task)
{
    global $cache, $db;
    $pluginsCache = $cache->read('plugins');
    if (empty($pluginsCache['active']['sttai']) || !$db->table_exists('sttai_jobs')) { return; }
    require_once MYBB_ROOT.'inc/plugins/sttai/core.php';
    $summary = sttai_worker();
    if ($summary !== '') { add_task_log($task, $summary); }
}
