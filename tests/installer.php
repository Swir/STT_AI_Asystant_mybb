<?php
/** Offline installer lifecycle checks. Uses a temporary package copy and SQLite. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
foreach (['curl', 'mbstring', 'pdo_sqlite'] as $extension) {
    if (!extension_loaded($extension)) { fwrite(STDERR, "Missing test extension: $extension\n"); exit(2); }
}
define('IN_MYBB', 1);
define('IN_ADMINCP', 1);
define('TABLE_PREFIX', 'mybb_');
$temporary = rtrim(sys_get_temp_dir(), '/\\').'/sttai-install-'.bin2hex(random_bytes(6)).'/';
mkdir($temporary, 0700, true);
define('MYBB_ROOT', $temporary);
$package = realpath(__DIR__.'/../Upload');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile()) { continue; }
    $target = $temporary.substr($file->getPathname(), strlen($package) + 1);
    if (!is_dir(dirname($target))) { mkdir(dirname($target), 0700, true); }
    copy($file->getPathname(), $target);
}
file_put_contents($temporary.'inc/functions_task.php', "<?php // Task scheduling is simulated by the offline test.\n");

class InstallDb
{
    public $type = 'mysqli';
    public $pdo;
    public function __construct() {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach ([
            'settinggroups' => 'gid INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT UNIQUE,title TEXT,description TEXT,disporder INTEGER,isdefault INTEGER',
            'settings' => 'sid INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT UNIQUE,title TEXT,optionscode TEXT,description TEXT,disporder INTEGER,gid INTEGER,isdefault INTEGER,value TEXT',
            'tasks' => 'tid INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,description TEXT,file TEXT UNIQUE,minute TEXT,hour TEXT,day TEXT,month TEXT,weekday TEXT,enabled INTEGER,logging INTEGER,locked INTEGER,nextrun INTEGER,lastrun INTEGER',
            'posts' => 'pid INTEGER PRIMARY KEY,message TEXT',
            'stt_messages' => 'id INTEGER PRIMARY KEY,text TEXT'
        ] as $table => $schema) { $this->pdo->exec('CREATE TABLE mybb_'.$table.' ('.$schema.')'); }
        $this->pdo->exec("INSERT INTO mybb_posts VALUES (1,'Existing forum post')");
        $this->pdo->exec("INSERT INTO mybb_stt_messages VALUES (1,'Existing chat message')");
    }
    public function query($sql) { return $this->pdo->query($sql); }
    public function write_query($sql) {
        if (str_starts_with($sql, 'CREATE TABLE mybb_sttai_jobs')) {
            // SQLite stand-in for MySQL's declared queue schema; not a MySQL compatibility test.
            $sql = "CREATE TABLE mybb_sttai_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT,channel TEXT,sourceid INTEGER,uid INTEGER,tid INTEGER DEFAULT 0,content_hash TEXT,created INTEGER,status TEXT DEFAULT 'queued',attempts INTEGER DEFAULT 0,available INTEGER DEFAULT 0,started INTEGER DEFAULT 0,error TEXT DEFAULT '',replyid INTEGER DEFAULT 0,UNIQUE(channel,sourceid))";
        }
        return $this->query($sql);
    }
    public function escape_string($value) { return str_replace("'", "''", (string)$value); }
    public function table_exists($table) { return (bool)$this->pdo->query("SELECT name FROM sqlite_master WHERE name='mybb_$table'")->fetchColumn(); }
    public function simple_select($table, $fields = '*', $where = '', $options = []) { return $this->query('SELECT '.$fields.' FROM mybb_'.$table.($where ? ' WHERE '.$where : '')); }
    public function fetch_array($query) { return $query->fetch(PDO::FETCH_ASSOC); }
    public function fetch_field($query, $name) { $row = $this->fetch_array($query); return $row[$name] ?? false; }
    public function insert_query($table, $values) {
        $this->query('INSERT INTO mybb_'.$table.' ('.implode(',', array_keys($values)).') VALUES ('.implode(',', array_map(fn($value) => "'".$value."'", array_values($values))).')');
        return (int)$this->pdo->lastInsertId();
    }
    public function update_query($table, $values, $where) {
        $parts = []; foreach ($values as $name => $value) { $parts[] = $name."='".$value."'"; }
        $this->query('UPDATE mybb_'.$table.' SET '.implode(',', $parts).' WHERE '.$where);
    }
    public function delete_query($table, $where) {
        if ($where === "name LIKE 'sttai\\_%'") { $where = "substr(name,1,6)='sttai_'"; }
        $this->query('DELETE FROM mybb_'.$table.' WHERE '.$where);
    }
    public function drop_table($table) { $this->query('DROP TABLE mybb_'.$table); }
}

$cache = new class { public function update_tasks() {} };
$plugins = new class { public $hooks = []; public function add_hook($name, $handler) { $this->hooks[$name] = $handler; } };
function rebuild_settings() {
    global $db, $mybb;
    $mybb->settings = [];
    $query = $db->simple_select('settings', 'name,value');
    while ($row = $db->fetch_array($query)) { $mybb->settings[$row['name']] = $row['value']; }
}
function fetch_next_run($task) { return time() + 60; }
function flash_message($text, $type) { global $flashes; $flashes[] = [$text, $type]; }
function admin_redirect($url) { global $redirect; $redirect = $url; }
function value($name) { global $db; return $db->fetch_field($db->simple_select('settings', 'value', "name='$name'"), 'value'); }
$checks = 0;
function check($ok, $label) { global $checks; ++$checks; if (!$ok) { throw new RuntimeException('FAIL: '.$label); } echo "PASS: $label\n"; }
require $temporary.'inc/plugins/sttai.php';
check(isset($plugins->hooks['admin_load'], $plugins->hooks['sttshoutbox_message_saved'], $plugins->hooks['datahandler_post_insert_post_end'], $plugins->hooks['datahandler_post_insert_thread_end']), 'language and message hooks are registered');

foreach (['english', 'polish'] as $language) {
    $db = new InstallDb(); $mybb = (object)['settings' => []]; $lang = (object)['language' => $language]; $flashes = [];
    sttai_install();
    check(sttai_is_installed() && !$flashes, "$language: install succeeds");
    check(value('sttai_enabled') === '0' && value('sttai_bot_uid') === '0' && value('sttai_forums') === '', "$language: installation stays disabled and unconfigured");
    check(value('sttai_api_key') === '' && value('sttai_gemini_api_key') === '', "$language: no bundled API keys");
    check((int)$db->fetch_field($db->simple_select('settinggroups', 'COUNT(*) AS n'), 'n') === 2, "$language: simple and advanced groups created");
    check((int)$db->fetch_field($db->simple_select('settings', 'COUNT(*) AS n'), 'n') === 19, "$language: all settings installed once");
    sttai_admin_language();
    check($lang->setting_sttai_enabled === ($language === 'polish' ? 'Włącz pomocnika AI' : 'Enable AI assistant'), "$language: native ACP translations loaded");
    $info = sttai_info();
    check($info['version'] === '1.0.0' && str_contains($info['description'], sttai_t('plugin_configure')), "$language: plugin version and configuration links");
    $db->update_query('settings', ['value' => 'saved-groq-test-value'], "name='sttai_api_key'");
    $db->update_query('settings', ['value' => 'saved-gemini-test-value'], "name='sttai_gemini_api_key'");
    $db->update_query('settings', ['value' => 'gemini'], "name='sttai_provider'");
    $db->update_query('settings', ['value' => '1'], "name='sttai_enabled'");
    $db->update_query('settings', ['value' => 'Custom response instructions'], "name='sttai_instructions'");
    $db->insert_query('sttai_jobs', ['channel' => 'post', 'sourceid' => 42, 'uid' => 123, 'status' => 'queued']);
    $db->delete_query('settings', "name='sttai_reply_language'");
    sttai_info(); // Upgrade from 0.2.x by opening the plugin page.
    check(value('sttai_reply_language') === 'auto', "$language: upgrade installs missing language preference");
    check(value('sttai_api_key') === 'saved-groq-test-value' && value('sttai_gemini_api_key') === 'saved-gemini-test-value', "$language: upgrade preserves both keys");
    check(value('sttai_enabled') === '1' && value('sttai_provider') === 'gemini' && value('sttai_instructions') === 'Custom response instructions', "$language: upgrade preserves enabled state, provider and custom instructions");
    check($db->fetch_field($db->simple_select('sttai_jobs', 'status', 'id=1'), 'status') === 'queued', "$language: upgrade preserves pending questions");
    sttai_activate(); sttai_activate();
    check((int)$db->fetch_field($db->simple_select('tasks', 'COUNT(*) AS n'), 'n') === 1, "$language: activation creates only one task");
    check($db->fetch_field($db->simple_select('tasks', 'title'), 'title') === sttai_t('task_title'), "$language: task title translated");
    sttai_deactivate();
    check(value('sttai_enabled') === '0' && (int)$db->fetch_field($db->simple_select('tasks', 'enabled'), 'enabled') === 0, "$language: deactivation stops AI and its task");
    check($db->fetch_field($db->simple_select('sttai_jobs', 'status', 'id=1'), 'status') === 'cancelled', "$language: deactivation cancels pending questions");
    sttai_uninstall();
    check(!sttai_is_installed() && (int)$db->fetch_field($db->simple_select('settings', 'COUNT(*) AS n'), 'n') === 0, "$language: uninstall removes plugin queue and settings");
    check((int)$db->fetch_field($db->simple_select('tasks', 'COUNT(*) AS n'), 'n') === 0, "$language: uninstall removes task");
    check((int)$db->fetch_field($db->simple_select('posts', 'COUNT(*) AS n'), 'n') === 1 && (int)$db->fetch_field($db->simple_select('stt_messages', 'COUNT(*) AS n'), 'n') === 1, "$language: published forum and chat messages stay intact");
}
$db = new InstallDb(); $mybb = (object)['settings' => []]; $lang = (object)['language' => 'english']; $flashes = [];
rename($temporary.'inc/tasks/sttai.php', $temporary.'inc/tasks/sttai.php.missing');
sttai_install();
check(!sttai_is_installed() && count($flashes) === 1 && $flashes[0][1] === 'error', 'incomplete upload is rejected before queue creation');
rename($temporary.'inc/tasks/sttai.php.missing', $temporary.'inc/tasks/sttai.php');
echo "\n$checks installer checks passed. Temporary copy only; no real API calls or hosting changes.\n";
