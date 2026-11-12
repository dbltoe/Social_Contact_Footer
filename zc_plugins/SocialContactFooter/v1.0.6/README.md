# Social Contact Footer

Version 1.0.6 · Zen Cart 1.5.8 through 3.0.0 · PHP 7.4 through 8.5 · GPL v3 · by My Zen Cart Host (dbltoe)

Put your social-media accounts and your blog in front of your customers, from the storefront footer. With a newsletter signup underneath, if you want one.

## What it adds

One block at the bottom of your storefront, in this order:

```
          [f] [X] [ig] [yt] [in] [rss] [@]

      Visit Our Blog At: Acme Widgets's Blog

  Would You Like to Receive Acme Widgets's Newsletter?

            E-Mail Address:   E-Mail Preference:   [ Subscribe ]
            [____________]    ( ) HTML ( ) TEXT-Only

              How We Handle Your Details
```

The blog and newsletter wording is built from your store name — there is nothing to type. The Subscribe button appears once an address is entered and a format chosen.

The icons and the blog link are the point of this plugin. The newsletter signup underneath is a useful addition rather than the reason to install it.

Every part is optional, and **the master switch is off on a fresh install**, so nothing appears on your storefront until you have set the block up and said so.

**Only one of the three collects anything.** The icons and the blog line are links — a visitor follows one and reads, and nothing is asked of them or recorded. The newsletter is the sole signup, which is why every subscriber this plugin has is a newsletter subscriber and there is only one list of them.

- **Icons** — twenty networks. Each icon appears only once you supply a link for it, and you enter only the distinguishing part of your address rather than the whole thing.
- **Blog** — just a link. A blog needs no registration, so this collects nothing and is entirely separate from the newsletter.
- **Newsletter** — a signup form that asks each subscriber whether they want HTML or TEXT-Only mail, and honors that choice when writing to them. Double opt-in, with neither format pre-selected.

No template file is edited or overridden, so the plugin survives template changes and template upgrades.

## Requirements

| Requirement | Supported |
| --- | --- |
| Zen Cart | v1.5.8 through v3.0.0, from a single codebase |
| PHP | 7.4 through 8.5 |
| Database | MySQL 5.7+ / MariaDB 10.2+ |

No Composer packages, no external services, no CDN assets, no jQuery.

## Installing

1. Upload the `zc_plugins/SocialContactFooter/` directory into the `zc_plugins/` directory of your store, so that it lands at `<store root>/zc_plugins/SocialContactFooter/v1.0.6/`.
2. In your admin, go to **Modules → Plugin Manager**.
3. Find *Social Contact Footer* and click **Install**.

The installer creates a configuration group, adds the subscriber table, and adds a **Footer Newsletter Subscribers** entry to the Tools menu. If the new menus do not appear straight away, log out of the admin and back in — Zen Cart caches the menu structure per session.

Once it's installed, a superuser can open this guide from the **ReadMe** button in Plugin Manager.

> **Plugin Manager will show “Social Contact Footer - Mod Not Turned On” in the Installed list until you switch it on.** That is not a fault — the master switch is off on a fresh install, so nothing reaches your storefront while you are still setting things up. Set **Enable Social Contact Footer?** to `true` and the notice is gone the next time you open Plugin Manager.

## Quick start

Everything lives under **Configuration → Social Contact Footer**. To get something on screen:

1. Set **Enable Social Contact Footer?** to `true`.
2. Scroll to the link fields near the bottom and enter your handles — just `YourPageName`, not the whole address. Leave the ones you do not use empty.
3. Optionally set **Blog Address:**.
4. Optionally set **Show Newsletter Signup Form?** to `true`.

Reload your storefront. The block appears in the footer.

## General settings

| Setting | Default | What it does |
| --- | --- | --- |
| Enable Social Contact Footer? | `false` | Master switch. `false` hides the whole block without uninstalling. |
| Hide On These Pages: | checkout & login pages | Comma-separated `main_page` values where the block is suppressed. Empty means show everywhere. The default keeps it out of checkout, where distractions cost you orders. |
| Block Background Color: | *empty* | Optional. Empty means the block sits on whatever your footer already uses. Setting it (`#FFFFFF` is the usual choice) gives the block a known surface, so the legibility of the text and icon badges inside becomes something you can rely on rather than hope for. An unrecognized value is ignored rather than guessed at. |
| Delete Subscribers When Uninstalling? | `false` | Read at uninstall time. `false` keeps your subscriber list safe from an accidental uninstall. |

