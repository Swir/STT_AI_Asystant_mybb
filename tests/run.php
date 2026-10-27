<?php
// Offline integration harness: SQLite MyBB adapter and simulated provider responses.
error_reporting(E_ALL);
if (extension_loaded('curl')) { fwrite(STDERR, "Run this offline test with php -n; cURL must not be loaded.\n"); exit(2); }
foreach (['STTAI_API_KEY', 'STTAI_GROQ_API_KEY', 'STTAI_GEMINI_API_KEY'] as $name) { putenv($name); }
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
define('IN_MYBB', 1);
define('TABLE_PREFIX', 'mybb_');
define('MYBB_ROOT', __DIR__.'/fixture/');
foreach (['CURLOPT_POST','CURLOPT_POSTFIELDS','CURLOPT_HTTPHEADER','CURLOPT_CONNECTTIMEOUT','CURLOPT_TIMEOUT','CURLOPT_FOLLOWLOCATION','CURLOPT_SSL_VERIFYPEER','CURLOPT_SSL_VERIFYHOST','CURLOPT_PROTOCOLS','CURLPROTO_HTTPS','CURLOPT_WRITEFUNCTION','CURLOPT_HEADERFUNCTION','CURLINFO_RESPONSE_CODE'] as $i => $name) { define($name, $i + 1); }
$apiCalls = 0; $apiHttp = 200; $apiDuring = null; $apiText = 'Sprawdź ustawienia i podaj komunikat błędu.';
function curl_init($url) { return (object)['url' => $url, 'options' => []]; }
function curl_setopt_array($handle, $options) { $handle->options = $options; }
function curl_exec($handle) {
    global $apiCalls, $apiDuring, $apiText, $apiHttp, $apiNetwork, $lastRequest, $lastUrl;
    $apiCalls++; $lastRequest = $handle->options[CURLOPT_POSTFIELDS];
    $lastUrl = $handle->url;
    if ($apiNetwork) { return false; }
    if ($apiDuring) { ($apiDuring)(); }
    if ($apiHttp === 429) { ($handle->options[CURLOPT_HEADERFUNCTION])($handle, "Retry-After: 120\r\n"); }
    $payload = json_encode(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => $apiText]]]]);
    if (str_contains($handle->url,'generativelanguage.googleapis.com')) {
        $payload = json_encode(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>$apiText]]]]]]);
    }
    ($handle->options[CURLOPT_WRITEFUNCTION])($handle, $payload);
    return true;
}
function curl_getinfo($handle, $key) { global $apiHttp; return $apiHttp; }
function curl_errno($handle) { global $apiNetwork; return $apiNetwork; }
function curl_close($handle) {}
function my_inet_pton($ip) { return inet_pton($ip); }
function sttshoutbox_info() { return ['version' => '2.5.0']; }

class TestDb
{
    public $pdo;
    public function __construct() {
        $this->pdo = class_exists('Pdo\\Sqlite') ? new Pdo\Sqlite('sqlite::memory:') : new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (method_exists($this->pdo, 'createFunction')) { $this->pdo->createFunction('GREATEST', 'max', -1); }
        else { $this->pdo->sqliteCreateFunction('GREATEST', 'max', -1); }
    }
    public function query($sql) {
        if (str_contains($sql, 'GET_LOCK')) { return $this->pdo->query('SELECT 1 AS acquired'); }
        if (str_contains($sql, 'RELEASE_LOCK')) { return $this->pdo->query('SELECT 1'); }
        return $this->pdo->query($sql);
    }
    public function write_query($sql) { return $this->query($sql); }
    public function escape_string($value) { return str_replace("'", "''", (string)$value); }
    public function escape_binary($value) { return $this->escape_string(bin2hex($value)); }
    public function table_exists($table) { return (bool)$this->pdo->query("SELECT name FROM sqlite_master WHERE name='mybb_$table'")->fetchColumn(); }
    public function simple_select($table, $fields = '*', $where = '', $options = []) {
        $sql = "SELECT $fields FROM mybb_$table".($where ? ' WHERE '.$where : '');
        if (!empty($options['order_by'])) { $sql .= ' ORDER BY '.$options['order_by'].' '.($options['order_dir'] ?? 'ASC'); }
        if (isset($options['limit'])) { $sql .= ' LIMIT '.(int)$options['limit']; }
        return $this->query($sql);
    }
    public function fetch_array($result) { return $result->fetch(PDO::FETCH_ASSOC); }
    public function fetch_field($result, $field) { $row = $this->fetch_array($result); return $row[$field] ?? false; }
    public function insert_query($table, $values) {
        $sql = 'INSERT INTO mybb_'.$table.' ('.implode(',', array_keys($values)).') VALUES (';
        $sql .= implode(',', array_map(fn($v) => "'".$v."'", array_values($values))).')';
        $this->query($sql); return (int)$this->pdo->lastInsertId();
    }
    public function update_query($table, $values, $where) {
        $set = []; foreach ($values as $k => $v) { $set[] = $k."='".$v."'"; }
        $this->query('UPDATE mybb_'.$table.' SET '.implode(',', $set).' WHERE '.$where);
    }
    public function delete_query($table, $where) { $this->query('DELETE FROM mybb_'.$table.' WHERE '.$where); }
}

