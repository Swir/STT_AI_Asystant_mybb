# Installation — STT AI Assistant 1.0.0

[Polski](INSTALACJA.md) · [README](README.md)

## Before uploading

Back up your forum files and database. Requirements: MyBB 1.8.x, PHP 8.0+, cURL, mbstring, MySQL/MariaDB and outbound HTTPS to your chosen provider. Use HTTPS for your forum and ACP.

Topic support does not require a shoutbox. Public chat support requires STTShoutbox 2.5.0+ with the `sttshoutbox_message_saved` hook. STTShoutbox itself is not included.

## Upload and activate

1. Extract the release ZIP.
2. Upload the contents of `Upload` into the MyBB root, beside `index.php`. Merge the `inc` directory; do not replace or delete its other contents.
3. Open **Admin CP → Configuration → Plugins → STT AI Assistant → Install & Activate**.

The package installs seven PHP files: the main plugin, its core, settings definitions, language helper, two language packs, and the queue task. There are no JavaScript assets or vendor SDKs to install.

## Configure

Click **Configure STT AI** beside the plugin, or open **Configuration → Settings → STT AI Assistant**.

1. Get your own [Groq key](https://console.groq.com/keys) or [Gemini key](https://aistudio.google.com/api-keys). To stay on a free plan, verify that your provider project/account is not using paid billing.
2. Paste the key into its dedicated password field and select that provider. Both keys can be saved; only the selected provider is used.
3. Create or choose a clearly named bot account. Enter its numeric UID from its profile URL. Use a normal member account with permission to write in the chosen places; administrator access is unnecessary.
4. Enable the shoutbox, topics, or both. For topics, select specific allowed forums. An empty forum list means no topic replies.
5. Publicly readable forums are the default. If needed, explicitly allow ordinary member-only forums. This still requires access for MyBB's standard Registered group (ID 2); restricted staff/VIP forums, password-protected ancestors and closed topics are excluded.
6. Choose automatic, English or Polish reply language. The ACP labels follow the ACP language; this package does not install MyBB's full Polish translation.
7. Enable the assistant and save.

**Advanced settings** contains user groups, quotas, cooldown, models and response instructions. Leaving model fields empty selects `openai/gpt-oss-20b` or `gemini-3.5-flash-lite`. Keys are separate: a missing Gemini key never reuses a Groq key. There is no automatic provider fallback.

## Queue and first question

In **Tools & Maintenance → Task Manager**, confirm that **STT AI — queue** is enabled. MyBB runs tasks with forum traffic. For reliable scheduling, ask your host to run:

```text
/path/to/php /path/to/forum/task.php TASK_ID
```

Replace both paths and `TASK_ID` with your installation's values. A one-minute schedule is suitable; no cron entry is installed by this package.

Post `!ai How can I troubleshoot a browser error?` in public chat, or a new post beginning `@AI` in an allowed topic. The task handles up to two questions per run. Check that the reply appears in the same place. A bot reply in a topic should not appear in the public shoutbox.

Defaults: 60-second user cooldown, 10 questions per user and 100 per forum in a rolling 24 hours, 150 API attempts, and 30 pending questions. Rejected triggers/limits are skipped silently. Temporary provider errors receive up to three attempts; provider limits can delay the whole queue. Pending jobs expire after 24 hours, and finished metadata is pruned after seven days.

## Upgrade from 0.1.x / 0.2.x

Back up, replace all files from `Upload`, and open the ACP plugin list. The plugin adds the new settings without deactivating or cancelling the queue. Existing keys, model choices and custom instructions are preserved. **Do not uninstall.**

If an early Gemini configuration used the shared key/model fields, move those values into the dedicated Gemini fields. `STTAI_API_KEY` is now a Groq-only legacy environment alias. Review old custom instructions if they explicitly request Polish but you now want English.

## Troubleshooting

Check the enabled switch, provider/key, bot UID and permissions, group/forum allowlists, command at the beginning of a **new** message, quotas and task schedule. Message edits do not create jobs. Deleted or changed questions are cancelled before publication.

Task logs contain status counts and short error codes, not provider response bodies, questions or keys:

| Code | Meaning / next step |
| --- | --- |
| `api_http_401`, `api_http_403` | Check the selected provider's key and API access. |
| `api_http_404` | Check the model name and availability. |
| `api_limit` | Provider quota reached; wait or check your account limits. |
| `api_unavailable` | Temporary provider failure. |
| `api_network_N` | cURL connection error; 28 is a timeout. |
| `api_incomplete` | Provider did not return a complete answer. |
| `post_validation` | MyBB rejected the bot's post; check permissions and moderation rules. |
| `source_changed` | The question or its visibility changed. |
| `interrupted` | A previous task stopped unexpectedly; not replayed to avoid duplicate replies. |

## Disable or uninstall

Deactivate to disable AI and its task and cancel pending jobs. Uninstall removes plugin settings, task and queue; published answers remain. Remove any host cron entry separately. See [Privacy](docs/PRIVACY.md) for API key storage and data scope.
