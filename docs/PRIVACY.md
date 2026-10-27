# Data scope and API keys

[Polski](PRYWATNOSC.md)

Only the explicit question after `!ai` or `@AI`, plugin instructions and administrator response instructions are sent to the selected provider over HTTPS. The plugin does not attach usernames, topic titles, earlier posts, attachments, forum history, MyBB private messages or STT private conversations. Text that a member includes inside the question is still transmitted.

A source containing recognised `hide*`, `lock` or `private` MyCode tags is skipped. Other content-hiding plugins need a specific integration; do not enable protected areas based only on this filter. Allowed topics must pass forum, parent-forum, requester and bot permission checks. Member-only mode explicitly requires the ordinary Registered group (ID 2).

The queue stores source IDs, user ID, topic ID, a content hash, timestamps, status, attempts, a short error code and reply ID. It does not store a copy of the question or an API key. Completed metadata is removed after seven days when the worker runs. Replies remain normal forum/chat records.

## Key storage

Both key defaults in the distribution are empty. An administrator pastes their own keys into password fields in MyBB settings. Password fields mask the display; they do **not** encrypt the database or MyBB's generated `inc/settings.php`. Protect those files, database dumps and backups. Do not upload them to GitHub.

Optional environment overrides:
- `STTAI_GROQ_API_KEY`: Groq only.
- `STTAI_GEMINI_API_KEY`: Gemini only.
- `STTAI_API_KEY`: old Groq-only alias, used when the Groq-specific override is absent.

A provider-specific override takes precedence over the saved key. The two providers never share a credential fallback. Keys are sent in request headers, not URLs. Requests use fixed provider endpoints, TLS verification, no redirects, a timeout and a response-size bound.

## Provider account and member notice

Tell members that explicitly addressed questions are sent to the chosen external AI service. Provider terms, data processing and free-tier limits apply. Consult [Groq data policies](https://console.groq.com/docs/your-data) and [Gemini terms](https://ai.google.dev/gemini-api/terms), including rules that can differ by region or billing tier.

The plugin cannot inspect or enforce your billing plan. Free availability was checked on 27 October 2026 using [Groq limits](https://console.groq.com/docs/rate-limits) and [Gemini pricing](https://ai.google.dev/gemini-api/docs/pricing). Check the current account settings before enabling it.

Gemini's current terms require Paid Services for API clients made available to users in the EEA, Switzerland or the UK. Check eligibility before selecting Gemini for a public forum; this plugin does not enable billing on your behalf.
