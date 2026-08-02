# Installing Social Contact Footer

Applies to Zen Cart v1.5.8 and later, including v2.x and the v3.0.0 development
branch. PHP 7.4 through 8.5.

---

## 1. Copy the files

This repository contains one directory that belongs in your store:

```
zc_plugins/SocialContactFooter/
```

Upload it so it lands at:

```
<your store root>/zc_plugins/SocialContactFooter/v1.0.0/
```

That directory should contain `manifest.php`, `readme.html`, `changelog.txt`,
and the `Installer/`, `admin/`, `catalog/` and `shared/` sub-directories.

Nothing goes anywhere else. The plugin does not drop files into your `admin/`,
`includes/` or template directories, which is what makes it safe to remove
later.

> **If your store is itself a git checkout of Zen Cart:** `zc_plugins/.gitignore`
> uses a deny-all rule with an explicit allowlist, so add `!SocialContactFooter/`
> and `!SocialContactFooter/**` to it or git will not see the plugin. This has
> no effect on the plugin working.

## 2. Install it

1. Log in to your Zen Cart admin.
2. Go to **Modules → Plugin Manager**.
3. Find **Social Contact Footer** and click **Install**.

> Selecting the plugin shows an info panel on the right. Alongside the
> description and the **Install / Uninstall / Disable** buttons you will find
> **Read Me** and **GitHub** buttons. Read Me opens the full documentation, and
> both work whether or not the plugin is installed — so you can read the manual
> before committing to anything.

The installer creates a configuration group, adds the subscriber table, adds a
**Footer Newsletter Subscribers** entry to the Tools menu, and registers two
audiences in Zen Cart's Newsletter Manager so subscribers without a customer
account can be mailed from it.

If the new menus do not appear straight away, log out of the admin and back in —
Zen Cart caches the menu structure per session.

> **Plugin Manager will list it as "Social Contact Footer - Mod Not Turned On"**
> until you switch it on. That is not a fault: the master switch is off on a
> fresh install, so nothing reaches your storefront while you are still setting
> up. Set **Enable Social Contact Footer?** to `true` and the notice is gone the
> next time you open Plugin Manager.

## 3. Switch it on

**Configuration → Social Contact Footer:**

1. **Enable Social Contact Footer?** → `true`
2. Scroll to the link fields near the bottom and enter your handles. **Enter
   only the distinguishing part** — `YourPageName`, not the whole address.
3. Optionally set **Blog Address:**.
4. Optionally set **Show Newsletter Signup Form?** to `true`.

Reload your storefront. The block appears in the footer.

Every setting is documented in [CONFIGURATION.md](CONFIGURATION.md).

---

## Upgrading

1. Upload the new version directory alongside the old one, e.g.
   `zc_plugins/SocialContactFooter/v1.1.0/`. **Do not delete the old one yet** —
   Plugin Manager needs both to offer the upgrade.
2. **Modules → Plugin Manager → Upgrade.**
3. Once it reports success, the old version directory can be deleted.

**Your settings survive.** Configuration records are written with
`INSERT IGNORE` followed by an update of the metadata only — labels, help text,
ordering and input type are refreshed to the current version, but
`configuration_value` is never touched. Settings that no longer exist are
deleted, so nothing is left orphaned.

Uninstall + Install produces the same result if you prefer it. Your subscriber
table is not dropped either way unless you have opted in (see below).

---

## Uninstalling

**Modules → Plugin Manager → Social Contact Footer → Uninstall.** This removes
the configuration group, all settings, both admin menu entries, and the two
Newsletter Manager audiences.

**Your subscriber list is kept by default**, so an accidental uninstall cannot
destroy addresses people trusted you with. If you genuinely want it gone, set
**Delete Subscribers When Uninstalling?** to `true` *before* uninstalling.

To remove the code as well, delete the `zc_plugins/SocialContactFooter/`
directory afterwards.

Uninstalling does not reset the newsletter flag on customer accounts. Those are
ordinary Zen Cart subscriptions once set, managed from **Customers** or the
Newsletter Manager.

---

## Troubleshooting

### The block does not appear

- Is **Enable Social Contact Footer?** set to `true`?
- Have you supplied at least one link, a blog address, or switched the
  newsletter form on? With none of those there is nothing to render.
- Is the current page named in **Hide On These Pages:**? Checkout and login
  pages are excluded by default.

### It appears at the very bottom of the page rather than in the footer

Your template's `tpl_footer.php` does not fire the in-footer notifier (added in
Zen Cart v2.1), so the plugin is using its end-of-page fallback. Either update
the template from a current release, or add this where you want the block:

```php
<?php $zco_notifier->notify('NOTIFY_FOOTER_AFTER_NAVSUPP', []); ?>
```

### The block appears but looks unstyled

The stylesheet is not loading. View source and check this URL does not 404:

```
zc_plugins/SocialContactFooter/v1.0.0/catalog/includes/templates/template_default/css/social_contact_footer.css
```

Zen Cart's shipped `zc_plugins/.htaccess` denies everything then explicitly
re-allows `.css`, `.js` and `.html`, so on a normal Apache host this works. If
your server ignores `.htaccess` — nginx, LiteSpeed without compatibility mode,
or Apache with `AllowOverride None` plus its own deny rule — you may need a
rule permitting those types under `zc_plugins/`.

### The Subscribe button never hides

The gate script is not loading. Same check as above, for:

```
zc_plugins/SocialContactFooter/v1.0.0/catalog/includes/templates/template_default/jscript/social_contact_footer.js
```

This is deliberately not fatal: the button is rendered visible by the server and
the script is the only thing that hides it, so the form still works and the
server still refuses a signup with no format chosen.

### The format radio buttons are invisible

They should not be — the visible indicator is drawn by the plugin's own
stylesheet precisely because many templates hide native radios. If you see the
words *HTML* and *TEXT-Only* with no rings, the stylesheet is not loading; see
above.

### Confirmation emails are not arriving

The plugin uses Zen Cart's own `zen_mail()`, so it obeys **Configuration →
E-Mail Options**. Check *Send E-Mails* is `true` and use the email test tool
under Tools. Enabling email archiving lets you see exactly what was generated.

Every message the plugin sends asks the recipient to add your sending address to
their address book, which is the most effective thing a small sender can do
about junk folders — but it only helps once the first message arrives.

### The Newsletter Manager shows no subscribers, but the plugin lists some

You are using one of Zen Cart's stock audiences. They read the `customers` table
alone, so a subscriber with no account is invisible to them. In **Tools →
Newsletter and Product Notifications Manager**, pick **Newsletter Subscribers:
Everyone (Social Contact Footer)** instead. If neither of this plugin's
audiences is in that drop-down, re-install from Plugin Manager — that is what
registers them.

If the audience is right but one person still receives nothing, check their
stored email format. Zen Cart skips a recipient silently when it is `NONE` or
`OUT`.

### A subscriber cannot be invited to create an account

Only **confirmed** subscribers with no existing customer account are eligible.
The *Account* column on the Footer Newsletter Subscribers page shows which is
which: **No**, **Invited**, or **Registered**.

### Admin menu entries are missing after installing

Log out and back in. If they are still missing, uninstall and re-install — that
rewrites the `admin_pages` records.

### The Read Me link in Plugin Manager 404s

Same cause as the stylesheet: your server is not serving files from
`zc_plugins/`. The file is in your local copy of the plugin at
`zc_plugins/SocialContactFooter/v1.0.0/readme.html` and opens in any browser by
double-clicking it.
