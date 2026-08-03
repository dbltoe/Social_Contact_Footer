# Configuration reference

Everything lives under **Admin → Configuration → Social Contact Footer**.
There are 41 settings: 22 that control behaviour, and one link field per
network.

Setting titles are Title Case and end in a colon, or a question mark where the
setting is a yes/no question.

---

## General

| Setting | Default | What it does |
|---|---|---|
| **Enable Social Contact Footer?** | `false` | Master switch, **off on a fresh install** so a half-configured block never goes live while you are still setting it up. Until it is `true`, Plugin Manager shows *Mod Not Turned On* beside the plugin name, so an unfinished setup cannot be forgotten. |
| **Hide On These Pages:** | checkout &amp; login pages | Comma-separated `main_page` values where the block is suppressed. Empty means show everywhere. The default keeps it out of checkout, where distractions cost you orders. |
| **Delete Subscribers When Uninstalling?** | `false` | Read at uninstall time. `false` keeps the subscriber table so an accidental uninstall cannot destroy addresses. |
| **Block Background Color:** | *(empty)* | Optional. Empty means the block sits on whatever your footer already uses. See [Why you might set a background](#why-you-might-set-a-background). |

### Why you might set a background

Left empty, the block inherits your footer — which looks right, but means the
contrast of what sits inside it depends on a color the plugin cannot know.
Setting this (**`#FFFFFF`** is the usual choice) gives the block a known
surface, so the legibility of the text and the icon badges becomes something
you can state rather than hope for.

An unrecognised value is ignored rather than guessed at, so a typo leaves the
block as it was instead of painting a grey slab across your footer.

---

## Icons

| Setting | Default | What it does |
|---|---|---|
| **Icons Heading:** | *(empty)* | Empty uses the translatable default, *Follow us*. A single space shows no heading. |
| **Icons Size On Desktop:** | `32` | Pixels, on screens wider than 767px. Anything outside 12–128 falls back to 32. |
| **Icons Size On Mobile:** | `24` | Pixels, on screens 767px and narrower. Smaller usually works better — footer icons compete for a short screen. |
| **Icons Color Style:** | `Brand colors` | `Brand colors`, `Monochrome`, or `Inherit`. Inherit draws them in your template's own text color, which is usually right for a dark footer. |
| **Icons Monochrome Color:** | `#444444` | Any CSS color. Only used in `Monochrome` mode. |
| **Icons Badge Shape:** | `Rounded` | `Circle`, `Rounded`, `Square`, or `Bare` (glyph only, no badge). |
| **Icons Alignment:** | `Center` | `Left`, `Center` or `Right`. Applies to the whole block. |
| **Icons Artwork Source:** | `Built-in SVG` | Built-in glyphs, or your own image files. See [CUSTOMIZING.md](CUSTOMIZING.md). |
| **Icons Image Directory:** | `social_contact_footer/` | Directory under your store's `images/` folder, used in image mode. |
| **Icons Display Order:** | *(empty)* | Comma-separated keys, e.g. `facebook,instagram,youtube`. Anything left out keeps its default position afterwards. |
| **Icons Open Links In:** | `_blank` | Where **off-site** links open. Links that stay on your own store always open in the same tab, whichever you choose. |
| **Contact Icon Links To:** | `Contact Us page` | Nothing to type — see [The contact icon](#the-contact-icon). |

### The link fields

One field per network. **An empty field means that icon is not rendered** —
that is the only switch.

Each asks for **only the distinguishing part of your address**, not the whole
URL, because a short fragment is far harder to get wrong:

| Field | You enter | Becomes |
|---|---|---|
| Facebook Page Name: | `YourPageName` | `https://www.facebook.com/YourPageName` |
| X (Twitter) Handle: | `yourhandle` | `https://x.com/yourhandle` |
| Instagram Handle: | `yourhandle` | `https://www.instagram.com/yourhandle` |
| YouTube Channel Handle: | `yourchannel` | `https://www.youtube.com/@yourchannel` |
| TikTok Handle: | `yourhandle` | `https://www.tiktok.com/@yourhandle` |
| LinkedIn Company Name: | `your-company` | `https://www.linkedin.com/company/your-company` |
| Pinterest Username: | `youraccount` | `https://www.pinterest.com/youraccount` |
| Threads Handle: | `yourhandle` | `https://www.threads.net/@yourhandle` |
| Bluesky Handle: | `you.bsky.social` | `https://bsky.app/profile/you.bsky.social` |
| Mastodon Handle: | `you@mastodon.social` | `https://mastodon.social/@you` |
| Snapchat Username: | `yourhandle` | `https://www.snapchat.com/add/yourhandle` |
| Reddit Subreddit: | `yoursubreddit` | `https://www.reddit.com/r/yoursubreddit` |
| Discord Invite Code: | `aBcD1234` | `https://discord.gg/aBcD1234` |
| Twitch Channel: | `yourchannel` | `https://www.twitch.tv/yourchannel` |
| Telegram Channel: | `yourchannel` | `https://t.me/yourchannel` |
| WhatsApp Number: | `15551234567` | `https://wa.me/15551234567` |
| Tumblr Blog Name: | `yourblog` | `https://yourblog.tumblr.com` |
| Vimeo Username: | `youraccount` | `https://vimeo.com/youraccount` |
| RSS Feed Path: | `blog/feed` | a path on **your own store** |

Notes:

- A leading `@` is tolerated — `@yourhandle` and `yourhandle` both work.
- **Mastodon** needs the instance, because Mastodon is federated: enter the
  full `user@instance` handle. A bare username is rejected, since there is no
  way to know where it lives.
- **A complete `https://` address still works** in any of these fields, for a
  profile that does not fit the usual pattern. Anything containing `/` or `:`
  is treated as a whole address.
- **RSS** is a path on your store, not a fragment — see
  [Keeping links on your own site](#keeping-links-on-your-own-site).
- `javascript:` and `data:` values are rejected and the icon is not rendered.

### The contact icon

There is nothing to enter. Your store already knows its own Contact Us page and
owner address, so **Contact Icon Links To:** simply chooses between them:

| Choice | Result |
|---|---|
| `Contact Us page` *(default)* | Links to your store's own Contact Us page. Keeps visitors on your site and keeps your address away from harvesting robots. |
| `Store owner E-Mail address` | A `mailto:` link to `STORE_OWNER_EMAIL_ADDRESS`. |
| `Off` | No contact icon. |

---

## Keeping links on your own site

Every link field prefers an on-site target. A relative address survives a
domain change, an http-to-https move and a copy from staging to live, and it
cannot produce a mixed-content warning. A full `https://othersite.com` address
should be a last resort, for something that genuinely lives elsewhere.

Accepted, in order of preference:

| You enter | Meaning |
|---|---|
| `contact_us`, `privacy`, `page_2` | A Zen Cart page name, resolved with `zen_href_link()`. The best option for anything the store already has. |
| `blog/` | A folder on your store. Note the trailing slash — without it, a bare word is read as a page name. |
| `index.php?main_page=page&id=4` | A store URL. |
| `feed.xml` | A file on your store. |
| `/some/path` | Already relative to your web root; used as-is. |
| `sales@example.com` | A bare address, turned into a `mailto:` link. |
| `https://example.com/x` | Off-site. The last resort. |

Social-network fields are the unavoidable exception — that is where those
networks live.

---

## Blog

The blog is **just a link**. There is nothing to sign up for, so it is entirely
separate from the newsletter below it, and no visitor data is involved.

| Setting | Default | What it does |
|---|---|---|
| **Blog Address:** | *(empty)* | Empty hides the blog line entirely. |

**There is no wording to enter.** The link writes itself from your store name:

> Visit Our Blog At: **Acme Widgets's Blog**

If you rename the store, the link follows. The possessive lives in a language
string so a translator can change the rule, drop it, or special-case a name
already ending in "s" — see [CUSTOMIZING.md](CUSTOMIZING.md).

> The separate **RSS Feed Path:** field in the icon section is a different
> thing: it puts an RSS icon in the icon row pointing at your feed. Use either,
> both, or neither.

---

## Newsletter

| Setting | Default | What it does |
|---|---|---|
| **Show Newsletter Signup Form?** | `false` | Shows the form beneath the blog line. |
| **Ask HTML Or TEXT-Only?** | `true` | Shows the *E-Mail Preference:* choice. See [Mail format](#mail-format). |
| **Enable Spam Trap?** | `true` | Hidden honeypot field plus a minimum time-on-form check. Genuine visitors never see it. |
| **Show Privacy Link?** | `true` | Shows *How We Handle Your Details* under the form. |
| **Privacy Link Destination:** | *(empty)* | Empty links to Zen Cart's built-in Privacy Notice page. |

The heading writes itself from your store name, exactly like the blog line:

> Would You Like to Receive **Acme Widgets's** Newsletter?

### Layout

One row on desktop: *E-Mail Address:*, *E-Mail
Preference*, and the **Subscribe** button. At 991px and narrower the row stacks
and the button centres. Switching off the name or format field simply removes
it from the row.

### Mail format

**Neither option is pre-selected, and this is not configurable.** Pre-selecting
a mail format is not lawful in the United States — a default is not a choice the
subscriber made. The subscriber picks, or nothing is recorded.

This is enforced twice:

- the form ships with neither radio checked, both marked `required`, and the
  **Subscribe button does not appear** until an address is entered *and* a
  format chosen;
- the server refuses a submission with no format, or an unrecognised one. It
  does not fall back to HTML. Nothing is written and no mail is sent.

The wording — **HTML** and **TEXT-Only** — deliberately matches Zen Cart's own
customer account page, because this writes to the same
`customers_email_format` preference. The stored value is the four-character
token `TEXT`, not the `TEXT-Only` label.

Switching **Ask HTML Or TEXT-Only?** off records everyone as `TEXT`. That is
also Zen Cart's own default for a new account, and it is the assumption that
assumes least: plain text renders everywhere and carries no images or tracking
pixels.

### Opt-in

**Double opt-in is not optional and there is no setting for it.** The signup is
recorded as *pending*, a confirmation link is emailed, and the person is only
marked subscribed when they click it. The link is valid for seven days.

Nothing is sent to the address, no customer account is touched, and the store
owner is not notified until that link is clicked. An address that is never
confirmed costs one pending row and nothing else.

In many jurisdictions — the EU and UK under GDPR and PECR among them — you must
be able to *prove* consent, and a double opt-in record is the easiest way to do
it. It also protects your sending reputation from people typing someone else's
address into your footer.

### What happens on confirmation

Three things, all automatic and none of them settings:

1. The subscriber is marked confirmed.
2. If the address belongs to a customer account, that account's *Newsletter*
   flag and email-format preference are updated, so Zen Cart's own Newsletter
   Manager reaches them. If it does not, they are still reachable through the
   audiences this plugin registers — see [Reaching subscribers from the
   Newsletter Manager](#reaching-subscribers-from-the-newsletter-manager).
3. The store owner is notified, and the subscriber gets a welcome message
   carrying their unsubscribe link.

Every message this plugin sends carries an unsubscribe link, and asks the reader
to add your sending address to their address book. Getting that in front of
someone early is the single most effective thing a small sender can do to stay
out of the junk folder.

### The spam trap

Two independent checks, which behave differently on purpose:

- **Honeypot.** A hidden field a person never sees. If it comes back filled,
  the submission is discarded and the bot is shown the same success message a
  real signup gets, so it learns nothing.
- **Dwell time.** The form must have been on screen at least two seconds. This
  is only a heuristic, so a failure produces an honest "please press Subscribe
  once more" message rather than pretending to have worked — silently
  swallowing a genuine signup would be far worse than one extra click.

### Where the privacy link goes

Left empty, *How We Handle Your Details* links to Zen Cart's own **Privacy
Notice** page (`index.php?main_page=privacy`).

**To read or change what that page says:** Admin → **Tools → Define Pages
Editor**, then choose **`define_privacy`**. That is stock Zen Cart boilerplate,
and it is worth rewriting to describe what your store actually does —
particularly now that you are collecting email addresses through the footer.

To point somewhere else, prefer an on-site target: a page name such as
`page_3`, or a path such as `policies/privacy/`.

---

## Unsubscribe links

Every subscriber gets a permanent opt-out address:

```
https://yourstore.example/index.php?main_page=index&scf_unsubscribe=<token>
```

The token never changes, so the link keeps working for the life of the
subscription. Every message the plugin sends carries it — the confirmation
request, the welcome message, and a registration invitation alike — and it is
exported in the `unsubscribe_url` column of the admin CSV.

**Put it in your newsletter template.** Export the CSV and use that column as a
mail-merge field in whatever you send campaigns with. Zen Cart's built-in
Newsletter Manager has no merge field for it, so if you send through that you
will need to send per-subscriber or provide a manual unsubscribe route.

Visiting the link marks the subscriber unsubscribed, clears the newsletter flag
on any matching customer account, and redirects to a clean URL so the token
does not leak through the address bar or a `Referer` header.

---

## The Footer Newsletter Subscribers page

**Tools → Footer Newsletter Subscribers** is not configured anywhere; it is
listed here because two things on it are easy to miss.

> **The footer offers exactly one subscription: the newsletter.** Nobody
> subscribes to an icon or to the blog — those are links, and a visitor who
> follows one just reads. Nothing is collected and nothing is stored. So
> "footer subscriber" and "newsletter subscriber" name the same person, and this
> page is the whole list of them. The only further relationship on offer starts
> here, not in the footer: a subscriber can be invited to become a registered
> customer.

### Importing a list from a CSV file

For addresses collected on paper. **Print the Sign-Up Sheet** on that page gives
you a blank portrait sheet headed *Please Print* — twenty-one lines, each with a
space for an address and a box for HTML or TEXT-Only, both marked required.

The file needs one column headed `email`; an optional `email_format` column may
hold `HTML` or `TEXT`. Headings go in the first row, in either order, case
insensitive, and the wording printed on the sheet works too.

> **Everyone imported is added as awaiting confirmation and sent a confirmation
> request**, exactly as if they had used the footer form. They are not
> subscribed until they click the link, and there is no setting to change that.
>
> Handwriting gets misread, and the address that gets typed in then belongs to
> somebody who never heard of your store. One confirmation request to them is a
> mistake; a newsletter is spam. Keep the signed sheets — they justify sending
> the request, they do not replace the answer.

Rows are skipped, and reported with their line number, when the address is
missing or malformed, duplicated within the file, or already on your list — an
import can never resurrect somebody who unsubscribed. Up to 250 rows and 500KB
per file; the remainder of a larger file is reported rather than dropped.

### Reaching subscribers from the Newsletter Manager

Zen Cart's **Tools → Newsletter and Product Notifications Manager** picks its
recipients from stored audience queries, and every stock query reads the
`customers` table alone. A subscriber who has never opened an account is not in
that table. Without help the Newsletter Manager therefore reports zero
recipients while this plugin's own list shows people waiting — which looks
exactly like a mail-server fault and is not one.

Installing adds two audiences to that drop-down. Both hold newsletter
subscribers, because that is the only thing anyone subscribes to; what separates
them is whether the person holds a customer account.

| Audience | Who it reaches |
|---|---|
| **Newsletter Subscribers: Everyone (Social Contact Footer)** | Every confirmed subscriber, account or no account, de-duplicated so nobody gets two copies. Use this for an ordinary newsletter. |
| **Newsletter Subscribers: No Account Yet (Social Contact Footer)** | Only those without a customer account — the same people this page can invite to register. |

Each address carries the format the subscriber chose in the footer, so everyone
gets the version they asked for. Uninstalling removes both audiences.

If one particular person receives nothing, check their stored email format:
Zen Cart skips a recipient silently when it is `NONE` or `OUT`.

### Invite Registration

Confirmed subscribers with no customer account can be invited to open one,
singly with **Invite to Create an Account**, or in batches of up to fifty with
**Invite All Eligible Subscribers to Create an Account**. The *Account* column
shows **No**, **Invited** or **Registered**.

**The invite button disappears once the invitation has gone out**, and that is
correct — the account already exists, and a second invitation must not create a
second one. In its place the row offers **Send the Invitation Again** for as
long as the account is still pending.

Re-sending issues a new password and a new link. That is not a choice: the
original password was hashed on the way in and the plaintext was never kept, so
there is nothing to repeat. It is safe because the account is still pending —
nobody has signed in with the old one — and the previous link stops working.

For that reason the re-send is a *different message*, not the first one sent
twice. If the original was merely slow rather than lost, the subscriber now
holds both, so the second says outright that the earlier password and link no
longer work, before giving them the new ones. Otherwise they would try the older
password, fail, and give up.

To help you judge that, the *Account* column shows **when**: "Invited 3 minutes
ago", with the exact timestamp on hover. If the last one went out within the past
ten minutes the confirmation says so in stronger terms, since mail routinely
takes a few minutes and a second press would invalidate a password still on its
way. It warns rather than blocks — you can always go ahead.

Every button on the page also refuses a second click while the first is still
being processed, so an impatient double-click cannot become two invitations.
Export is the exception: it downloads without leaving the page, and running it
twice costs nothing.

Once the subscriber accepts, both buttons are gone — they are an ordinary
customer, managed from **Customers** like any other.

An invitation creates a customer account in *pending* state
(`customers_authorization` = 3) — able to browse, not able to order — sets a
generated password on it, and emails the subscriber that password with one link
that activates the account and lands on the login page. Until that link is
followed the account stays pending and unusable, so an ignored invitation
leaves nothing but a dormant record and changes nothing about the subscription.
The message says so, and carries the unsubscribe link like every other message
this plugin sends.

---

## Settings removed since v1.0.0

These are deleted automatically on install and upgrade, so nothing is left
orphaned in your Configuration list:

| Removed | Why |
|---|---|
| `SCF_ICON_SIZE` | Split into separate desktop and mobile sizes. |
| `SCF_LOAD_CSS` | The stylesheet carries the block's layout; without it the markup collapses. Override it from your template instead. |
| `SCF_SUBSCRIBE_DEFAULT_FORMAT` | Pre-selecting a mail format is not lawful in the US. |
| `SCF_BLOG_LINK_TEXT` | The blog link text is built from your store name. |
| `SCF_SUBSCRIBE_HEADING` | The newsletter heading is built from your store name. |
| `SCF_SUBSCRIBE_TYPE` | Blog and newsletter are separate features now. |
| `SCF_SUBSCRIBE_CONFIRM` | Double opt-in is not optional. |
| `SCF_SUBSCRIBE_SYNC_CUSTOMER` | Always done, once confirmed. |
| `SCF_SUBSCRIBE_NOTIFY_ADMIN` | Always done, once confirmed. |
| `SCF_URL_EMAIL` | Replaced by **Contact Icon Links To:**, which needs no typing. |

Your own values are never reset by an upgrade. Labels, help text and ordering
are refreshed to the current version; `configuration_value` is not touched.