class TestCache {
    public function read($name, $hard = false) {
        global $forums;
        if ($name === 'plugins') { return ['active' => ['sttai' => 'sttai']]; }
        if ($name === 'forums') { return $forums; }
        return [];
    }
}
function usergroup_permissions($groups) {
    return ['canview' => 1, 'isbannedgroup' => str_contains(','.$groups.',', ',7,') ? 1 : 0, 'cancp' => str_contains(','.$groups.',', ',4,') ? 1 : 0];
}
function get_forum($fid) { global $forums; return $forums[$fid] ?? false; }
function forum_permissions($fid, $uid = 0, $gid = 0) {
    global $privateForum, $vipForum;
    $blocked = ($privateForum && (int)$gid === 1) || ($vipForum && in_array((int)$gid,[1,2],true));
    return ['canview' => $blocked ? 0 : 1, 'canviewthreads' => 1, 'canpostreplys' => 1, 'canonlyviewownthreads' => 0, 'canonlyreplyownthreads' => 0];
}

function resetFixture() {
    global $db, $mybb, $cache, $forums, $privateForum, $vipForum, $apiCalls, $apiHttp, $apiDuring, $apiText, $apiNetwork;
    $db = new TestDb(); $cache = new TestCache(); $privateForum = false;
    $vipForum = false; $apiNetwork = 0;
    $apiCalls = 0; $apiHttp = 200; $apiDuring = null; $apiText = 'Sprawdź ustawienia i podaj komunikat błędu.';
    $mybb = (object)['settings' => [
        'sttai_enabled' => 1, 'sttai_api_key' => 'test-key-never-sent', 'sttai_provider' => 'groq',
        'sttai_bot_uid' => 99, 'sttai_groups' => '2,4,6', 'sttai_forums' => '10', 'sttai_context' => 1,
        'sttai_shout' => 1, 'sttai_threads' => 1, 'stt_enabled' => 1, 'stt_bot_post' => 1, 'postmergemins' => 60
    ], 'user' => ['uid' => 1], 'usergroup' => ['canview' => 1], 'binary_fields' => []];
    foreach ([
        'settings' => 'name TEXT PRIMARY KEY,value TEXT',
        'users' => 'uid INTEGER PRIMARY KEY, username TEXT,usergroup INTEGER,additionalgroups TEXT,postnum INTEGER,suspendposting INTEGER DEFAULT 0',
        'usergroups' => 'gid INTEGER PRIMARY KEY,isbannedgroup INTEGER DEFAULT 0',
        'posts' => 'pid INTEGER PRIMARY KEY AUTOINCREMENT,uid INTEGER,tid INTEGER,fid INTEGER,visible INTEGER,message TEXT',
        'threads' => 'tid INTEGER PRIMARY KEY,uid INTEGER,fid INTEGER,firstpost INTEGER,visible INTEGER,closed TEXT,subject TEXT',
        'stt_messages' => 'id INTEGER PRIMARY KEY AUTOINCREMENT,uid INTEGER,text TEXT,date INTEGER,ipaddress TEXT',
        'stt_hidden' => 'sid INTEGER PRIMARY KEY',
        'sttai_jobs' => 'id INTEGER PRIMARY KEY AUTOINCREMENT,channel TEXT,sourceid INTEGER,uid INTEGER,tid INTEGER,content_hash TEXT,created INTEGER,status TEXT,attempts INTEGER DEFAULT 0,available INTEGER DEFAULT 0,started INTEGER DEFAULT 0,error TEXT DEFAULT \'\',replyid INTEGER DEFAULT 0,UNIQUE(channel,sourceid)'
    ] as $table => $schema) { $db->query('CREATE TABLE mybb_'.$table.' ('.$schema.')'); }
    foreach ($mybb->settings as $name => $value) { $db->insert_query('settings', ['name' => $name, 'value' => $value]); }
    $db->insert_query('usergroups', ['gid' => 2, 'isbannedgroup' => 0]);
    $db->insert_query('usergroups', ['gid' => 15, 'isbannedgroup' => 1]);
    foreach ([1 => 'Tester', 2 => 'Inny', 99 => 'STT AI'] as $uid => $name) {
        $db->insert_query('users', ['uid' => $uid, 'username' => $name, 'usergroup' => 2, 'additionalgroups' => '', 'postnum' => 100]);
    }
    $forums = [10 => ['fid' => 10, 'parentlist' => '10', 'active' => 1, 'password' => '', 'type' => 'f']];
    $db->insert_query('threads', ['tid' => 1, 'uid' => 1, 'fid' => 10, 'firstpost' => 10, 'visible' => 1, 'closed' => '', 'subject' => 'Problem z odtwarzaczem']);
    $db->insert_query('posts', ['pid' => 10, 'tid' => 1, 'fid' => 10, 'uid' => 1, 'visible' => 1, 'message' => 'Program pokazuje błąd 404.']);
    $db->insert_query('posts', ['pid' => 11, 'tid' => 1, 'fid' => 10, 'uid' => 1, 'visible' => 1, 'message' => '@AI Jak rozwiązać ten problem?']);
    $db->insert_query('stt_messages', ['id' => 1, 'uid' => 1, 'text' => '!ai Jak zmienić ustawienia?', 'date' => time()]);
}
function setSetting($key, $value) {
    global $db, $mybb;
    $mybb->settings[$key] = $value;
    $db->query("INSERT OR REPLACE INTO mybb_settings (name,value) VALUES ('".$db->escape_string($key)."','".$db->escape_string($value)."')");
}
$checks = 0;
function check($condition, $label) { global $checks; $checks++; if (!$condition) { throw new RuntimeException('FAIL: '.$label); } echo 'PASS: '.$label."\n"; }
function job() { return sttai_row('sttai_jobs', 'id=1'); }
require __DIR__.'/../Upload/inc/plugins/sttai/core.php';
$plugins = new class { public function add_hook($name, $handler) {} };
function get_thread($tid) { return ['tid' => $tid, 'firstpost' => 0]; }
require __DIR__.'/../Upload/inc/plugins/sttai.php';

