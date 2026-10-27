<?php
/** STT AI Assistant 1.0.0 — MyBB 1.8 / STTShoutbox 2.5. */
if (!defined('IN_MYBB')) { die('Direct access denied.'); }

require_once __DIR__.'/sttai/i18n.php';
$plugins->add_hook('admin_load', 'sttai_admin_language');

$plugins->add_hook('sttshoutbox_message_saved', 'sttai_shout_hook');
$plugins->add_hook('datahandler_post_insert_post_end', 'sttai_post_hook');
$plugins->add_hook('datahandler_post_insert_thread_end', 'sttai_thread_hook');

function sttai_info()
{
    global $db;
    // Add new settings on the ACP plugin page without deactivating or cancelling jobs.
    if (defined('IN_ADMINCP') && sttai_is_installed()
        && !$db->fetch_field($db->simple_select('settings', 'sid', "name='sttai_reply_language'"), 'sid')) {
        sttai_install();
    }
    $gid = (int)$db->fetch_field($db->simple_select('settinggroups', 'gid', "name='sttai'"), 'gid');
    $advancedGid = (int)$db->fetch_field($db->simple_select('settinggroups', 'gid', "name='sttai_advanced'"), 'gid');
    return [
        'name' => 'STT AI Assistant', 'version' => '1.0.0', 'compatibility' => '18*',
        'author' => 'SWIRTV TEAM', 'website' => 'https://github.com/Swir/STT_AI_Asystant_mybb', 'authorsite' => '',
        'description' => sttai_t('plugin_description')
            .($gid ? '<br><a href="index.php?module=config-settings&amp;action=change&amp;gid='.$gid.'"><strong>'.sttai_t('plugin_configure').'</strong></a>' : '')
            .($advancedGid ? ' · <a href="index.php?module=config-settings&amp;action=change&amp;gid='.$advancedGid.'">'.sttai_t('plugin_advanced').'</a>' : '')
    ];
}

function sttai_load()
{
    require_once __DIR__.'/sttai/core.php';
}

function sttai_is_installed()
{
    global $db;
    return $db->table_exists('sttai_jobs');
}

