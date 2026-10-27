# Offline checks / Testy lokalne

No real API calls, credentials or hosting access are needed. The behaviour test replaces cURL with a simulated transport and uses an in-memory SQLite adapter. The installer uses a temporary package copy and a database double. These checks do not replace testing on a real MyBB/MySQL installation.

Nie potrzeba prawdziwych kluczy, API ani hostingu. Test działania zastępuje połączenia odpowiedziami testowymi i używa SQLite w pamięci. Test instalacji pracuje na tymczasowej kopii paczki. Nie zastępuje to sprawdzenia prawdziwej instalacji MyBB/MySQL.

## Run

Requires PHP CLI, mbstring and PDO SQLite. Behaviour tests must run **without cURL loaded**; the script refuses to run otherwise. Installer tests require cURL, but never call it.

```sh
php -n -d extension=mbstring -d extension=pdo_sqlite tests/run.php
php -n -d extension=curl -d extension=mbstring -d extension=pdo_sqlite tests/installer.php
```

On systems where PDO is a shared extension, add `-d extension=pdo` before `pdo_sqlite`. On Windows, set `extension_dir` to the PHP extension directory. You can choose a writable temporary directory using `-d sys_temp_dir=/path/to/temp`.

Expected results: **100 behaviour checks**, **38 installer checks**. Tests ran on PHP 8.5.12. PHP 8.0 is the declared runtime minimum, not a claim that every intermediate PHP release was tested.

Spodziewany wynik: **100 testów działania**, **38 testów instalatora**. Sprawdzono na PHP 8.5.12. PHP 8.0 jest minimalnym wymaganiem kodu, a nie deklaracją sprawdzenia każdej wersji pośredniej.

## Package

```sh
python tools/build_release.py --output /path/to/output
```

Only the seven installation files and the public guides/license are packaged. The builder checks credential patterns and defaults and writes per-file checksums plus an archive checksum. Test fixtures and repository metadata are excluded.