resetFixture();
$db->update_query('posts', ['message'=>'@AI Nowy temat'], 'pid=10');
sttai_thread_hook((object)['tid'=>1,'pid'=>10]);
check(job()['sourceid'] === 10, 'new thread uses fresh handler PID despite stale thread cache');
check(sttai_source('post',10)!==false,'MyBB default empty closed value is an open thread');
$db->update_query('threads',['closed'=>'0'],'tid=1');
check(sttai_source('post',10)!==false,'reopened MyBB thread remains eligible');
$db->update_query('threads',['closed'=>'moved|22'],'tid=1');
check(sttai_source('post',10)===false,'moved thread marker is never eligible');
resetFixture();
setSetting('sttai_gemini_api_key','second-test-key');
check(sttai_key()==='test-key-never-sent','adding Gemini key preserves active Groq key');
setSetting('sttai_provider','gemini');
check(sttai_key()==='second-test-key','Gemini selects its separate key');
setSetting('sttai_gemini_api_key','');
check(sttai_key()==='' && !sttai_ready(),'Gemini without its own key never falls back to Groq credentials');
resetFixture(); setSetting('sttai_gemini_api_key','second-test-key'); setSetting('sttai_provider','gemini');
setSetting('sttai_model','openai/gpt-oss-20b'); sttai_enqueue('shout',1); sttai_worker();
check(job()['status']==='done' && str_contains($lastUrl,'gemini-3.5-flash-lite:generateContent'), 'Gemini uses its model even when Groq model remains configured');
check((int)$db->fetch_field($db->simple_select('posts','COUNT(*) AS n'),'n')===2, 'shout question never creates a topic reply');