## Icon settings

| Setting | Default | What it does |
| --- | --- | --- |
| Icons Heading: | *empty* | Empty uses the translatable default, *Follow Us*. Enter a single space for no heading at all. |
| Icons Size On Desktop: | `32` | Glyph size in pixels above 767px. Values outside 12–128 fall back to 32. |
| Icons Size On Mobile: | `24` | Glyph size at 767px and below. Smaller usually works better — footer icons compete for a short screen. |
| Icons Color Style: | Brand colors | *Brand colors*, *Monochrome*, or *Inherit*. Inherit draws them in your template's own text color — usually the right choice for a dark footer. |
| Icons Monochrome Color: | `#444444` | Any CSS color. Only used in Monochrome mode. |
| Icons Badge Shape: | Rounded | Circle, Rounded, Square, or Bare (glyph only, no badge). |
| Icons Alignment: | Center | Left, Center or Right. |
| Icons Artwork Source: | Built-in SVG | Built-in glyphs, or your own image files. See Icon artwork. |
| Icons Image Directory: | `social_contact_footer/` | Directory under your store's `images/` folder, used in image mode. |
| Icons Display Order: | *empty* | Comma-separated keys, e.g. `facebook,instagram,youtube`. Anything left out keeps its default position afterwards. |
| Icons Open Links In: | `_blank` | New tab, with `rel="noopener noreferrer"` applied automatically, or same tab. |

### The link fields

One field per network, near the bottom of the settings list. **An empty field means that icon is not rendered** — there is no separate on/off switch. The network keys are:

```
facebook   x          instagram  youtube    tiktok
linkedin   pinterest  threads    bluesky    mastodon
snapchat   reddit     discord    twitch     telegram
whatsapp   tumblr     vimeo      rss        email
```

Values are tidied up before use:

- `https://`, `http://`, `mailto:`, `tel:` and `/relative/paths` are used as given;
- a bare address containing `@` becomes a `mailto:` link — so a plain contact address works in any of these fields;
- a bare domain such as `x.com/yourhandle` gets `https://` prepended;
- anything else, including `javascript:` and `data:` URLs, is rejected and the icon is simply not shown.

## Blog settings

The blog is **just a link**. Nothing is collected and nobody signs up for anything.

| Setting | Default | What it does |
| --- | --- | --- |
| Blog Address: | *empty* | Shown under the icons as *Visit Our Blog At: <link>*. Empty hides the line entirely. |

*There is no wording setting.* The link writes itself from your store name — **Acme Widgets's Blog** — so it stays right if you rename the store.

> The *RSS Feed Path:* field in the icon section is a different thing: it puts an RSS icon in the icon row pointing at your feed. Use either, both, or neither.

## Newsletter settings

The layout is fixed by design: a heading, then one row holding *E-Mail Address:* and *E-Mail Preference:* with the Subscribe button beside them, then the privacy link. On a narrow screen the fields stack. There is no name field — see below.

| Setting | Default | What it does |
| --- | --- | --- |
| Show Newsletter Signup Form? | `false` | Shows the form beneath the blog line. |
| Show Newsletter Signup Form? | *empty* | Empty uses the default, *Would You Like to Receive Our Newsletter?* A single space shows no heading. |
| Ask HTML Or TEXT-Only? | `true` | Shows the *E-Mail Preference:* choice. **Neither option is pre-selected** and there is no “default format” setting — see Opt-in and the law. Set to `false` and the question is not asked; everyone is then recorded as `TEXT`, which is also Zen Cart’s own default for a new account. |
| Enable Spam Trap? | `true` | A hidden honeypot field plus a minimum time-on-form check. Genuine visitors never see it. |
| Show Privacy Link? | `true` | Shows *How We Handle Your Details* under the form. |
| Privacy Link Destination: | *empty* | See The privacy link. |

## Opt-in and the law

### Double opt-in, and it is not optional

There is no setting for this. The signup is recorded as *pending*, a confirmation link is emailed, and the person is only marked subscribed once they click it. The link is valid for seven days.

Until that link is clicked: nothing is sent to the address, no customer account is touched, and you are not notified. An address that is never confirmed costs one pending row and nothing else.

