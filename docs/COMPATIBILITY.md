# Compatibility notes: Zen Cart v1.5.8 → v3.0.0, PHP 7.4 → 8.5

This plugin runs unmodified across six years of Zen Cart releases. That is not
free — it came from choosing, at each decision point, the mechanism that exists
in *every* supported version rather than the newest and nicest one.

This document records those choices so a future maintainer knows which ones are
load-bearing.

## How it is verified

Not by assertion. Three checks run against the real thing:

| Check | Method |
|---|---|
| **Zen Cart API** | Every core symbol the plugin uses — functions, constants, notifiers, classes, installer methods — is extracted from the source and looked up in all six release branches (`v158`, `2.0`, `2.1`, `2.2`, `2.3`, `master`) with `git grep`. 58 symbols, all present. |
| **PHP** | Lint plus the full harness suite on 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5. Clean, with zero diagnostics at `E_ALL`. |
| **Accessibility** | WCAG 2.1 AA checks over the rendered storefront block and the HTML readme, including contrast computed from the declared colours. |

A symbol that exists in `master` but not `v158` is exactly the failure mode
that produces a plugin which works for the developer and fatals for half the
installed base — so that check fails the build rather than warning.

---

## What we rely on

### Observer registration: `auto.*.php` + `zcObserver*`

`includes/init_includes/init_observers.php` scans each installed plugin's
`catalog/includes/classes/observers/` directory for files matching
`auto.*.php`, includes them, and instantiates
`'zcObserver' . base::camelize($name, true)`.

This convention is identical in v1.5.8 and v3.0.0-dev. v3.0.0 additionally
supports an `AutoSomething.php` / PSR-4 form, but the legacy pairing still
works, so that is what the plugin uses:

```
auto.social_contact_footer.php  →  class zcObserverSocialContactFooter
```

### Notifier dispatch: `updateNotifyXxx()`

`Zencart\Traits\NotifierManager::notify()` looks for, in order, a snake_case
method named after the event, then `update` + CamelCased event name, then a
generic `update()`. This resolution order is byte-for-byte the same in v1.5.8
and v3.0.0-dev.

### Three notifiers, one of them optional

| Notifier | Fires | Available |
|---|---|---|
| `NOTIFY_HTML_HEAD_END` | end of `<head>` | all supported versions |
| `NOTIFY_FOOTER_AFTER_NAVSUPP` | inside `tpl_footer.php` | **v2.1 and later** |
| `NOTIFY_FOOTER_END` | `tpl_main_page.php`, before `</body>` | all supported versions |

The observer attaches to both footer notifiers and renders on whichever fires
first. That gives good placement on modern carts and a guaranteed fallback on
v1.5.8 and v2.0 — and on any version where the store runs a custom template
whose `tpl_footer.php` predates the newer notifier.

> An earlier draft of this file said `NOTIFY_FOOTER_AFTER_NAVSUPP` arrived in
> v2.2.0, taken from a `@version` comment in the template file. The branch scan
> shows it in v2.1 as well. Trust the scan, not the file headers.

The compatibility checker knows this notifier is optional, but only accepts it
on condition that `NOTIFY_FOOTER_END` is present in every branch — so removing
that guard would fail the check rather than pass quietly.

### Stylesheet and script, linked not inlined

`NOTIFY_HTML_HEAD_END` exists in every supported version, and Zen Cart's
shipped `zc_plugins/.htaccess` denies everything then explicitly re-allows a
list that includes `css` and `js`. So the plugin can link real, cacheable
assets from inside its own directory on every release:

```
catalog/includes/templates/template_default/css/social_contact_footer.css
catalog/includes/templates/template_default/jscript/social_contact_footer.js
```

Both check the active template first, so a store can override either by
dropping its own copy into `includes/templates/YOUR_TEMPLATE/css/` or
`/jscript/`.

The alternative — `linkCatalogStylesheet()` from the `InteractsWithPlugins`
trait — is v2.x-only and would have needed a version branch.

### Language files: `lang.*.php` returning an array

v1.5.8 introduced the array-returning language format alongside the legacy
`define()` files, and both loaders have shipped in every release since. Plugin
`extra_definitions` directories are scanned on both the catalog and admin
sides.

### `admin/includes/extra_datafiles/` for `FILENAME_*` and `TABLE_*`

