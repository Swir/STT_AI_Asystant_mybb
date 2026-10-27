# STT AI Assistant 1.0.0 — Stable / Stabilna

## English

First stable public release for MyBB 1.8, with English and Polish configuration and documentation.

- `!ai question` in public STTShoutbox → reply in that shoutbox.
- `@AI question` at the beginning of a new forum post → reply in the same topic, without a shoutbox notification.
- Groq or Google Gemini, with separate keys and models entered in the ACP.
- Automatic or fixed English/Polish replies, permission checks, quotas and a scheduled queue.
- **Clean package: empty API keys, AI disabled, no forum-specific settings or member data.**

Download **STT-AI-Assistant-1.0.0.zip**, upload the contents of `Upload`, and follow `INSTALL.md`. Requires MyBB 1.8.x, PHP 8.0+, cURL, mbstring and MySQL/MariaDB. Public chat additionally needs STTShoutbox 2.5.0+.

Upgrading from 0.1.x/0.2.x: replace all files and open the ACP plugin list. Existing keys and settings are preserved; do not uninstall. Early single-key Gemini configurations must use the dedicated Gemini field.

Validation: 100 offline behaviour checks and 38 installer checks passed on PHP 8.5.12. Free API availability depends on your provider/account; billing is not enabled or enforced by this plugin.

## Polski

Pierwsze stabilne wydanie publiczne dla MyBB 1.8, z konfiguracją i dokumentacją po polsku i angielsku.

- `!ai pytanie` na publicznym STTShoutboxie → odpowiedź na tym shoutboxie.
- `@AI pytanie` na początku nowego posta → odpowiedź w tym samym temacie, bez powiadomienia na shoutboxie.
- Groq albo Google Gemini, osobne klucze i modele wpisywane w ACP.
- Automatyczny lub wybrany język odpowiedzi, uprawnienia, limity i kolejka zadania.
- **Czysta paczka: puste klucze API, AI wyłączone, bez ustawień konkretnego forum i danych użytkowników.**

Pobierz **STT-AI-Assistant-1.0.0.zip**, wgraj zawartość `Upload` i przeczytaj `INSTALACJA.md`. Wymagane MyBB 1.8.x, PHP 8.0+, cURL, mbstring i MySQL/MariaDB. Publiczny czat dodatkowo wymaga STTShoutbox 2.5.0+.

Aktualizacja z 0.1.x/0.2.x: podmień wszystkie pliki i otwórz listę pluginów w ACP. Klucze i ustawienia pozostają; nie odinstalowuj. Bardzo stare konfiguracje Gemini z jednym polem muszą korzystać z osobnego pola Gemini.

Sprawdzenie: 100 testów działania i 38 testów instalatora na PHP 8.5.12. Darmowe użycie zależy od konta i dostawcy; wtyczka nie włącza ani nie kontroluje rozliczeń.