> In many jurisdictions — the EU and UK under GDPR and PECR among them — you must be able to *prove* consent, and a double opt-in record is the easiest way to do it. It also protects your sending reputation from people typing someone else’s address into your footer.

### Neither mail format is pre-selected

Also not configurable. **Pre-selecting a mail format is not lawful in the United States** — a default is not a choice the subscriber made. They pick, or nothing is recorded.

This is enforced twice over:

- the form ships with neither option selected, both marked required, and the **Subscribe button does not appear** until an address has been entered *and* a format chosen;
- the server refuses a submission with no format, or an unrecognized one. It does not fall back to HTML. Nothing is written and no mail is sent.

The wording — **HTML** and **TEXT-Only** — matches your customers’ own Zen Cart account page, because this writes to the same preference. The stored value is the four-character token `TEXT`, not the `TEXT-Only` label.

### What happens when they confirm

Three things, all automatic and none of them settings:

1. The subscriber is marked confirmed.
2. If the address belongs to a customer account, that account’s *Newsletter* flag and email-format preference are updated, so Zen Cart’s own Newsletter Manager reaches them. If it does not, they are still reachable through the audiences this plugin registers — see Sending to them from the Newsletter Manager.
3. You are notified, and the subscriber gets a welcome message carrying their unsubscribe link.

Every message this plugin sends carries an unsubscribe link, and asks the reader to add your sending address to their address book. Getting that in front of someone early is the single most effective thing a small sender can do to stay out of the junk folder.

### The spam trap

Two independent checks, which behave differently on purpose:

- **Honeypot.** A hidden field a person never sees. If it comes back filled, the submission is discarded and the bot is shown the same success message a real signup gets, so it learns nothing.
- **Dwell time.** The form must have been on screen for at least two seconds. This is only a heuristic, so a failure produces an honest "please press Subscribe once more" message rather than pretending to have worked — silently swallowing a genuine signup would be far worse than one extra click.

## Unsubscribe links

Every subscriber gets a permanent opt-out address:

```
https://yourstore.example/index.php?main_page=index&scf_unsubscribe=<token>
```

The token never changes, so the link keeps working for the life of the subscription. It is included in the plugin's own confirmation and welcome emails, and exported in the `unsubscribe_url` column of the admin CSV export.

**Put it in your newsletter template.** Export the CSV and use the `unsubscribe_url` column as a mail-merge field in whatever you send campaigns with. Zen Cart's built-in Newsletter Manager has no merge field for it, so if you send through that you will need to send per-subscriber or provide a manual unsubscribe route instead.

Visiting the link marks the subscriber unsubscribed, clears the newsletter flag on any matching customer account, and redirects to a clean URL so the token does not leak through the address bar or a `Referer` header.

## Managing newsletter subscribers

**Tools → Footer Newsletter Subscribers** lists everyone who has signed up. You can filter by status, search by E-Mail address, mark somebody subscribed or unsubscribed, delete a record, and export the whole list as CSV.

Statuses are *Awaiting confirmation*, *Subscribed* and *Unsubscribed*.

> **The footer offers exactly one subscription: the newsletter.** Nobody subscribes to an icon or to the blog. Those are links — a visitor follows one and reads, and the store learns nothing about them and stores nothing. So “footer subscriber” and “newsletter subscriber” are the same person, and this page is the whole list of them.

> A second relationship enters only here, on this page: a newsletter subscriber can be invited to become a registered customer. That is the one thing on offer beyond the newsletter itself, and it is offered by you rather than asked for in the footer.

### Importing a list from a CSV file

For addresses collected on paper — typically at a trade show. The **Print the Sign-Up Sheet** button on that page gives you a blank portrait sheet headed *Please Print*: twenty-one lines, each with a space for an address and a box for HTML or TEXT-Only, both marked required. Take it to the event, type what comes back into a spreadsheet, and upload it.

The file needs one column headed `email`. A second headed `email_format` may hold `HTML` or `TEXT`; without it everyone gets TEXT, which is Zen Cart’s own default. Headings go in the first row, in either order, and case does not matter — the wording printed on the sheet works as a heading too. Anything else in the file is ignored.

> **Everyone imported is added as awaiting confirmation and sent a confirmation request**, exactly as if they had used the footer form. They are not subscribed until they click the link in it, and there is no setting to change that.