v2.x and v3.0 prefer a `filenames.php` (and `database_tables.php`) in the
plugin root. Neither is read by v1.5.8. `extra_datafiles` is read by *all* of
them, so that is where the constants live, wrapped in `defined()` guards.

### Admin page routing: `index.php?cmd=<page>`

`admin/index.php` maps `cmd` to a page file, checking the store's own `admin/`
directory first and then each installed plugin's via
`FileSystem::findPluginAdminPage()`. Unchanged from v1.5.8 to v3.0.0-dev.

Note this only resolves for *installed* plugins — which is why the Read Me link
in Plugin Manager points at the `.html` file directly rather than at an admin
page. It has to work before installation.

### Autoload breakpoint 178

The plugin's init script runs at 178: after `init_observers.php` (175) and
before `init_header.php` (180). Sessions (70), language files (110),
`$messageStack` (130) and the general function library (60) are all in place by
then, and the subscription POST is handled — including any redirect — before a
page module has produced output.

### `pluginDescription` renders as raw HTML in Plugin Manager

Plugin Manager's info box — the one carrying the Install / Uninstall / Disable
buttons — is built by `PluginManagerController::processDefaultAction()`, and in
both branches it emits the description unescaped. The value comes from
`plugin_control.description`, a `TEXT` column, and it is **not** one of the
columns in the plugin list table.

So markup placed in `pluginDescription` appears exactly once, in the right
panel, with no core change and no notifier needed — there is no notifier fired
for that panel, which is why this route is used. The plugin puts its Read Me
and GitHub buttons there.

`tableBlock()` wraps every `setBoxContent()` item in its own
`<div class="row">`. The core adds each button as a separate item, which is why
they stack; the whole description is a single item, so two inline-block buttons
within it sit side by side.

The `github_repo` and `changelog` manifest keys are stored but never rendered
anywhere by the core in either branch — do not rely on them to surface
anything.

### `name` and `description` are NOT refreshed on half the supported range

This one cost a working feature before it was found, so it is worth stating
plainly. `PluginManager::updatePluginControl()` writes the manifest values, but
what happens to an **existing** row differs by release:

| Releases | Call | Effect on a row that already exists |
|---|---|---|
| v2.2, v2.3, v3.0 | `upsertMany()` | SQL ends `ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), …` — **refreshed every scan** |
| v1.5.8, v2.0, v2.1 | Eloquent `upsert($values, ['id'], ['infs'])` | the third argument is the list of columns to update on conflict, and it holds only `infs` — **`name` and `description` are frozen at first insert** |

So on three of the six supported releases, anything derived from `manifest.php`
is captured once, when Plugin Manager first sees the plugin, and never updated
again no matter how many times the page is opened.

Two consequences the plugin has to live with:

- **State-dependent text cannot live in the manifest.** The "Mod Not Turned On"
  notice would be permanent on those releases: the owner switches the plugin on
  and Plugin Manager keeps saying it is off, forever. The notice is therefore
  written to `plugin_control.name` directly by
  `admin/includes/functions/extra_functions/social_contact_footer_admin.php`,
  which every release loads on every admin page, and cleared by the uninstaller
  — because once uninstalled, nothing of ours loads to fix it.
- **The Read Me URL does not self-correct on v1.5.8–v2.1.** It is built from
  `DIR_WS_CATALOG` at scan time, so a store that later moves keeps the old path
  in `description` until the plugin is re-installed. Uninstall + install fixes
  it; nothing else will.

### `pluginName` is varchar(64), and the plugin list echoes it unescaped

The scan writes `pluginName` straight into `plugin_control.name`, which is
`varchar(64)` in every supported release. The plugin list then echoes that
column with no escaping — `$column['value']` in v1.5.8's `table_view.php`, and
the same in v2.2+'s `plugin_manager.php`.

Both halves matter. Unescaped means markup in the name *would* render; 64
characters means a value carrying markup can be **truncated mid-tag**, silently
on a normal server and fatally under MySQL `STRICT_ALL_TABLES`. That is why the
"Mod Not Turned On" notice appends **plain text** to the name (41 characters in
all) and puts its styled banner in `pluginDescription` instead, which is a
`TEXT` column with no such limit.

The row's identity is `unique_key`, not the name — `colKey` in the table
definition — so varying the name per state does not disturb row selection or
the install state.

### A plugin can style a core admin page, conditionally

`admin/includes/admin_html_head.php` walks every installed plugin and, for the
page being viewed (`basename($PHP_SELF, '.php')`), pulls in from that plugin's
`admin/includes/css/`:

| File | Treatment |
|---|---|
| `<page>.css` | linked |
| `<page>_*.css` | linked |
| `<page>_*.php` | **required** — executed, and whatever it prints lands in `<head>` |

Identical in v1.5.8, v2.0, v2.1, v2.2, v2.3 and v3.0.

The `.php` form is what makes conditional styling possible. A plain
`configuration.css` would restyle *every* configuration group in the store —
someone else's settings page changed as a side effect of installing this plugin.
`configuration_social_contact_footer.php` reads `gID`, compares it with this
plugin's own group, and prints nothing at all otherwise.

### Configuration constants are available to `manifest.php`

`admin/plugin_manager.php` requires `application_top.php` before calling
`$pluginManager->inspectAndUpdate()`, and `application_top` has already turned
every `configuration` row into a constant. A manifest can therefore read the
plugin's own settings — which is how the notice knows whether `SCF_STATUS` is
`'true'`.

Guard with `defined()`. The constant does not exist when the plugin is not
installed, and the manifest is read for *every* plugin on the filesystem,
installed or not.

**The banner's colours are constrained, not chosen.** `#D9534F` reaches only
3.96:1 against pure white — the lightest background there is — so it can never
meet WCAG's 4.5:1 for ordinary body text. It clears 3:1 easily, which is the
threshold for large text and for non-text elements, so it is used for the
headline (19px bold, which is what makes it *large* text) and the left rule,
while the explanatory sentence uses `#843534` at 7.54:1.

The headline size is declared in `px` deliberately. An `em` value would inherit
whatever base size the admin theme sets, and a 13px base would silently drop it
below the 18.66px that makes bold text "large" — taking the contrast out of
compliance without anything visibly changing. `scf_manifest.php` computes all
three ratios rather than trusting the comment.

---

## What we deliberately avoid

### PSR-4 plugin classes

Both versions register PSR-4 prefixes for plugins, but **at different paths**:

| | Catalog classes | Admin classes |
|---|---|---|
| v1.5.8 | `<plugin>/<ver>/classes/` | `<plugin>/<ver>/classes/admin/` |
| v3.0.0-dev | `<plugin>/<ver>/catalog/includes/classes/` | `<plugin>/<ver>/admin/includes/classes/` |

A namespaced class would land in the wrong place on one of them. The plugin
therefore uses plain functions in `extra_functions`, plus one non-namespaced
observer class, and resolves its own shared file with `__DIR__` arithmetic
rather than an autoloader.

### The `ScriptedInstaller` convenience helpers

`addConfigurationKey()`, `deleteConfigurationKeys()`,
`getOrCreateConfigGroupId()`, `executeInstallerDbPerform()` and the rest come
from the `ScriptedInstallHelpers` trait, added in ZC v2.0.1 and extended in
v2.1.0. **None of them exist on v1.5.8.**

The installer uses only `executeInstallerSql()` and `$this->dbConn`, present
since v1.5.7.

### `executeUpgrade()` signature

v1.5.8's base class declares `executeUpgrade()` with no parameters and calls it
with none. v2.x/v3.x declare `executeUpgrade($oldVersion)` and pass the old
version. The plugin declares:

```php
protected function executeUpgrade($oldVersion = null)
```

Widening a parameter to optional satisfies both parent signatures.

### `zen_draw_form()` — two different signatures

A genuine trap:

```php
// catalog: $action is a full URL
zen_draw_form($name, $action, $method = 'post', $parameters = '')

// admin: $action is a FILENAME_* constant; zen_href_link() is called for you
zen_draw_form($name, $action, $parameters = '', $method = 'post', $params = '', $usessl = 'false')
```

Both are stable across v1.5.8→v3.0.0, but they are not interchangeable.
`zen_draw_input_field()` likewise has a different parameter order on each side.

### Native radio buttons

The format choice draws its own indicator (`.scf-mark`) rather than relying on
the browser painting `<input type="radio">`.

This is not a stylistic preference. Zen Cart templates very commonly hide
native radios so they can draw their own — `display: none !important`, the
off-screen `left: -9999px` trick, or a blanket `appearance: none`. Those rules
are written for the template's own forms but they match ours too, and the
result was the choice rendering as the bare words "HTML  TEXT-Only" with no
control at all, while the label still lit up on hover.