function sttai_install()
{
    global $db;
    if (PHP_VERSION_ID < 80000 || !extension_loaded('curl') || !extension_loaded('mbstring')
        || !in_array($db->type, ['mysqli', 'mysql', 'mysql_pdo'], true)
        || !is_file(__DIR__.'/sttai/core.php') || !is_file(__DIR__.'/sttai/settings.php')
        || !is_file(MYBB_ROOT.'inc/languages/english/sttai.lang.php')
        || !is_file(MYBB_ROOT.'inc/languages/polish/sttai.lang.php')
        || !is_file(MYBB_ROOT.'inc/tasks/sttai.php')) {
        flash_message(sttai_t('requirements'), 'error');
        admin_redirect('index.php?module=config-plugins');
        return;
    }
    if (!$db->table_exists('sttai_jobs')) {
        $db->write_query('CREATE TABLE '.TABLE_PREFIX.'sttai_jobs (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            channel varchar(8) NOT NULL, sourceid int unsigned NOT NULL,
            uid int unsigned NOT NULL, tid int unsigned NOT NULL DEFAULT 0,
            content_hash char(64) NOT NULL, created int unsigned NOT NULL,
            status varchar(16) NOT NULL DEFAULT \'queued\',
            attempts tinyint unsigned NOT NULL DEFAULT 0,
            available int unsigned NOT NULL DEFAULT 0, started int unsigned NOT NULL DEFAULT 0,
            error varchar(48) NOT NULL DEFAULT \'\', replyid int unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY(id), UNIQUE KEY source(channel,sourceid),
            KEY pending(status,available), KEY quota(created,uid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    $gid = (int)$db->fetch_field($db->simple_select('settinggroups', 'gid', "name='sttai'"), 'gid');
    if (!$gid) {
        $gid = (int)$db->insert_query('settinggroups', [
            'name' => 'sttai', 'title' => 'STT AI Assistant',
            'description' => $db->escape_string(sttai_t('setting_group_sttai_desc')), 'disporder' => 5, 'isdefault' => 0
        ]);
    }
    $advancedGid = (int)$db->fetch_field($db->simple_select('settinggroups', 'gid', "name='sttai_advanced'"), 'gid');
    if (!$advancedGid) {
        $advancedGid = (int)$db->insert_query('settinggroups', [
            'name' => 'sttai_advanced', 'title' => $db->escape_string(sttai_t('setting_group_sttai_advanced')),
            'description' => $db->escape_string(sttai_t('setting_group_sttai_advanced_desc')), 'disporder' => 6, 'isdefault' => 0
        ]);
    }
    require_once __DIR__.'/sttai/settings.php';
    $positions = [0, 0];
    foreach (sttai_setting_definitions() as $key => $setting) {
        [$options, $default, $basic] = $setting;
        $name = 'sttai_'.$key;
        $metadata = [
            'name' => $name, 'title' => $db->escape_string(sttai_t('setting_'.$name)),
            'optionscode' => $db->escape_string($options),
            'description' => $db->escape_string(sttai_t('setting_'.$name.'_desc')),
            'disporder' => ++$positions[$basic ? 0 : 1],
            'gid' => $basic ? $gid : $advancedGid, 'isdefault' => 0
        ];
        if ($db->fetch_field($db->simple_select('settings', 'sid', "name='$name'"), 'sid')) {
            $db->update_query('settings', $metadata, "name='$name'");
        } else { $db->insert_query('settings', $metadata + ['value'=>$db->escape_string($default)]); }
    }
    $db->delete_query('settings', "name='sttai_context'");
    rebuild_settings();
}

function sttai_activate()
{
    global $db, $cache;
    if (!sttai_is_installed()) { return; }
    // Activation upgrades missing settings without replacing configured values.
    sttai_install();
    require_once MYBB_ROOT.'inc/functions_task.php';
    $task = [
        'title' => $db->escape_string(sttai_t('task_title')), 'description' => $db->escape_string(sttai_t('task_description')),
        'file' => 'sttai', 'minute' => '*', 'hour' => '*', 'day' => '*', 'month' => '*', 'weekday' => '*',
        'enabled' => 1, 'logging' => 1, 'locked' => 0
    ];
    $task['nextrun'] = fetch_next_run($task);
    $tid = (int)$db->fetch_field($db->simple_select('tasks', 'tid', "file='sttai'"), 'tid');
    if ($tid) { $db->update_query('tasks', $task, 'tid='.$tid); }
    else { $db->insert_query('tasks', $task + ['lastrun' => 0]); }
    $cache->update_tasks();
}

function sttai_deactivate()
{
    global $db, $cache;
    $db->update_query('settings', ['value' => '0'], "name='sttai_enabled'");
    rebuild_settings();
    $db->update_query('tasks', ['enabled' => 0], "file='sttai'");
    if (sttai_is_installed()) {
        $db->update_query('sttai_jobs', ['status' => 'cancelled', 'error' => 'plugin_disabled'], "status='queued'");
    }
    $cache->update_tasks();
}

function sttai_uninstall()
{
    global $db, $cache;
    sttai_deactivate();
    $db->delete_query('tasks', "file='sttai'");
    $db->delete_query('settings', "name LIKE 'sttai\\_%'");
    $db->delete_query('settinggroups', "name='sttai'");
    $db->delete_query('settinggroups', "name='sttai_advanced'");
    // Remove only this plugin's queue. Published posts and chat history remain intact.
    if (sttai_is_installed()) { $db->drop_table('sttai_jobs'); }
    rebuild_settings();
    $cache->update_tasks();
}

function sttai_shout_hook(&$event)
{
    if (!is_file(__DIR__.'/sttai/core.php')) { return; }
    sttai_load();
    sttai_enqueue('shout', (int)($event['shout_id'] ?? 0));
}

function sttai_post_hook($handler)
{
    if (!is_file(__DIR__.'/sttai/core.php')) { return; }
    sttai_load();
    sttai_enqueue('post', (int)($handler->pid ?? 0));
}

function sttai_thread_hook($handler)
{
    if (!is_file(__DIR__.'/sttai/core.php')) { return; }
    sttai_load();
    // The handler owns the freshly inserted PID; get_thread() may still cache firstpost=0.
    sttai_enqueue('post', (int)($handler->pid ?? 0));
}
