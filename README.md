# Social Contact Footer

An encapsulated Zen Cart plugin for putting your social-media accounts and your
blog in front of your customers, from the storefront footer.

That is what it is for. The newsletter signup underneath is a useful addition
rather than the point — worth having, and built properly, but the reason to
install this is the row of icons and the link to your blog.

**Nothing appears until you switch it on.** A fresh install leaves the master
switch off, so a half-configured block never goes live while you are still
setting it up, and every icon stays hidden until you supply a link for it.

**Runs on Zen Cart v1.5.8 through v3.0.0 and PHP 7.4 through 8.5 from a single
codebase**, verified against all six release branches rather than assumed — see
[docs/COMPATIBILITY.md](docs/COMPATIBILITY.md).

---

## What it adds

```
          [f] [X] [ig] [yt] [in] [rss] [@]

        Visit Our Blog At: Acme Widgets's Blog

      Would You Like to Receive Acme Widgets's Newsletter?

            E-Mail Address:   E-Mail Preference:   [ Subscribe ]
            [____________]    ( ) HTML ( ) TEXT-Only

              How We Handle Your Details
```

Every part is optional and independent of the others.

**Only one of those three collects anything.** The icons and the blog line are
links: a visitor follows one and reads, and nothing is asked of them or recorded.
The newsletter is the sole signup, which is why every subscriber the plugin has
is a newsletter subscriber and there is only one list of them.

### Icons

Twenty networks: Facebook, X, Instagram, YouTube, TikTok, LinkedIn, Pinterest,
Threads, Bluesky, Mastodon, Snapchat, Reddit, Discord, Twitch, Telegram,
WhatsApp, Tumblr, Vimeo, RSS, and a contact icon.

**You enter only the distinguishing part of your address** — `YourPageName`,
not `https://www.facebook.com/YourPageName` — because a short fragment is far
harder to get wrong. A complete address still works where a profile doesn't fit
the usual pattern.

The contact icon needs no entry at all: your store already knows its own Contact
Us page and owner address, so the setting just chooses between them.

Separate desktop and mobile sizes, four badge shapes, brand/monochrome/inherited
color, alignment and display order are all admin settings.

### Blog

Just a link — nothing to sign up for, entirely separate from the newsletter.
Supply an address and the wording writes itself from your store name. Leave it
empty and no blog line appears.

### Newsletter

- **Neither mail format is pre-selected**, and that is not configurable —
  pre-selecting one is not lawful in the United States. The Subscribe button
  does not appear until an address is entered *and* a format chosen.
- **Double opt-in is not optional.** Nothing is sent, no customer account is
  touched, and you are not notified until the confirmation link is clicked.
- Confirmed subscribers whose address matches a customer account get that
  account's *Newsletter* flag and format preference updated, so Zen Cart's own
  Newsletter Manager reaches them.
- Subscribers who have *no* account are reachable too: installing registers two
  audiences in the Newsletter Manager, because every stock audience query reads
  the `customers` table alone and cannot see them. They are named by whether the
  person holds an account — the footer's only signup is the newsletter, so there
  is no other kind of subscriber.
- Honeypot plus dwell-time spam trap. Every message the plugin sends carries a
  permanent unsubscribe link and asks the reader to add your sending address to
  their address book.

### Admin

**Tools → Footer Newsletter Subscribers** — filter by status, search by address,
change status, delete, and export CSV including each subscriber's unsubscribe
URL.

**Import from CSV** — for addresses collected on paper. A printable sign-up
sheet comes with the plugin; type what comes back into a spreadsheet and upload
it. Everyone imported is added as *awaiting confirmation* and sent a
confirmation request, exactly as if they had used the footer form — handwriting
gets misread, and a mistyped address belongs to somebody who never asked to hear
from you.

**Invite Registration** — invite confirmed subscribers who have no customer
account to open one, singly or in batches. They get a password and a single link
that activates the account and lands on the login page. The account stays
pending, and unusable, until they follow it, so an ignored invitation changes
nothing.

---

## Requirements

