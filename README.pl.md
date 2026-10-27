# STT AI Assistant dla MyBB

**Pytasz na czacie — odpowiedź jest na czacie. Pytasz w temacie — odpowiedź jest w tym temacie.**

Pomocnik AI dla MyBB 1.8, autorstwa **SWIRTV TEAM**, korzystający z własnego konta API Groq lub Google Gemini. Wtyczka, instrukcje i informacje o wydaniu są dostępne **po polsku i angielsku**.

[English](README.md) · [Pobierz stabilną wersję 1.0.0](https://github.com/Swir/STT_AI_Asystant_mybb/releases/tag/v1.0.0) · [Instalacja](INSTALACJA.md) · [Prywatność i klucze](docs/PRYWATNOSC.md)

## Jak korzystać

| Gdzie | Początek nowej wiadomości | Gdzie pojawi się odpowiedź |
| --- | --- | --- |
| Publiczny STTShoutbox | `!ai Jak rozwiązać ten problem?` | Na publicznym shoutboxie |
| Dozwolony temat forum | `@AI Jak rozwiązać ten problem?` | W tym samym temacie |

Wielkość liter nie ma znaczenia. Działają też `!ai:` i `@AI:`. Zwykłe wiadomości i edycje nie wywołują AI. Odpowiedź w temacie nie tworzy powiadomienia bota aktywności na shoutboxie.

## Instalacja i konfiguracja

1. Pobierz **STT-AI-Assistant-1.0.0.zip** z wydań. Wgraj **zawartość** katalogu `Upload` do głównego katalogu MyBB.
2. Wejdź w **ACP → Konfiguracja → Pluginy → STT AI Assistant → Zainstaluj i aktywuj**.
3. Kliknij **Konfiguruj STT AI**. Wklej własny klucz dostawcy, wybierz zwykłe konto bota, miejsca odpowiadania i włącz pomocnika.

W paczce **oba pola kluczy są puste**, AI jest wyłączone, a konto bota i dozwolone działy nie są ustawione. Nie ma eksportu bazy, konfiguracji serwera, treści użytkowników ani danych dostępowych. W [INSTALACJA.md](INSTALACJA.md) znajdziesz ustawienie kolejki i sprawdzenie pierwszej odpowiedzi.

## Możliwości

- Osobne klucze i modele Groq oraz Gemini, konfigurowane w ACP.
- Polskie i angielskie ustawienia, opisy, oznaczenia odpowiedzi oraz komunikaty zadania.
- Język odpowiedzi zgodny z językiem MyBB pytającego albo wymuszony polski/angielski.
- Opcjonalna integracja z STTShoutbox; odpowiedzi w tematach działają też bez shoutboxa.
- Wybór działów i grup, przerwa między pytaniami, limity dobowe, ograniczona kolejka i ponowne próby po chwilowym błędzie.
- Sprawdzanie pytania i uprawnień przed wysłaniem do AI oraz ponownie przed publikacją.
- Zwykły tekst odpowiedzi: HTML, MyCode i wzmianki generowane przez AI są neutralizowane.

Do wybranego dostawcy trafia tylko pytanie po komendzie i instrukcje administratora. Bez wcześniejszych postów, tytułu tematu, załączników i prywatnych wiadomości. [Szczegóły zakresu danych](docs/PRYWATNOSC.md).

## Wymagania

MyBB **1.8.x**, PHP **8.0+** z cURL i mbstring, MySQL/MariaDB oraz wychodzące połączenia HTTPS. Czat wymaga [STTShoutbox](https://github.com/Swir/STTShoutbox) **2.5.0+** ze zdarzeniem zapisu publicznej wiadomości; integrację sprawdzono względem 2.5.2. Inne shoutboxy wymagają osobnego adaptera.

Zadanie MyBB obsługuje do dwóch pytań na uruchomienie. Przy małym ruchu warto ustawić harmonogram hostingu. Odpowiedź pojawia się po obsłużeniu kolejki.

## Darmowe API

Można korzystać z dostępnych kont **Free** w [Groq](https://console.groq.com/docs/rate-limits) i [Google Gemini](https://ai.google.dev/gemini-api/docs/pricing). Wtyczka nie włącza płatności, nie sprawdza planu konta i nie gwarantuje darmowego użycia bez limitów. Klucz konta płatnego może generować opłaty. O limitach i dostępności decyduje dostawca.

Domyślne modele: `openai/gpt-oss-20b` dla Groq oraz `gemini-3.5-flash-lite` dla Gemini. Możesz je zmienić. Nie ma automatycznego przełączania dostawcy — pytanie trafia tylko do wybranej usługi.

Przy publicznym forum sprawdź także zasady użycia: [aktualne warunki Gemini](https://ai.google.dev/gemini-api/terms) wymagają usług płatnych przy udostępnianiu aplikacji korzystającej z API użytkownikom w EOG, Szwajcarii lub Wielkiej Brytanii. Sama dostępność darmowego limitu nie oznacza zgody na takie zastosowanie. Domyślnym dostawcą wtyczki jest Groq.

## Aktualizacja i sprawdzenie

Z **0.1.x lub 0.2.x**: podmień wszystkie pliki paczki i otwórz listę pluginów w ACP. Brakujące ustawienia dodadzą się bez nadpisania kluczy, własnych instrukcji i oczekujących pytań. **Nie odinstalowuj przy aktualizacji.** W starych konfiguracjach Gemini z jednym wspólnym polem trzeba przenieść klucz Gemini do osobnego pola.

Sprawdzono **100 przypadków działania i 38 przypadków instalacji**, w tym oba języki, rozdzielenie kluczy, uprawnienia, kolejkę i aktualizację. Podstawowa integracja została wcześniej uruchomiona na MyBB 1.8.41 z prawdziwymi kontami Groq i Gemini. Tłumaczenia i zmiany instalatora 1.0.0 sprawdzono lokalnie na PHP 8.5.12; nie sprawdzono każdej kombinacji PHP, MyBB i motywu. Szczegóły w [tests/README.md](tests/README.md).

AI może się mylić. Wtyczka nie naprawia serwera i nie przegląda internetu.

## Licencja i pomoc

[MIT](LICENSE.txt), SWIRTV TEAM. MyBB i dostawcy API to osobne projekty/usługi. Problemy zgłaszaj w [GitHub Issues](https://github.com/Swir/STT_AI_Asystant_mybb/issues), bez kluczy API i prywatnych danych forum.