> This is deliberate. Handwriting gets misread — *rn* becomes *m*, a 1 becomes a 7 — and the address that gets typed in then belongs to somebody who never heard of your store. Sending that person one confirmation request is a mistake; sending them a newsletter is spam. Keep the signed sheets: they are what justifies sending the request, not a substitute for the answer.

Rows are skipped, and reported with their line number, when the address is missing or malformed, when the same address appears twice in one file, and when it is already on your list — an import can never resurrect somebody who unsubscribed. Up to 250 rows and 500KB per file; the rest of a larger file is reported rather than silently dropped, so split it and upload again.

### The header image on these emails

**The three emails this plugin sends carry no image at the top unless you add one** — not your store logo, nothing. That is the default and it needs no setting.

To add one, use **Newsletter E-Mail Header Image** on the subscribers page. Most email headers are **550 × 110** pixels, which is the size to aim for; accepted formats are `jpg`, `jpeg`, `png`, `gif` and `webp`, because Zen Cart’s own `zc_plugins/.htaccess` will not serve anything else. Nothing is resized or cropped. Whatever you upload appears at the top of the confirmation request, the welcome message and the registration invitation, and nowhere else.

> **About that generic header on your other emails.** Every release of Zen Cart from v1.5.8 to v3.0 ships the same placeholder image at `email/header.jpg` in your store root — the identical file, byte for byte — and *every other email your store sends* uses it: order confirmations, account notices, contact-us replies. So unless you have already replaced it, your customers are receiving Zen Cart’s stock artwork.

> Most store owners never notice, because it is not a Configuration setting. The filename is a language define — `EMAIL_LOGO_FILENAME`, set to `header.jpg` in `includes/languages/english/lang.email_extras.php` — so there is no admin screen pointing at it and nothing ever prompts you to change it.

> Nothing here changes that file, and this plugin’s own emails do not show it. But it is worth replacing with something of your own, and the easy way is to put your own image at `email/header.jpg` in your store root, keeping the filename. A customer who signed up in your footer and later places an order will see it on the order confirmation, whatever these three emails look like.

> There is also one uncommon case where a subscriber could see it on one of *these* emails: if the store is configured such that the plugin cannot work out its own public address, it steps back and lets Zen Cart insert the store logo rather than send a broken image. Replacing that placeholder means even that case looks deliberate.

### Sending to them from the Newsletter Manager

Zen Cart’s **Tools → Newsletter and Product Notifications Manager** picks its recipients from a stored list of audience queries, and every stock query reads the `customers` table alone. A subscriber who has never opened an account is not in that table, so without help the Newsletter Manager cannot see them — it will happily report zero recipients while this plugin’s own list shows people waiting.

Installing adds two audiences to that drop-down. Both hold newsletter subscribers, because that is the only thing anyone subscribes to; what separates them is whether the person has a customer account.

| Audience | Who it reaches |
| --- | --- |
| **Newsletter Subscribers: Everyone (Social Contact Footer)** | Every confirmed subscriber, account or no account, de-duplicated so nobody gets two copies. This is the one to use for an ordinary newsletter. |
| **Newsletter Subscribers: No Account Yet (Social Contact Footer)** | Only those without a customer account — the same people this page can invite to register. Useful for a message aimed at them specifically. |

Each address carries the format — HTML or TEXT-Only — that the subscriber chose in the footer, so Zen Cart sends each person the version they asked for. Uninstalling removes both audiences again.

> Zen Cart skips a recipient silently when their stored email format is `NONE` or `OUT`. If one particular person never receives anything, check that column on their record before suspecting your mail server.

### Invite Registration

Subscribers who have no customer account can be invited to create one, singly with **Invite to Create an Account** on their row, or in batches of up to fifty with **Invite All Eligible Subscribers to Create an Account**. The *Account* column shows where each person stands: *No*, *Invited*, or *Registered*.

Only **confirmed** subscribers with no existing account are eligible. Someone who has already been invited is not invited again by the batch button.

> **The invite button disappears once the invitation has gone out, and that is correct** — the account already exists, and a second invitation must not create a second one. In its place the row offers **Send the Invitation Again**, for as long as the account is still pending.

> Re-sending issues a *new* password and a *new* link. That is not a choice: the original password was hashed on the way in and the plaintext was never kept, so there is nothing to repeat. It is safe precisely because the account is still pending — nobody has signed in with the old one. The previous link stops working.