| | |
|---|---|
| Zen Cart | v1.5.8 or later, including v3.0.0-dev |
| PHP | 7.4 – 8.5 |
| Database | MySQL 5.7+ / MariaDB 10.2+ |

No Composer packages, no external services, no CDN assets, no jQuery.

---

## Installation

Copy `zc_plugins/SocialContactFooter/` into the `zc_plugins/` directory of your
store, then install from **Admin → Modules → Plugin Manager**.

Full instructions, including upgrading and uninstalling, are in
[docs/INSTALL.md](docs/INSTALL.md).

### Documentation is in the admin, not just here

Selecting the plugin in Plugin Manager shows an info panel with **Read Me** and
**GitHub** buttons beside Install / Uninstall / Disable. Read Me opens
[`readme.html`](zc_plugins/SocialContactFooter/v1.0.0/readme.html) — a
self-contained offline manual covering every setting — so a store owner never
has to go looking elsewhere. Both work before the plugin is installed.

This needs no core file changes; the mechanism is documented in
[docs/COMPATIBILITY.md](docs/COMPATIBILITY.md).

---

## Configuration

**Admin → Configuration → Social Contact Footer.** 41 settings: 22 behavioural,
plus one link field per network. Every one is documented in
[docs/CONFIGURATION.md](docs/CONFIGURATION.md).

The two you want first:

1. **Enable Social Contact Footer?** → `true`
2. Paste your handles into the link fields near the bottom.

---

## Appearance is yours

The block ships a real stylesheet, not inline styles, and your template stays in
charge of it:

- Copy `social_contact_footer.css` to
  `includes/templates/YOUR_TEMPLATE/css/` and **yours is used instead**.
- Or just add rules to any `style*.css` in your template — those load
  afterwards and win, no `!important` needed.
- Colors and fonts are deliberately not set, so the block inherits your footer.
  Sizes and spacing come from CSS custom properties you can retune in a line.

The same override rule applies to the small script that gates the Subscribe
button. Details in [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md).

---

## Where the block appears

The plugin observes `NOTIFY_FOOTER_AFTER_NAVSUPP` (in-footer, Zen Cart v2.1+)
and falls back to `NOTIFY_FOOTER_END`, which every supported release fires just
before `</body>`. Whichever fires first wins.

**No template file is edited or overridden**, so the plugin survives template
changes and template upgrades.

---

## Accessibility

Checked against WCAG 2.1 AA, not just intended to be:

- Real radio inputs with `fieldset`/`legend` and wired labels — the visible
  indicator is drawn by the stylesheet, because many templates hide native
  radios, but the input still carries focus, keyboard handling and screen-reader
  semantics.
- Icon links carry text naming their destination; result messages use
  `role="status"`; the honeypot is out of the tab order and hidden from
  assistive technology.
- Contrast is computed from the declared colors, in both color schemes for
  the HTML manual.
- Animation respects `prefers-reduced-motion`.

**One documented deviation:** four brand badges (WhatsApp, Vimeo, Telegram,
RSS) use white glyphs on light official colors and fall below the 3:1 non-text
contrast minimum. Brand fidelity was chosen over the ratio; setting **Icons
Color Style** to `Monochrome` or `Inherit` resolves it. It is reported as an
advisory rather than passed silently.

---

## Support

Questions and general help: the
[Forum Support Thread](https://www.zen-cart.com/showthread.php/231169-Social-Contact-Footer?p=1412052#post1412052)
at zen-cart.com, which is where most Zen Cart store owners look first.

Something actually broken: open an
[issue](https://github.com/dbltoe/Social_Contact_Footer/issues) — easier to
track to a fix than a forum reply.

---

## Licence

GPL-2.0 — the same licence Zen Cart uses. See [LICENSE](LICENSE).

The bundled icon glyphs are simplified original drawings, **not** the official
brand logos. All trademarks belong to their respective owners, who are not
affiliated with this plugin and do not endorse it. Complying with each
platform's brand-usage terms is the store owner's responsibility.

---

By **My Zen Cart Host (dbltoe)** ·
[github.com/dbltoe/Social_Contact_Footer](https://github.com/dbltoe/Social_Contact_Footer)
