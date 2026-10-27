#!/usr/bin/env python3
"""Build the credential-free, reproducible STT AI Assistant installation ZIP."""
import argparse
import hashlib
from pathlib import Path
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
VERSION = '1.0.0'
INSTALL_FILES = [
    'Upload/inc/plugins/sttai.php',
    'Upload/inc/plugins/sttai/core.php',
    'Upload/inc/plugins/sttai/i18n.php',
    'Upload/inc/plugins/sttai/settings.php',
    'Upload/inc/tasks/sttai.php',
    'Upload/inc/languages/english/sttai.lang.php',
    'Upload/inc/languages/polish/sttai.lang.php',
]
GUIDES = [
    'README.md', 'README.pl.md', 'INSTALL.md', 'INSTALACJA.md',
    'LICENSE.txt', 'CHANGELOG.md', 'RELEASE-1.0.0.md',
    'docs/PRIVACY.md', 'docs/PRYWATNOSC.md',
]
SECRET_PATTERNS = [
    rb'gsk_[A-Za-z0-9]{20,}',
    rb'AIza[0-9A-Za-z_-]{25,}',
    rb'gh[pousr]_[A-Za-z0-9]{20,}',
    rb'github_pat_[A-Za-z0-9_]{30,}',
    rb'-----BEGIN [A-Z ]*PRIVATE KEY-----',
]


def content(relative):
    path = ROOT / relative
    if path.is_symlink() or not path.resolve().is_relative_to(ROOT):
        raise SystemExit('Unsafe release path: ' + relative)
    data = path.read_bytes().replace(b'\r\n', b'\n')
    for pattern in SECRET_PATTERNS:
        if re.search(pattern, data):
            # Report the file only; never print the matched credential.
            raise SystemExit('Credential-like content detected: ' + relative)
    return data


def validate():
    found = {p.relative_to(ROOT).as_posix() for p in (ROOT / 'Upload').rglob('*') if p.is_file()}
    if found != set(INSTALL_FILES):
        raise SystemExit('Installation files differ from the release allowlist.')
    for relative in INSTALL_FILES + GUIDES:
        data = content(relative)
        if b'swirtvteam.info' in data or b'6048' in data:
            raise SystemExit('Installation-specific content detected: ' + relative)
    definitions = content('Upload/inc/plugins/sttai/settings.php').decode('utf-8')
    for name in ('api_key', 'gemini_api_key'):
        if not re.search(r"'" + name + r"'\s*=>\s*\['passwordbox',\s*''", definitions):
            raise SystemExit('API key default must be empty: ' + name)
    for name in ('enabled', 'bot_uid'):
        if not re.search(r"'" + name + r"'\s*=>\s*\['(?:yesno|numeric)',\s*'0'", definitions):
            raise SystemExit('Unsafe enabled/bot default: ' + name)
    if not re.search(r"'forums'\s*=>\s*\['forumselect',\s*''", definitions):
        raise SystemExit('Default forum allowlist must be empty.')
    # Scan all public source/test/build files, including the fake API fixtures.
    for path in ROOT.rglob('*'):
        relative = path.relative_to(ROOT)
        if any(part in {'.git', '__pycache__', 'dist'} for part in relative.parts):
            continue
        if path.is_file() and path.suffix in {'.php', '.py', '.md', '.txt', '.yml', '.yaml'}:
            content(relative.as_posix())
    # Check local documentation links without making any network requests.
    for path in ROOT.rglob('*.md'):
        if '.git' in path.parts:
            continue
        for target in re.findall(r'\]\(([^)]+)\)', path.read_text(encoding='utf-8')):
            if '://' in target or target.startswith('#'):
                continue
            if not (path.parent / target.split('#', 1)[0]).is_file():
                raise SystemExit('Broken local documentation link in ' + path.name)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=ROOT / 'dist')
    parser.add_argument('--check-only', action='store_true')
    args = parser.parse_args()
    validate()
    if args.check_only:
        print('Credential scan, clean defaults, file allowlist and documentation links: OK')
        return
    payload = {name: content(name) for name in sorted(INSTALL_FILES + GUIDES)}
    manifest = ''.join(hashlib.sha256(data).hexdigest() + '  ' + name + '\n' for name, data in payload.items())
    payload['SHA256SUMS.txt'] = manifest.encode('utf-8')
    args.output.mkdir(parents=True, exist_ok=True)
    archive = args.output / ('STT-AI-Assistant-' + VERSION + '.zip')
    with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as target:
        for name, data in payload.items():
            info = zipfile.ZipInfo(name, date_time=(2026, 10, 27, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            target.writestr(info, data)
    with zipfile.ZipFile(archive) as verify:
        if verify.testzip() is not None or set(verify.namelist()) != set(payload):
            raise SystemExit('ZIP integrity check failed.')
        for name, expected in payload.items():
            if verify.read(name) != expected:
                raise SystemExit('ZIP content mismatch: ' + name)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    (args.output / (archive.name + '.sha256')).write_text(digest + '  ' + archive.name + '\n', encoding='utf-8')
    print(f'Built and verified {archive.name}: {len(payload)} files, {archive.stat().st_size} bytes')
    print('SHA-256: ' + digest)


if __name__ == '__main__':
    main()