> Because of that, the re-send is a **different message**, not the first one sent twice. If the original was merely slow rather than lost, the subscriber is now holding both, and the second one says plainly that the earlier password and link no longer work — before it gives them the new ones. Without that they would try the older password, fail, and give up.

> To help you judge that, the *Account* column shows **when** — “Invited 3 minutes ago”, with the exact timestamp on hover. If the last one went out within the past ten minutes, the confirmation says so in stronger terms, because mail routinely takes a few minutes and a second press would invalidate a password that is still on its way. It is a warning, not a block; you can always go ahead.

> Every button on the page also refuses a second click while the first is still being processed, so an impatient double-click cannot become two invitations. (Export is the exception — it downloads without leaving the page, and running it twice costs nothing.)

> Once the subscriber accepts, both buttons are gone for good — the password is theirs, and Zen Cart’s own **Customers** page handles them from then on.

An invitation:

1. creates a customer account in **pending** state (`customers_authorization` = 3), which can browse but cannot order;
2. sets a generated password on it;
3. emails the subscriber that password and one link. Following the link activates the account and lands them on the login page.

The account stays pending, and unusable, until that link is followed, so an invitation that is ignored leaves nothing behind but a dormant record. The message says as much, and carries the subscriber’s permanent unsubscribe link like every other message this plugin sends. Ignoring an invitation does not change anything about their subscription.

The account is created without an address, so when the new customer first signs in they are sent to add one, with a message saying their account has no address on file. That address becomes their primary address, and they go on to the page they were heading for.

The mail is sent through `zen_mail()` in whichever format the subscriber chose, so it follows your store’s own email configuration. It tells the recipient to change the password once they are signed in.

## The privacy link

Left at its default, *How We Handle Your Details* links to Zen Cart's built-in **Privacy Notice** page (`index.php?main_page=privacy`).

**To read or change what that page says:** go to **Tools → Define Pages Editor** and choose `define_privacy`. Stock Zen Cart ships generic boilerplate there. It is worth rewriting to describe what your store actually does, particularly now that you are collecting email addresses through the footer.

To point the link somewhere else, put the destination into **Privacy Link Destination:**. Prefer an on-site target — a Zen Cart page name such as `page_3`, or a path such as `policies/privacy/` — because a relative address survives a domain change and an http-to-https move. A full `https://` address should be a last resort.

## Icon artwork

The bundled glyphs are **simplified original drawings, not the official brand logos**. They are deliberately plain, monochrome and geometric so they read well at small sizes and carry no licensing baggage. They are inline SVG, so there are no external requests, no icon font and no CDN.

### Using your own images

1. Create `images/social_contact_footer/` in your store root.
2. Add one file per network named after its key — `facebook.svg`, `youtube.png` and so on. The plugin tries `.svg`, `.png`, `.webp`, `.gif` then `.jpg`. Networks with no matching file are skipped.
3. Set **Icons Artwork Source:** to *Image files*.

> Most platforms publish a brand-assets page with approved artwork and usage rules. Complying with those terms is the store owner's responsibility.

## Styling

The block ships a real stylesheet, linked into the page head. Your template stays in charge of it, and there are three ways in.

### Retune the variables

Almost everything is driven by custom properties. Add a rule to any stylesheet your template already loads:

```
#scfWrapper {
    --scf-gap: 1.2em;        /* space between icons */
    --scf-radius: 50%;       /* badge corner rounding */
    --scf-row-gap: 2em;      /* space above and below the block */
    --scf-field-gap: 1.5em;  /* space between newsletter fields */
}
```

### Add ordinary overrides

Zen Cart auto-loads every `style*.css` in your template’s css folder, and those load *after* the plugin’s, so plain rules win with no `!important` needed.

### Replace the file

Copy it to `includes/templates/YOUR_TEMPLATE/css/social_contact_footer.css` and the plugin links yours instead of its own. Nothing else changes — no admin setting, no file edit. The same rule applies to the small script that gates the Subscribe button, in your template’s `jscript/` folder.

There is no setting to switch the stylesheet off: it carries the block’s layout, so without it the markup collapses. Overriding replaces those rules rather than removing them. Colors and fonts are deliberately not set, so the block inherits your footer.

The stacking breakpoint for the newsletter row is `max-width: 991px`, chosen to cover tablets in both orientations as well as phones.