resetFixture();
check(sttai_question('!ai pytanie', 'shout') === 'pytanie', 'explicit shout trigger');
check(sttai_question('Cześć @AI pytanie', 'post') === '', 'no accidental inline trigger');
check(sttai_question('@AI: pytanie', 'post') === 'pytanie', 'case insensitive post trigger');
check(sttai_question('!air pytanie', 'shout') === '', 'no partial trigger');
check(sttai_question('!ai '.str_repeat('x',6001), 'shout') === '', 'oversized questions rejected');
check(sttai_has_hidden('[hide=10]secret[/hide]'), 'protected MyCode detected');
check(sttai_has_hidden('&#91;lock&#93;secret'), 'encoded protected MyCode detected');
check(sttai_source('shout', 1) !== false, 'STT public message allowed');
check(sttai_source('post', 11)['context'] === '' && sttai_source('post',11)['subject'] === '', 'previous posts and title never included');
$privateForum = true;
check(sttai_source('post', 11) === false, 'private forum excluded even for member');
$privateForum = false; $forums[10]['password'] = 'secret';
check(sttai_source('post', 11) === false, 'password forum excluded');
$forums[10]['password'] = ''; $db->update_query('posts',['message'=>'[hide]secret[/hide]'],'pid=10');
check(sttai_source('post',11)['context'] === '', 'hidden first post omitted');
$db->update_query('posts',['message'=>'@AI [hide]secret[/hide]'],'pid=11');
check(sttai_source('post',11) === false, 'hidden question never sent');
resetFixture(); $db->insert_query('stt_hidden',['sid'=>1]);
check(sttai_source('shout',1) === false, 'deleted shout excluded');
resetFixture(); $db->update_query('threads',['closed'=>'1'],'tid=1');
check(sttai_source('post',11) === false, 'closed thread excluded');
resetFixture(); $db->update_query('posts',['visible'=>0],'pid=11');
check(sttai_source('post',11) === false, 'unapproved post excluded');
resetFixture(); $db->update_query('users',['usergroup'=>7],'uid=1');
check(sttai_source('shout',1) === false, 'banned user excluded');
resetFixture(); $db->update_query('users',['usergroup'=>15],'uid=1'); setSetting('sttai_groups','15');
check(sttai_source('shout',1) === false, 'custom banned primary group excluded');
resetFixture(); $db->update_query('stt_messages',['uid'=>99],'id=1');
check(sttai_source('shout',1) === false, 'bot cannot call itself');

resetFixture(); sttai_enqueue('shout',1); sttai_enqueue('shout',1);
check(sttai_count('1=1')===1, 'duplicate event queued only once');
sttai_enqueue('post',11);
check(sttai_count('1=1')===1, 'cooldown shared across channels');
$db->update_query('sttai_jobs',['created'=>time()-100],'id=1'); setSetting('sttai_user_daily',1); sttai_enqueue('post',11);
check(sttai_count('1=1')===1, 'per-user daily quota');
setSetting('sttai_user_daily',10); setSetting('sttai_global_daily',1); $db->update_query('posts',['uid'=>2],'pid=11'); sttai_enqueue('post',11);
check(sttai_count('1=1')===1, 'global daily quota');
resetFixture(); sttai_enqueue('shout',1); sttai_worker();
check(job()['status']==='done' && $apiCalls===1, 'queued shout answered once');
sttai_worker(); check($apiCalls===1, 'completed job not replayed');
check(sttai_row('stt_messages','id=2')['uid']==99, 'answer uses configured bot account');

resetFixture(); $post=['caller'=>'preserved']; sttai_enqueue('post',11); sttai_worker();
check(job()['status']==='done', 'topic answer uses post data handler');
check($post===['caller'=>'preserved'], 'MyAlerts global post context restored after publishing');
check($mybb->user['uid']===1 && $mybb->settings['postmergemins']==60, 'caller context restored after posting');
check(!str_contains($lastRequest,'pierwszy_post_tematu') && !str_contains($lastRequest,'Program pokazuje') && !str_contains($lastRequest,'test-key-never-sent'), 'request sends only explicit question without history or secret');
check((int)$db->fetch_field($db->simple_select('stt_messages','COUNT(*) AS n'),'n')===1 && (int)$mybb->settings['stt_bot_post']===1, 'topic reply does not create a shout and restores STT notifications');
resetFixture(); $privateForum=true; setSetting('sttai_forum_access','members');
check(sttai_source('post',11)!==false, 'approved ordinary members mode supports login-required forums');
$vipForum=true;
check(sttai_source('post',11)===false, 'members mode still rejects VIP and staff-only forums');
$vipForum=false; $forums[10]['password']='secret';
check(sttai_source('post',11)===false, 'members mode still rejects password-protected forums');

