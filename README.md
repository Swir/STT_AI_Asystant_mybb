# STT AI Assistant for MyBB

**Ask in chat. Get the answer in chat. Ask in a topic. Get the answer in that topic.**

An AI assistant for MyBB 1.8 by **SWIRTV TEAM**, using your own Groq or Google Gemini API account. The plugin, setup instructions and release notes are available in **English and Polish**.

[Polski](README.pl.md) · [Download stable 1.0.0](https://github.com/Swir/STT_AI_Asystant_mybb/releases/tag/v1.0.0) · [Installation](INSTALL.md) · [Privacy and API keys](docs/PRIVACY.md)

## How members use it

| Where | Start a new message with | Where the answer appears |
| --- | --- | --- |
| Public STTShoutbox | `!ai How can I fix this problem?` | Public shoutbox |
| An allowed forum topic | `@AI How can I fix this problem?` | The same topic |

Commands are case-insensitive; `!ai:` and `@AI:` work too. Ordinary messages and edits do not trigger the assistant. Topic replies do not create STTShoutbox activity notifications.

## Install and configure

1. Download **STT-AI-Assistant-1.0.0.zip** from Releases. Upload the **contents** of its `Upload` directory into your MyBB root.
2. Open **Admin CP → Configuration → Plugins → STT AI Assistant → Install & Activate**.
3. Click **Configure STT AI**. Paste your own provider key, choose a normal bot account, select where it may reply, then enable the assistant.

The package ships with **both API fields empty**, AI disabled, no bot user ID and no allowed forum IDs. No database export, server configuration, member content or credentials are bundled. Follow [INSTALL.md](INSTALL.md) to enable the queue task and check your first reply.

## What is included

- Separate Groq and Gemini keys and models, configured entirely in the ACP.
- English and Polish settings, help text, reply labels and task messages.
- Automatic reply language based on the member's MyBB language, or a forced English/Polish choice.
- Optional STTShoutbox integration; topic replies also work without a shoutbox.
- Forum and group allowlists, cooldown, daily limits, a bounded queue and temporary-error retries.
- Source and permission checks before requesting an answer and again before publishing it.
- Plain-text output: generated HTML, MyCode and mentions are neutralised.

Only the question after the command and administrator instructions are sent to the selected provider. Previous posts, topic titles, attachments and private messages are not included. [Read the full data scope](docs/PRIVACY.md).

## Requirements

MyBB **1.8.x**, PHP **8.0+** with cURL and mbstring, MySQL/MariaDB, and outbound HTTPS. Public chat integration requires [STTShoutbox](https://github.com/Swir/STTShoutbox) **2.5.0+** with its public-message hook; integration was checked against 2.5.2. Other shoutbox plugins need their own adapter.

The MyBB task handles up to two questions per run. Low-traffic forums should schedule it through their host; replies are asynchronous.

## Free API plans

You can use eligible **Free** accounts from [Groq](https://console.groq.com/docs/rate-limits) and [Google Gemini](https://ai.google.dev/gemini-api/docs/pricing). The plugin does not enable billing, check your billing status or guarantee unlimited free usage. A paid API account can incur charges. Availability and limits are controlled by the provider.

Default models: `openai/gpt-oss-20b` for Groq and `gemini-3.5-flash-lite` for Gemini. They are configurable. There is no automatic provider fallback: a question goes to the selected service only.

For a public forum, also check the provider's usage terms: [Gemini's current terms](https://ai.google.dev/gemini-api/terms) require Paid Services when making API clients available to users in the EEA, Switzerland or the UK. An available free quota alone does not establish eligibility for that use. Groq is the plugin's default provider.

## Updating and testing

From **0.1.x or 0.2.x**: replace all package files and open the ACP plugin list. Missing settings are added without overwriting saved keys, custom instructions or pending questions. **Do not uninstall to update.** Older single-key Gemini setups must move their Gemini key to the dedicated Gemini field.

Release checks: **100 offline behaviour checks and 38 installer checks**, including both languages, credential isolation, permissions, queue handling and upgrades. The underlying integration was exercised on MyBB 1.8.41 with real Groq and Gemini accounts before this release. The 1.0.0 localisation and installer changes were checked offline on PHP 8.5.12; they have not been tested on every PHP/MyBB/theme combination. See [tests/README.md](tests/README.md).

AI can make mistakes and cannot repair your server or browse the web through this plugin.

## License and support

[MIT](LICENSE.txt), SWIRTV TEAM. MyBB and API providers are separate projects/services. Report reproducible issues in [GitHub Issues](https://github.com/Swir/STT_AI_Asystant_mybb/issues), without API keys or private forum data.