> Two things not to change. Keep `.scf-hp` hidden — that is the spam trap, and if a visitor can see it they will fill it in and be silently discarded. And keep the rule that hides the real radio input using `clip` rather than `display: none`: the visible radio is drawn by the stylesheet as `.scf-mark`, but the input underneath is still what carries keyboard focus and screen-reader semantics.

### Why the radio is drawn rather than native

Zen Cart templates very commonly hide native radio inputs so they can draw their own — `display: none !important`, the off-screen `left: -9999px` trick, or a blanket `appearance: none`. Those rules are written for the template’s own forms but they match this one too, and the result is the format choice rendering as the bare words “HTML  TEXT-Only” with no control at all.

So the plugin draws the indicator itself. There is no native radio left for a template to hide, and `.scf-mark` is a class name nothing else targets. The ring uses `currentColor`, so it is legible on a dark footer and a light one alike.

## Accessibility

Checked against WCAG 2.1 AA:

- Real radio inputs with `fieldset`/`legend` and wired labels. The visible indicator is drawn by the stylesheet, because many templates hide native radios, but the input still carries focus, keyboard handling and screen-reader semantics.
- Icon links carry text naming their destination; result messages use `role="status"`; the honeypot is out of the tab order and hidden from assistive technology.
- Contrast is computed from the declared colors.
- Animation respects `prefers-reduced-motion`.

**One documented deviation:** four brand badges (WhatsApp, Vimeo, Telegram, RSS) use white glyphs on light official colors and fall below the 3:1 non-text contrast minimum. Brand fidelity was chosen over the ratio; setting **Icons Color Style:** to *Monochrome* or *Inherit* resolves it.

## Translating

All storefront wording lives in one file inside the plugin:

```
catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php
```

Copy it into the matching directory for your language and translate the values. Keep the `lang.` prefix and the array format — that is what Zen Cart v1.5.8 through v3.0.0 all understand.

The blog link and the newsletter heading are built from your store name, so there is nothing for you to type and nothing to keep in step if you rename the store:

| Constant | Default | Produces |
| --- | --- | --- |
| SCF_STORE_POSSESSIVE | `%s's` | Acme Widgets's |
| SCF_BLOG_LINK_PATTERN | `%s Blog` | Acme Widgets's Blog |
| SCF_NEWSLETTER_HEADING | `Would You Like to Receive %s Newsletter?` | … |

The possessive is its own constant so a translator can change the rule, drop it, or special-case a name already ending in “s” — English style guides disagree, and many languages do not form possessives this way at all.

The format labels `HTML` and `TEXT-Only` match Zen Cart’s own account page, because this writes to the same preference. The stored value is the four-character token `TEXT` — that column is `varchar(4)`, so a longer label would be truncated.

> The email templates are passed through `sprintf()`, so a literal percent sign must be written as `%%`. Safer still, avoid them — use `font-size:0.9em` rather than a percentage, as the bundled strings do. A stray `%` produces a fatal error when the mail is sent.

## Upgrading

1. Upload the new version directory alongside the old one, so that both the old directory and `zc_plugins/SocialContactFooter/v1.0.6/` are present. **Do not delete the old one yet** — Plugin Manager needs both to offer the upgrade.
2. Go to **Modules → Plugin Manager** and click **Upgrade**.
3. Once it reports success, the old version directory can be deleted.

**Your settings survive.** Configuration records are written so that the value belongs to you and is never reset by an upgrade, while the label, help text, ordering and input type are refreshed to the current version. Settings that no longer exist are removed, so nothing is left orphaned in your list.

Uninstall followed by Install gives the same result if you prefer it. Your subscriber table is not dropped either way unless you have opted in.

**Version 1.0.6** moves the plugin to the GNU General Public License, version 3, and replaces the old HTML readme with this guide: the ReadMe button in Plugin Manager now opens it inside the admin, for superusers, in its own window. Nothing changes on the storefront or in the settings.

## Uninstalling

**Modules → Plugin Manager → Social Contact Footer → Uninstall.** This removes the configuration group, all settings and both admin menu entries.

**Your subscriber list is kept by default**, so an accidental uninstall cannot destroy addresses people trusted you with. If you genuinely want it gone, set **Delete Subscribers When Uninstalling?** to `true` *before* uninstalling.

Uninstalling does not reset the newsletter flag on customer accounts. Those are ordinary Zen Cart subscriptions once set, managed from **Customers** or the Newsletter Manager.