resetFixture(); sttai_enqueue('shout',1); $db->update_query('stt_messages',['text'=>'zmienione'],'id=1'); sttai_worker();
check(job()['status']==='cancelled' && $apiCalls===0, 'edit before processing cancels request');
resetFixture(); sttai_enqueue('shout',1); $apiDuring=function(){global $db;$db->insert_query('stt_hidden',['sid'=>1]);}; sttai_worker();
check(job()['status']==='cancelled' && !sttai_row('stt_messages','id=2'), 'delete during API call prevents publication');
resetFixture(); sttai_enqueue('post',11); $apiDuring=function(){global $privateForum;$privateForum=true;}; sttai_worker();
check(job()['status']==='cancelled', 'permission change during API call prevents publication');
resetFixture(); sttai_enqueue('shout',1); $apiDuring=function(){setSetting('sttai_enabled',0);}; sttai_worker();
check(job()['status']==='cancelled', 'disable during API call prevents publication');

resetFixture(); sttai_enqueue('shout',1); $apiHttp=429; sttai_worker();
check(job()['status']==='queued' && job()['available']>=time()+119, '429 retry-after respected');
$db->update_query('posts',['uid'=>2],'pid=11'); sttai_enqueue('post',11); sttai_worker();
check($apiCalls===1, 'provider backoff also covers newly queued requests');
resetFixture(); sttai_enqueue('shout',1); $apiHttp=401; sttai_worker();
check(job()['status']==='failed' && job()['error']==='api_http_401', 'invalid key fails without leaking response');
resetFixture(); sttai_enqueue('shout',1); $apiNetwork=28; $networkLog=sttai_worker();
check(job()['status']==='queued' && job()['error']==='api_network_28', 'network timeout preserves bounded retry');
check(str_contains($networkLog,'api_network_28') && !str_contains($networkLog,'test-key'), 'network log exposes only a numeric error code');
resetFixture(); sttai_enqueue('shout',1); $db->update_query('sttai_jobs',['status'=>'running','started'=>time()-700],'id=1'); sttai_worker();
check(job()['status']==='failed' && $apiCalls===0, 'interrupted publication never replayed automatically');
resetFixture(); sttai_enqueue('shout',1); $db->update_query('sttai_jobs',['attempts'=>150,'started'=>time()],'id=1'); sttai_worker();
check($apiCalls===0, 'API attempt budget enforced');
resetFixture(); sttai_enqueue('post',11); setSetting('postfloodcheck',1); setSetting('postfloodsecs',60);
$db->query('ALTER TABLE mybb_users ADD COLUMN lastpost INTEGER DEFAULT 0');
$db->update_query('users',['lastpost'=>time()],'uid=99'); sttai_worker();
check($apiCalls===0 && job()['available']>time(), 'forum flood delay checked before spending API quota');
resetFixture(); sttai_enqueue('shout',1); $db->update_query('sttai_jobs',['attempts'=>2],'id=1'); $apiHttp=429; sttai_worker();
check(job()['status']==='failed' && job()['attempts']==3, 'transient API retries capped at three attempts');