Forcing the native control back with `!important` worked, but won the argument
one rule at a time. Drawing the indicator ends it: there is no native radio
left to hide, and `.scf-mark` is our own class name.

The real `<input>` is still there and still does all the work — submission,
focus, arrow keys, screen-reader announcement. It is hidden with the standard
clip technique, **not** `display: none`, so it stays focusable and stays in the
accessibility tree.

---

## Database

The plugin owns one table, created with `CREATE TABLE IF NOT EXISTS` so an
existing one is never disturbed. Column defaults are realigned separately with
an `ALTER TABLE ... MODIFY`, which changes the default only and touches no
stored row.

`email_format` defaults to `TEXT`, matching Zen Cart's own default for
`customers.customers_email_format`. It is `varchar(4)`: the stored values are
`HTML` and `TEXT`, never the `TEXT-Only` display label, which would be
truncated. Zen Cart core also recognises `NONE` and `OUT` on its own column.

Configuration records are written with `INSERT IGNORE` followed by an `UPDATE`
of the metadata only. The value belongs to the store owner and is never reset
by an upgrade; the label, help text, ordering and input type belong to the
plugin and are refreshed to the current version.

### `query_builder`: how the Newsletter Manager finds recipients

Zen Cart's Newsletter Manager does not query the customer table directly. It
lists the rows of `query_builder` and runs the stored `query_string` of
whichever audience the owner picked. Every stock row selects from `customers`
alone, so a footer subscriber with no account cannot be reached by any of them —
the Manager reports zero recipients while the plugin's own list shows people
waiting. That is the failure this plugin's two extra rows exist to fix.

Writing one of those rows has a trap in it. `parsed_query_string()` splits the
stored SQL **on spaces** and passes any token beginning `TABLE_` to
`constant()`, so every table name must be written as the bare constant with a
single space on each side — `FROM TABLE_CUSTOMERS c` — never glued to
punctuation such as `(TABLE_CUSTOMERS)`, and the whole statement must be one
line, because a newline leaves `TABLE_X\nwhere` as one token. Either mistake
throws rather than degrading. The queries are stored in that shape
deliberately; reformatting them for readability would break them.

The rows are keyed by `query_name`, which carries a UNIQUE index. Registration
deletes by name and re-inserts, so re-running it is safe and the same names
drive the uninstaller. Nothing else in `query_builder` is touched, so an owner's
own audiences survive install, upgrade and uninstall alike.

**Renaming an audience means keeping the old name.** There is no id to update
against — the delete matches on `query_name` — so a store that installed an
earlier build has a row under a name the current code no longer knows. The
installer therefore carries `RETIRED_AUDIENCE_QUERIES` alongside the live list
and deletes both, on registration and on uninstall. Drop a name from that list
and the orphan it leaves behind is the fatal described below.

Uninstall **must** remove these rows, and does. `parsed_query_string()` calls
`constant()` unguarded, and the subscriber-table constant only exists while the
plugin is installed — a leftover row would make the Newsletter Manager's
audience page fatal on PHP 8.

### Passwords for invited accounts

An invitation generates its password with `zen_create_PADSS_password()` and
stores it through `zen_encrypt_password()` — both present in every supported
branch, and both the same functions Zen Cart's own admin uses when it creates a
customer. Nothing about the password format is reimplemented here, so a store
that has changed its hashing has already changed this too.

The account is created with `customers_authorization = 3` (pending: may browse,
may not order) and only moves to `0` when the invitation link is followed. Those
two values are stable across all supported versions.

---

## CSRF handling

- **Storefront:** `zen_draw_form()` injects a `securityToken` hidden field into
  every POST form. The subscription handler compares it with `hash_equals()`.
- **Admin:** `admin/includes/init_includes/init_sessions.php` rejects any POST
  carrying an `action` parameter whose token does not match. The plugin's admin
  actions are all named `action` so they fall under that global check, and the
  page re-checks the token itself.

---

## If you are adding to this plugin

Before using any Zen Cart function or constant, check it exists on `v158` as
well as `master`:

```bash
git clone --depth 1 --branch master https://github.com/zencart/zencart.git zc
git -C zc remote set-branches --add origin 2.0 2.1 2.2 2.3 v158
git -C zc fetch --depth 1 origin 2.0 2.1 2.2 2.3 v158
git -C zc grep -c "function zen_whatever" origin/v158 origin/master
```

Fetched refs can be grepped without a working tree, so this costs one clone
rather than six.
