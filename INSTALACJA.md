# Instalacja — STT AI Assistant 1.0.0

[English](INSTALL.md) · [README](README.pl.md)

## Przed wgraniem

Zrób kopię plików i bazy forum. Wymagane: MyBB 1.8.x, PHP 8.0+, cURL, mbstring, MySQL/MariaDB oraz połączenia HTTPS do wybranego API. Forum i ACP powinny korzystać z HTTPS.

Odpowiedzi w tematach nie wymagają shoutboxa. Publiczny czat wymaga STTShoutbox 2.5.0+ ze zdarzeniem `sttshoutbox_message_saved`. Sam STTShoutbox nie jest częścią tej paczki.

## Wgranie i aktywacja

1. Rozpakuj ZIP wydania.
2. Wgraj zawartość `Upload` do katalogu MyBB, obok `index.php`. Scal katalog `inc`; nie usuwaj jego pozostałej zawartości.
3. **ACP → Konfiguracja → Pluginy → STT AI Assistant → Zainstaluj i aktywuj**.

Paczka zawiera siedem plików PHP: główną wtyczkę, jej logikę, definicje ustawień, obsługę języków, dwa tłumaczenia i zadanie kolejki. Nie trzeba instalować JavaScriptu ani bibliotek dostawcy.

## Ustawienia

Kliknij **Konfiguruj STT AI** przy wtyczce albo wejdź w **Konfiguracja → Ustawienia → STT AI Assistant**.

1. Pobierz własny [klucz Groq](https://console.groq.com/keys) lub [klucz Gemini](https://aistudio.google.com/api-keys). Jeśli chcesz korzystać bez opłat, sprawdź plan konta/projektu i brak płatnego rozliczania.
2. Wklej klucz w osobne pole danego dostawcy i wybierz dostawcę. Możesz zachować oba klucze; używany jest tylko wybrany.
3. Utwórz lub wybierz wyraźnie nazwane konto bota. Podaj jego numer UID z adresu profilu. Wystarczy zwykłe konto z prawem pisania w wybranych miejscach; bot nie potrzebuje administratora.
4. Wybierz shoutbox, tematy albo oba miejsca. Dla tematów zaznacz konkretne działy. Pusta lista oznacza brak odpowiedzi w tematach.
5. Domyślnie obsługiwane są działy czytelne bez logowania. Możesz dopuścić zwykłe działy dla zalogowanych. Nadal wymagany jest dostęp standardowej grupy Registered (ID 2); działy z ograniczonym dostępem administracji/VIP, działy lub nadrzędne kategorie z hasłem oraz zamknięte tematy są pomijane.
6. Wybierz język automatyczny, polski lub angielski. Opisy ustawień używają języka ACP; paczka nie instaluje pełnego polskiego tłumaczenia MyBB.
7. Włącz pomocnika i zapisz.

W **ustawieniach zaawansowanych** są grupy, limity, przerwa między pytaniami, modele i instrukcje odpowiedzi. Puste modele oznaczają `openai/gpt-oss-20b` lub `gemini-3.5-flash-lite`. Brak klucza Gemini nigdy nie powoduje użycia klucza Groq. Nie ma automatycznego przełączania dostawcy.

## Kolejka i pierwsze pytanie

W **Narzędzia i konserwacja → Menedżer zadań** sprawdź, czy zadanie **STT AI — kolejka** jest aktywne. MyBB uruchamia zadania przy ruchu na forum. Dla regularnego działania ustaw z hostingiem:

```text
/sciezka/php /sciezka/forum/task.php ID_ZADANIA
```

Dopasuj obie ścieżki i ID do swojej instalacji. Możesz uruchamiać zadanie co minutę. Paczka nie dodaje samodzielnie wpisu crona.

Napisz `!ai Jak sprawdzić błąd przeglądarki?` na publicznym czacie albo nowy post zaczynający się od `@AI` w dozwolonym temacie. Jedno uruchomienie zadania obsługuje do dwóch pytań. Sprawdź, czy odpowiedź pojawiła się w tym samym miejscu. Odpowiedź w temacie nie powinna pojawić się na shoutboxie.

Domyślnie: 60 sekund przerwy, 10 pytań użytkownika i 100 pytań forum w ostatnich 24 godzinach, 150 prób API oraz 30 oczekujących pytań. Błędne wywołania i przekroczenia lokalnych limitów są pomijane bez komunikatu. Chwilowe błędy mają do trzech prób; limit dostawcy może opóźnić całą kolejkę. Oczekujące pytania wygasają po 24 godzinach, a zakończone wpisy techniczne są sprzątane po siedmiu dniach.

## Aktualizacja z 0.1.x / 0.2.x

Zrób kopię, podmień wszystkie pliki z `Upload` i otwórz listę pluginów w ACP. Wtyczka doda brakujące ustawienia bez dezaktywacji i anulowania kolejki. Zachowa klucze, wybrane modele i własne instrukcje. **Nie odinstalowuj przy aktualizacji.**

Jeżeli bardzo wczesna konfiguracja Gemini korzystała ze wspólnych pól klucza/modelu, przenieś wartości do osobnych pól Gemini. Zmienna `STTAI_API_KEY` jest od teraz starszym aliasem tylko dla Groq. Sprawdź własne instrukcje, jeśli wymagają polskich odpowiedzi, a chcesz teraz używać angielskiego.

## Gdy bot nie odpowiada

Sprawdź włączenie AI, dostawcę i klucz, UID i uprawnienia bota, grupy i działy, komendę na początku **nowej** wiadomości, limity i harmonogram zadania. Edycja wiadomości nie dodaje pytania do kolejki. Usunięte lub zmienione pytania są anulowane przed publikacją.

Logi zadania zawierają liczniki i krótkie kody błędów, bez odpowiedzi dostawcy, pytań i kluczy:

| Kod | Znaczenie / co sprawdzić |
| --- | --- |
| `api_http_401`, `api_http_403` | Klucz wybranego dostawcy i dostęp do API. |
| `api_http_404` | Nazwa i dostępność modelu. |
| `api_limit` | Limit dostawcy; poczekaj lub sprawdź konto. |
| `api_unavailable` | Chwilowy błąd dostawcy. |
| `api_network_N` | Błąd połączenia cURL; 28 oznacza przekroczony czas. |
| `api_incomplete` | Model nie zwrócił ukończonej odpowiedzi. |
| `post_validation` | MyBB odrzuciło post bota; sprawdź uprawnienia i moderację. |
| `source_changed` | Zmieniło się pytanie lub jego dostępność. |
| `interrupted` | Zadanie zostało przerwane; nie jest powtarzane, aby uniknąć duplikatów. |

## Wyłączanie i odinstalowanie

Dezaktywacja wyłącza AI i zadanie oraz anuluje oczekujące pytania. Odinstalowanie usuwa ustawienia, zadanie i kolejkę; opublikowane odpowiedzi pozostają. Osobny wpis crona usuń w hostingu. Informacje o kluczach i danych są w [opisie prywatności](docs/PRYWATNOSC.md).
