# Customising Social Contact Footer

---

## Styling

The block ships a real stylesheet:

```
zc_plugins/SocialContactFooter/v1.0.0/catalog/includes/templates/template_default/css/social_contact_footer.css
```

It is linked into `<head>`, so it loads before your template's own `style*.css`
files. There are three ways to change it, in increasing order of commitment.

### 1. Retune the variables

Almost everything is driven by custom properties on `#scfWrapper`. Add a rule
to any stylesheet your template already loads:

```css
#scfWrapper {
    --scf-gap: 1.2em;          /* space between icons */
    --scf-radius: 50%;         /* badge corner rounding */
    --scf-row-gap: 2em;        /* space above and below the block */
    --scf-field-gap: 1.5em;    /* space between newsletter fields */
    --scf-max-form-width: 40em;
    --scf-bg-pad: 1.5em;       /* padding when a block background is set */
}
```

`--scf-icon-size` is written inline from your admin settings and switched by a
media query at 767px, so set the sizes in the admin rather than here.

### 2. Add ordinary overrides

Zen Cart auto-loads every `style*.css` in your template's `css` folder, and
those load *after* the plugin's, so plain rules win without `!important`:

```css
#scfWrapper .scf-heading { font-size: 1.3em; letter-spacing: .04em; }
```

### 3. Replace the file entirely

Copy the stylesheet to:

```
includes/templates/YOUR_TEMPLATE/css/social_contact_footer.css
```

The plugin looks there first and links yours instead of its own. Nothing else
changes — no admin setting, no file edit.

> There is no setting to disable the stylesheet. It carries the block's layout
> — the icon row, the field row, the stacking breakpoint — so without it the
> markup collapses into an unreadable pile. Overriding replaces those rules
> rather than removing them.

### Class names

```
#scfWrapper                     outer container
  .scf-align-left|center|right  alignment modifier
  .scf-has-bg                   present when a block background is set
  .scf-heading                  section headings
  .scf-icons                    icon block
    .scf-shape-circle|rounded|square|bare
    .scf-icon-list / .scf-icon-item
    .scf-icon                   the <a>; also .scf-icon-facebook etc.
    .scf-visually-hidden        accessible link text
  .scf-blog                     blog line
    .scf-blog-label / .scf-blog-link
  .scf-message                  result message
    .scf-message-success|warning|error
  .scf-subscribe                newsletter block
    .scf-form / .scf-fields
    .scf-field                  one field; also -name -email -format -action
    .scf-label / .scf-legend / .scf-input
    .scf-radios / .scf-radio
    .scf-mark                   the drawn radio indicator
    .scf-radio-text             the option's visible text
    .scf-button
    .scf-privacy
  .scf-hp                       honeypot wrapper (must stay hidden)
```

### Two rules not to remove

**`.scf-hp` must stay hidden.** It is the spam trap; if a visitor can see it
they will fill it in and their signup is silently discarded.