$safe=sttai_output('[img]evil[/img] <script> @user &#91;url&#93;',900);
check(!str_contains($safe,'[')&&!str_contains($safe,'<')&&!str_contains($safe,'@')&&!str_contains($safe,'&#91;'), 'output cannot activate HTML, MyCode or mentions');
check(mb_strlen(sttai_output(str_repeat('ą',1000),100))===100, 'unicode output bounded');
$spec=sttai_request_spec('gemini','gemini-3.5-flash-lite','KEY','system','question',4096);
check(!str_contains($spec['url'],'KEY')&&in_array('x-goog-api-key: KEY',$spec['headers']), 'Gemini key is sent only as header');
try {sttai_request_spec('groq','../bad?query','KEY','s','u',4096);check(false,'invalid model');} catch(RuntimeException $e){check($e->getMessage()==='api_configuration','invalid model rejected');}
check(sttai_parse_answer('gemini',['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['thought'=>true,'text'=>'hidden reasoning'],['text'=>'visible answer']]]]]])==='visible answer','Gemini thoughts not published');
try {sttai_parse_answer('groq',['choices'=>[['finish_reason'=>'length','message'=>['content'=>'partial']]]]);check(false,'truncation');}catch(RuntimeException $e){check($e->getMessage()==='api_incomplete','incomplete model answer rejected');}
resetFixture();
putenv('STTAI_GROQ_API_KEY=groq-environment-test-value');
putenv('STTAI_GEMINI_API_KEY=gemini-environment-test-value');
check(sttai_key()==='groq-environment-test-value', 'Groq-specific environment key takes precedence');
setSetting('sttai_provider','gemini');
check(sttai_key()==='gemini-environment-test-value', 'Gemini-specific environment key stays isolated');
putenv('STTAI_GEMINI_API_KEY');
putenv('STTAI_API_KEY=legacy-groq-test-value');
check(sttai_key()==='', 'Gemini never uses either Groq environment key');
setSetting('sttai_gemini_api_key','saved-gemini-test-value');
check(sttai_key()==='saved-gemini-test-value', 'Gemini uses its saved key when its environment override is absent');
setSetting('sttai_provider','unknown');
check(sttai_key()==='' && !sttai_ready(), 'unknown provider cannot select credentials');
foreach (['STTAI_API_KEY','STTAI_GROQ_API_KEY','STTAI_GEMINI_API_KEY'] as $name) { putenv($name); }

resetFixture();
$source=sttai_source('post',11);
check(sttai_reply_language($source)==='english', 'missing language falls back to English');
setSetting('bblanguage','polish');
check(sttai_reply_language($source)==='polish', 'reply language uses forum default');
$source['user']['language']='english';
check(sttai_reply_language($source)==='english', 'requester language overrides forum default');
setSetting('sttai_reply_language','polish');
check(sttai_reply_language($source)==='polish' && str_contains(sttai_prompt($source)[0],'Reply in Polish.'), 'forced Polish controls the model instruction');
setSetting('sttai_reply_language','english');
check(str_contains(sttai_prompt($source)[0],'Reply in English.'), 'forced English controls the model instruction');
$replyId=sttai_publish($source,'Example answer');
check(str_starts_with(sttai_row('posts','pid='.$replyId)['message'],'AI reply to post #11'), 'English answer labels are published in the original topic');
setSetting('sttai_reply_language','polish');
$replyId=sttai_publish($source,'Przykładowa odpowiedź');
check(str_starts_with(sttai_row('posts','pid='.$replyId)['message'],'Odpowiedź AI na post #11'), 'Polish answer labels are published in the original topic');
$en=sttai_dictionary('english'); $pl=sttai_dictionary('polish');
check(array_keys($en)===array_keys($pl), 'English and Polish packs contain matching keys');
foreach ([$en,$pl] as $dictionary) {
    check(count(array_filter($dictionary,fn($v)=>is_string($v)&&trim($v)!==''))===count($dictionary), 'language pack has no empty translations');
}
require __DIR__.'/../Upload/inc/plugins/sttai/settings.php';
$definitions=sttai_setting_definitions();
check($definitions['api_key'][1]==='' && $definitions['gemini_api_key'][1]==='', 'both API keys ship empty');
check($definitions['enabled'][1]==='0' && $definitions['bot_uid'][1]==='0' && $definitions['forums'][1]==='', 'fresh install is disabled with no bot account or forum allowlist');
foreach ($definitions as $key=>$definition) {
    check(isset($en['setting_sttai_'.$key],$en['setting_sttai_'.$key.'_desc'],$pl['setting_sttai_'.$key],$pl['setting_sttai_'.$key.'_desc']), 'setting translated in both languages: '.$key);
}
define('IN_ADMINCP',1); $lang=(object)['language'=>'polish'];
sttai_admin_language();
check($lang->setting_sttai_enabled==='Włącz pomocnika AI' && $lang->setting_sttai_forum_access_public==='Tylko działy czytelne bez logowania', 'ACP setting labels and select choices use Polish');
$lang->language='english'; sttai_admin_language();
check($lang->setting_sttai_enabled==='Enable AI assistant', 'ACP switches back to English without changing saved values');

echo "\n$checks checks passed. No real API calls or hosting changes.\n";
