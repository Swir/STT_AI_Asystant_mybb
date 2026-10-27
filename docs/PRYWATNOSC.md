# Zakres danych i klucze API

[English](PRIVACY.md)

Do wybranego dostawcy przez HTTPS trafia tylko pytanie po `!ai` lub `@AI`, instrukcje wtyczki i instrukcje odpowiedzi administratora. Wtyczka nie dołącza nicków, tytułów tematów, wcześniejszych postów, załączników, historii forum, PW MyBB ani prywatnych rozmów STT. Informacje wpisane przez użytkownika w samym pytaniu nadal zostaną przesłane.

Wiadomości ze znanymi znacznikami MyCode `hide*`, `lock` lub `private` są pomijane. Inne systemy ukrywania treści wymagają osobnego dopasowania — sam ten filtr nie wystarcza do udostępniania obszarów chronionych. Sprawdzane są uprawnienia działu i jego rodziców, pytającego oraz bota. Tryb dla zalogowanych wymaga dostępu zwykłej grupy Registered (ID 2).

Kolejka przechowuje identyfikatory źródła, użytkownika i tematu, skrót treści, czasy, stan, liczbę prób, krótki kod błędu i identyfikator odpowiedzi. Bez kopii pytania i bez klucza API. Zakończone wpisy techniczne są usuwane po siedmiu dniach podczas pracy zadania. Odpowiedzi pozostają zwykłymi wpisami forum/czatu.

## Przechowywanie kluczy

W paczce oba klucze są puste. Administrator wkleja swoje klucze w pola hasłowe ustawień MyBB. Maskowanie pola **nie oznacza szyfrowania** bazy ani generowanego przez MyBB pliku `inc/settings.php`. Chroń te pliki, eksporty bazy i kopie zapasowe. Nie wysyłaj ich na GitHuba.

Opcjonalne zmienne środowiskowe:
- `STTAI_GROQ_API_KEY`: tylko Groq.
- `STTAI_GEMINI_API_KEY`: tylko Gemini.
- `STTAI_API_KEY`: starszy alias tylko dla Groq, gdy nie ma osobnej zmiennej Groq.

Zmienna dostawcy ma pierwszeństwo przed zapisanym kluczem. Klucze nie zastępują się między dostawcami. Trafiają do nagłówków żądania, a nie adresów URL. Połączenia mają stałe adresy dostawców, weryfikację TLS, wyłączone przekierowania, limit czasu i rozmiaru odpowiedzi.

## Konto dostawcy i informacja dla użytkowników

Poinformuj użytkowników, że pytania skierowane komendą do AI są wysyłane do wybranej usługi zewnętrznej. Obowiązują zasady przetwarzania i limity dostawcy. Sprawdź [zasady danych Groq](https://console.groq.com/docs/your-data) i [warunki Gemini](https://ai.google.dev/gemini-api/terms), w tym różnice zależne od regionu i planu.

Wtyczka nie sprawdza ani nie narzuca planu rozliczeń. Dostępność darmowych planów sprawdzono 27 października 2026 na podstawie [limitów Groq](https://console.groq.com/docs/rate-limits) i [cennika Gemini](https://ai.google.dev/gemini-api/docs/pricing). Przed włączeniem sprawdź aktualne ustawienia konta.

Aktualne warunki Gemini wymagają usług płatnych dla aplikacji API udostępnianych użytkownikom w EOG, Szwajcarii lub Wielkiej Brytanii. Sprawdź możliwość danego zastosowania przed wybraniem Gemini na publicznym forum. Wtyczka nie włącza płatności za Ciebie.
