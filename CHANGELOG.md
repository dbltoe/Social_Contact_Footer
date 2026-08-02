# Changelog

All notable changes to this project are recorded here. This project follows
[Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-08-02

Initial release. Runs on Zen Cart v1.5.8 through v3.0.0 and PHP 7.4 through
8.5 from a single codebase.

The plugin exists to put a store's social-media accounts and blog in front of
its customers. The newsletter signup is an addition rather than the point.

### Storefront

- **Off on a fresh install.** The master switch defaults to `false`, so a
  half-configured block never goes live while the owner is still setting it up.
  While it is off, Plugin Manager shows **Mod Not Turned On** beside the plugin
  name, with a red banner in the info panel naming the setting to change — so an
  unfinished setup announces itself instead of looking like a broken plugin.
- Footer block rendered through `NOTIFY_FOOTER_AFTER_NAVSUPP` (Zen Cart v2.1+)
  with a `NOTIFY_FOOTER_END` fallback for older carts and older templates. No
  template file is edited or overridden.
- Twenty networks, each hidden until a link is supplied.
- Link fields ask only for the distinguishing part of an address — the page
  name, handle or subreddit — rather than a whole URL. A complete `https://`
  address still works where a profile does not fit the usual pattern.
- The contact icon is derived from the store's own Contact Us page or owner
  address, so there is nothing to type.
- On-site link forms are first-class: a Zen Cart page name, a store folder or a
  relative path is preferred over a full address.
- Blog line, separate from the newsletter, with wording built from the store
  name.
- Separate desktop and mobile icon sizes, four badge shapes, three colour
  treatments, alignment, display order, link target, and an optional block
  background colour.
- Appearance lives in a real stylesheet that a template can override by
  supplying its own copy; colours and fonts are inherited by design.

### Newsletter

- **Double opt-in, not optional.** Nothing is sent, no customer account is
  touched and the owner is not notified until the confirmation link is clicked.
- **Neither mail format is pre-selected**, and that is not configurable —
  pre-selecting one is not lawful in the United States. Enforced in the markup
  and again server-side, which refuses a submission with no format rather than
  defaulting to HTML.
- The Subscribe button does not appear until an address is entered and a format
  chosen, implemented as progressive enhancement so a visitor without
  JavaScript still gets a working form.
- The visible radio indicator is drawn by the stylesheet, because Zen Cart
  templates commonly hide native radio inputs; the real input is retained for
  submission, focus and screen-reader semantics.
- **No name field.** The form asks for an address and a format preference and
  nothing else. A single free-text name does not fit Zen Cart's own
  first-name/last-name convention, and nothing downstream uses one: mail goes to
  an address, and an invited customer enters their own details on registering.
- Honeypot plus dwell-time spam trap.
- Confirmed subscribers are mirrored onto matching customer accounts.
- Two Newsletter Manager audiences are registered at install, so subscribers who
  have no customer account are still reachable from **Tools → Newsletter and
  Product Notifications Manager**. Without them a subscriber can appear in the
  plugin's own table yet receive nothing, because Zen Cart's stock audience
  queries read the `customers` table alone. The two are named by whether the
  person holds an account, not by what they subscribed to: the footer's only
  signup is the newsletter, so there is no other kind of subscriber to
  distinguish them from.
- Every subscriber-facing email carries an unsubscribe link, and unsubscribe
  links also appear in the admin CSV export.
- Every subscriber-facing email asks the reader to add the sending address to
  their address book.

### Admin

- **Tools → Footer Newsletter Subscribers**: filtering, search, status changes,
  deletion, CSV export including unsubscribe URLs.
- **CSV import**, with a printable sign-up sheet (portrait, `pdf/`) for
  collecting addresses on paper. Imported people are created *pending* and sent
  the same confirmation request the footer form sends; there is no way to
  bulk-import an already-subscribed person, matching the store owner's own Admin
  Add Customer plugin. Rows are skipped and reported when the address is
  missing, malformed, duplicated in the file, or already known — an import
  cannot resurrect somebody who unsubscribed.
- Both admin pages set a **1.2rem floor** on every piece of text, including form
  controls and `code` spans, which browsers and Bootstrap would otherwise leave
  smaller. The Configuration page is styled through a plugin-supplied
  `configuration_*.php` that prints nothing unless this plugin's own group is
  the one on screen.
- The subscribers list no longer has a Name column; nothing collects a name.
- **Invite Registration**: confirmed subscribers with no customer account can be
  invited to open one, singly or in batches of up to fifty. The invitation
  creates a pending account — able to browse, not able to order — sets a
  generated password, and emails it with a single link that activates the
  account and lands on the login page. The account is unusable until that link
  is followed, so an ignored invitation changes nothing. Once sent, the row
  offers **Send the Invitation Again** — with a fresh password and link, since
  the original password was hashed and never kept — until the subscriber
  accepts. The re-send is its own message, stating that the earlier password and
  link no longer work, so a subscriber holding both knows which to use. The
  *Account* column shows when the last one went out, the confirmation warns
  harder inside ten minutes, and every writing button on the page ignores a
  second click while the first is in flight.
- Wording follows Zen Cart's own **E-Mail** spelling throughout the storefront
  and admin.
- Self-contained `readme.html`, linked with the GitHub project page as buttons
  in the Plugin Manager info panel. Both work before installation, and need no
  core file changes.
- Idempotent installer: values belong to the store owner and are never reset by
  an upgrade, while labels, help text and ordering are refreshed. Retired
  settings are removed automatically. The subscriber table survives an
  uninstall unless the owner opts out.

### Verified

- Zen Cart API: 58 core symbols checked against all six release branches.
- PHP: lint and the full harness suite on 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5.
- Accessibility: WCAG 2.1 AA, with one documented brand-colour deviation.

[1.0.0]: https://github.com/dbltoe/Social_Contact_Footer/releases/tag/v1.0.0