**The `.scf-radio input[type="radio"]` rule must keep hiding the input with
`clip`, never `display: none`.** See [The drawn radio](#the-drawn-radio).

---

## The drawn radio

The visible radio button on the format choice is drawn by the stylesheet as
`.scf-mark` — a ring in `currentColor` that fills with a dot when selected.

**Why, and why it matters if you are editing this.** Zen Cart templates very
commonly hide native radio inputs so they can draw their own:
`input[type="radio"] { display: none !important }`, the off-screen
`left: -9999px` trick, or a blanket `appearance: none`. Those rules are written
for the template's own forms but they match ours too. The result was the format
choice rendering as the bare words "HTML  TEXT-Only" with no control at all —
while the label still lit up on hover, which made it look cosmetic rather than
broken.

Drawing the indicator ends the argument: there is no native radio left to hide,
and `.scf-mark` is a class name nothing else targets.

The real `<input type="radio">` is still present and still does all the work —
it is what submits, what takes focus, what arrow keys move between, and what a
screen reader announces. It is hidden with the standard clip technique so it
stays focusable and stays in the accessibility tree. Focus is drawn on the mark
instead.

If you restyle this, keep those properties. Swapping the clip for
`display: none` would make the choice unreachable by keyboard and invisible to
screen readers.

`currentColor` is deliberate: the surrounding text is legible against your
footer by definition, so a ring in the same colour is legible too — on a dark
footer and a light one alike, with nothing to configure.

---

## The Subscribe button gate

```
zc_plugins/SocialContactFooter/v1.0.0/catalog/includes/templates/template_default/jscript/social_contact_footer.js
```

Hides the Subscribe button until an address is entered and a format chosen.
Override it the same way as the stylesheet:

```
includes/templates/YOUR_TEMPLATE/jscript/social_contact_footer.js
```

**Keep the progressive-enhancement property if you edit it.** The server
renders the button *visible*; the script is the only thing that ever hides it.
So a visitor with JavaScript disabled — or one for whom the file fails to load
— still gets a working form, and the server refuses a signup with no format
either way. Hiding the button in CSS instead would lock those people out.

It is ES5 with no dependencies and no build step, because it has to run on
whatever a Zen Cart storefront happens to be serving.

---

## Icon artwork

The bundled glyphs are **simplified original drawings, not the official brand
logos**. Plain, monochrome and geometric so they read well at small sizes, and
inline SVG so there are no external requests, no icon font and no CDN.

### Using your own images

1. Create `images/social_contact_footer/` in your store root.
2. Add one file per network named after its key — `facebook.svg`,
   `youtube.png`. The plugin tries `.svg`, `.png`, `.webp`, `.gif` then `.jpg`.
   Networks with no matching file are skipped.
3. Set **Icons Artwork Source:** to `Image files`.

Network keys:

```
facebook   x          instagram  youtube    tiktok
linkedin   pinterest  threads    bluesky    mastodon
snapchat   reddit     discord    twitch     telegram
whatsapp   tumblr     vimeo      rss        email
```

Most platforms publish a brand-assets page with approved artwork and usage
rules. Complying with those terms is the store owner's responsibility.

### Changing a glyph, or adding a network

Both live in one file:

```
zc_plugins/SocialContactFooter/v1.0.0/shared/networks.php
```

It returns a plain array, keyed by network slug:

```php
'mynetwork' => [
    'label'        => 'My Network',
    'entry_label'  => 'Handle',                       // builds the admin field title
    'url_template' => 'https://mynetwork.example/%s', // %s is what the owner typed
    'example'      => 'yourhandle',
    'color'        => '#123456',
    'contrast'     => 'light',   // 'light' = white glyph, 'dark' = dark glyph
    'sort'         => 205,
    'svg'          => '<svg viewBox="0 0 24 24" ...>…</svg>',
],
```

Notes:

- The SVG must use `currentColor`, or the monochrome and inherit modes break.
- An empty `url_template` means the value is treated as a path on your store —
  that is how the RSS field works.
- `'derived' => true` means the plugin works the link out from the store's own
  settings and creates **no** configuration field. That is how the contact icon
  works.
- Check `contrast` against the 3:1 non-text minimum. A white glyph needs a
  reasonably dark brand colour.
- Adding an entry does not create its admin field on its own. Re-install from
  Plugin Manager to add the missing `SCF_URL_MYNETWORK` record; existing
  settings are preserved.
- The file is read by the storefront *and* the installer, so it has no Zen Cart
  dependencies. Keep it that way.

---

## Translating

All storefront wording lives in:

```
catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php
```

Copy it to the matching directory for your language and translate the values.
Keep the `lang.` prefix and the array format — that is what Zen Cart v1.5.8
through v3.0.0 all understand.

Admin text is in `admin/includes/languages/english/`:
`extra_definitions/lang.social_contact_footer_names.php` for the menu labels,
and `lang.social_contact_footer_subscribers.php` for the Footer Newsletter
Subscribers page.

Note that the **registration invitation** email — the one message a subscriber
receives that is sent from the admin rather than the storefront — is in that
subscribers file, not the storefront one. The confirmation and welcome messages
are in the storefront file with the rest of the customer-facing wording.

Whatever you translate, keep an unsubscribe route in every subscriber-facing
message. All of them carry one, and `%3$s` (storefront) or `%5$s` (invitation)
is the link.

### The store-name wording

The blog link and newsletter heading are built from your store name, so there is
nothing for a store owner to type:

| Constant | Default | Produces |
|---|---|---|
| `SCF_STORE_POSSESSIVE` | `%s's` | `Acme Widgets's` |
| `SCF_BLOG_LINK_PATTERN` | `%s Blog` | `Acme Widgets's Blog` |
| `SCF_NEWSLETTER_HEADING` | `Would You Like to Receive %s Newsletter?` | … |

The possessive is a separate constant so a translator can change the rule, drop
it, or special-case a name already ending in "s" — English style guides
disagree, and many languages do not form possessives this way at all.

> `SCF_BLOG_LINK_PATTERN` is deliberately **not** named `SCF_BLOG_LINK_TEXT`.
> That was a configuration key in an earlier build, and configuration loads long
> before language files — a leftover value would have silently overridden the
> pattern until the installer removed it.

### Format labels

`SCF_FORMAT_HTML` and `SCF_FORMAT_TEXT` are `HTML` and `TEXT-Only`, matching
Zen Cart's own customer account page, because this writes to the same
`customers_email_format` preference. The stored value is the four-character
token `TEXT` — the column is `varchar(4)`, so a longer label would be
truncated. These constants are the display text only.

### Email placeholders

The subscriber emails take four `sprintf()` placeholders:

| | |
|---|---|
| `%1$s` | store name |
| `%2$s` | the message's primary link (confirm, or unsubscribe) |
| `%3$s` | the permanent unsubscribe link |
| `%4$s` | the address the mail is sent from |

**A literal percent sign must be written as `%%`.** Safer still, avoid them —
use `font-size:0.9em` rather than `font-size:90%`, as the bundled strings do. A
stray `%` here produces a fatal error when the mail is sent.

---

## Rendering the block somewhere else

The block is produced by one function, available on every storefront page:

```php
echo scf_render_block();
```

It returns an empty string when there is nothing to show, so it is safe to call
unconditionally.

To put it somewhere other than the footer, add that call where you want it and
stop the observer emitting its own copy by commenting out the `attach()` call
in `catalog/includes/classes/observers/auto.social_contact_footer.php`.

Leave **Enable Social Contact Footer?** set to `true` — that switch is checked
inside `scf_render_block()` itself, so turning it off would silence your manual
call too.

The block is only ever emitted once per page: the stylesheet link is written at
most once, and the observer ignores the second footer notifier if the first has
already fired.
