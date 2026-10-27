<?php
class PostDataHandler
{
    public $data;
    public function __construct($method) {}
    public function set_data(&$data) { $this->data = $data; }
    public function validate_post() { return true; }
    public function insert_post() {
        global $db, $mybb, $post;
        if ($post['tid'] !== $this->data['tid'] || $post['uid'] !== 99 || $this->data['posthash'] !== '') { throw new RuntimeException('missing_mybb_post_context'); }
        if ($mybb->user['uid'] !== 99 || $mybb->settings['postmergemins'] !== 0) { throw new RuntimeException('bad_bot_context'); }
        if ($mybb->settings['stt_bot_post'] !== 0) { throw new RuntimeException('topic_leaked_to_shoutbox_notifications'); }
        return ['pid' => $db->insert_query('posts', [
            'uid' => $this->data['uid'], 'tid' => $this->data['tid'], 'fid' => $this->data['fid'],
            'visible' => 1, 'message' => $db->escape_string($this->data['message'])
        ])];
    }
}
