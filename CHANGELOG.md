# Changelog / Historia zmian

## 1.0.0 — 2026-10-27

First stable public release / Pierwsze stabilne wydanie publiczne.

- English and Polish ACP settings, reply labels and task messages.
- Automatic or fixed English/Polish reply language.
- Separate provider keys, models and environment overrides; no cross-provider credential fallback.
- Gemini default: `gemini-3.5-flash-lite`; Groq default: `openai/gpt-oss-20b`.
- Explicit public-chat and topic commands with replies confined to their source channel.
- Permission checks, bounded queue, quotas, retries and source-change cancellation.
- Empty credentials and disabled defaults; no installation-specific account, forum or hosting data.
- Bilingual documentation and repeatable release packaging.
- 100 offline behaviour checks and 38 installer lifecycle checks.

Polski: ustawienia i oznaczenia odpowiedzi w dwóch językach, wybór języka odpowiedzi, osobne klucze i modele dostawców, pusta konfiguracja startowa, ograniczenia i kolejka oraz instrukcje instalacji po polsku i angielsku. Aktualizacja z wersji rozwojowych zachowuje ustawienia.

## Before 1.0.0 / Przed 1.0.0

Versions 0.1.x–0.2.x were development builds. Their core chat/topic integration forms the basis of this release. Site-specific configuration is not part of the public distribution.

Wersje 0.1.x–0.2.x były wydaniami rozwojowymi. Ich integracja czatu i tematów stanowi podstawę tego wydania. Konfiguracja konkretnego forum nie jest częścią publicznej paczki.
