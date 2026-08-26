# Test harnesses

Fourteen harnesses that hold down what this plugin already does. They need no
web server, no database and no Zen Cart install — Zen Cart's own functions are
stubbed in `_bootstrap.php`, and the database is a recording fake that hands
back whatever rows a test asks it to.

None of this ships. `tools/build_package.ps1` copies only the plugin, the docs
and `readme.html` into `dist/`, and `readme_check.php` asserts that this
directory never turns up in a release.

## Running them

```bash
pwsh tests/run_all.ps1
```

Every harness against every PHP version it can find:

```
7.4   ..............
8.0   ..............
...
ALL GREEN (7 x 14 runs)
```

One harness while you work on it:

```bash
pwsh tests/run_all.ps1 -Only storefront
```

Or directly, which prints every assertion rather than a dot:

```bash
php tests/storefront_check.php
```

Exit code is 0 only when everything passed, so `run_all.ps1` can gate a release.

## PHP versions

The plugin claims **PHP 7.4 through 8.5 from a single codebase**, and that claim
is worth only as much as the checking behind it — the syntax that breaks is
rarely the syntax you are looking at.

`run_all.ps1` looks for builds in `tests/php/php74\`, `php80\` … `php85\`, then
in `$env:SCF_PHP_DIR`, and always tries whatever `php` is on `PATH`. Windows
NTS zips from the [PHP release archives](https://windows.php.net/downloads/releases/archives/)
need no installation — unzip each into its own directory. Whatever is missing is
named in the summary rather than passed over silently.

`tests/php/` is gitignored.

## What each one covers

| harness | holds down |
|---|---|
| `manifest_check` | the Plugin Manager panel, the `varchar(64)` name limit, balanced description markup, the forum link |
| `pluginname_check` | that **Mod Not Turned On** clears itself — including on the three releases whose Plugin Manager never refreshes the name |
| `installer_check` | that an upgrade never overwrites a setting the store owner chose, and never drops the subscriber table unasked |
| `audience_check` | the two Newsletter Manager audiences, and the `parsed_query_string()` traps their SQL has to survive |
| `storefront_check` | link building, what it must refuse, the icon row, the form, and the observer's double-fire guard |
| `subscribe_flow_check` | subscribe, confirm, unsubscribe and invitation acceptance, run against the fake database |
| `subscribe_off_check` | the newsletter switched off — including that an unsubscribe link still works |
| `invite_check` | the registration invitation: pending account, re-send, and the token rules |
| `import_check` | the CSV import: heading aliases, size limits, and what it refuses |
| `email_check` | that every message carries an unsubscribe link, and that no template can break `sprintf()` |
| `header_image_check` | the optional header image — upload validation, and that the store's own `email/header.jpg` is never touched |
| `adminpage_check` | landmarks, table semantics, the 1.2rem floor, and contrast computed rather than eyeballed |
| `readme_check` | documentation, spelling, one version number everywhere, and that `dist/` and the release zip match the source |
| `security_scan` | the checks a Zen Cart reviewer runs, before they run them |

## Writing more

`check($label, $condition)` — in that order, and enforced at runtime. A reversed
call would report `ok` forever while testing nothing, which has happened twice
and is why the guard is there.

Assert on behaviour where the code can be called, and on source only where it
cannot. A harness that stubs away the thing it is testing passes beautifully and
tells you nothing: `_bootstrap.php` draws real form markup for exactly that
reason, because a stub returning `''` let a check on `type="email"` pass against
an input that was never drawn.

Two harnesses re-run themselves in a child process, because a PHP constant
cannot be redefined and a `static` cache cannot be cleared. `scf_quiet()` keeps
the child from repeating every assertion on its way to the one case it exists to
reach.