## Troubleshooting

### The block does not appear

- Is **Enable Social Contact Footer?** set to `true`?
- Have you supplied at least one link, a blog address, or switched the newsletter form on? With none of those there is nothing to render.
- Is the current page named in **Hide On These Pages:**? Checkout and login pages are excluded by default.

### It appears at the very bottom of the page rather than in the footer

Your template's `tpl_footer.php` does not fire the newer in-footer notifier, so the plugin is using its end-of-page fallback. Either update the template from a current Zen Cart release, or add this where you want the block in your `tpl_footer.php`:

```
<?php $zco_notifier->notify('NOTIFY_FOOTER_AFTER_NAVSUPP', []); ?>
```

### The block appears but looks unstyled

The stylesheet is not loading. View source and check this does not 404:

```
zc_plugins/SocialContactFooter/v1.0.6/catalog/includes/templates/template_default/css/social_contact_footer.css
```

Zen Cart’s shipped `zc_plugins/.htaccess` denies everything then explicitly re-allows `.css`, `.js` and `.html`, so on a normal Apache host this works. If your server ignores `.htaccess` — nginx, LiteSpeed without compatibility mode, or Apache with `AllowOverride None` plus its own deny rule — you may need a rule permitting those types under `zc_plugins/`.

### The format radio buttons are invisible

They should not be — the visible indicator is drawn by the plugin’s own stylesheet precisely because many templates hide native radios. If you see the words *HTML* and *TEXT-Only* with no rings beside them, the stylesheet is not loading; see above.

### The Subscribe button never hides

The gate script is not loading. Same check, for:

```
zc_plugins/SocialContactFooter/v1.0.6/catalog/includes/templates/template_default/jscript/social_contact_footer.js
```

This is deliberately not fatal. The button is rendered visible by the server and the script is the only thing that hides it, so the form still works — and the server still refuses a signup with no format chosen.

### Confirmation emails are not arriving

The plugin uses Zen Cart's own mail function, so it obeys **Configuration → E-Mail Options**. Check that *Send E-Mails* is `true`, and use the email test tool under Tools to confirm the store can send mail at all. Turning on email archiving lets you see exactly what was generated.

Every message the plugin sends asks the recipient to add your sending address to their address book, which is the most effective thing a small sender can do about junk folders — but it only helps once the first message arrives.

### The Newsletter Manager shows no subscribers, but the plugin lists some

You are on one of Zen Cart's stock audiences, which read the `customers` table alone and therefore cannot see a subscriber who has no account. In **Tools → Newsletter and Product Notifications Manager**, choose **Newsletter Subscribers: Everyone (Social Contact Footer)** instead. If neither of this plugin's audiences is in that drop-down, re-install from Plugin Manager; that is what registers them. See Sending to them from the Newsletter Manager.

If the list is right but one particular person never receives anything, check their stored email format. Zen Cart skips a recipient silently when it is `NONE` or `OUT`.

### A subscriber cannot be invited to create an account

Only **confirmed** subscribers who do not already have a customer account can be invited. Somebody awaiting confirmation, already unsubscribed, or already holding an account is not eligible — the *Account* column tells you which.

### Admin menu entries are missing after installing

Log out and back in. If they are still missing, uninstall and re-install from Plugin Manager — that rewrites the menu records.

## Support

Need help? Ask in the Forum Support Thread at zen-cart.com (https://www.zen-cart.com/threads/207241), which is also linked from the plugin's Plugin Manager panel. If something is broken rather than unclear, a GitHub issue (https://github.com/dbltoe/Social_Contact_Footer/issues) is easier to track.

Source, issues and newer versions are on GitHub (https://github.com/dbltoe/Social_Contact_Footer).

## Changelog

See `CHANGELOG.md`. Version 1.0.6 moves the plugin to the GNU General Public License, version 3, and replaces the old HTML readme with this guide, opened from the ReadMe button in Plugin Manager.

## License

Social Contact Footer is free software under the GNU General Public License, version 3 (https://www.gnu.org/licenses/gpl-3.0.html); the full text is in `LICENSE`. Copyright © 2026 My Zen Cart Host (dbltoe).

The bundled icon glyphs are original simplified drawings and are not the official logos of the services they represent. All trademarks belong to their respective owners, who are not affiliated with this plugin and do not endorse it.
