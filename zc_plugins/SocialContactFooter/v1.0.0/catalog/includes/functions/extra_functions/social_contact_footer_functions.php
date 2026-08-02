<?php
/**
 * Social Contact Footer -- storefront rendering and request handling.
 *
 * Loaded automatically from the plugin's `extra_functions` directory at
 * autoload point 60, so everything here is available well before the footer
 * observer or the plugin's init script run.
 *
 * Nothing in this file uses an API newer than Zen Cart v1.5.8.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/* --------------------------------------------------------------------------
 * Small helpers
 * ----------------------------------------------------------------------- */

/**
 * Read one of the plugin's configuration constants, tolerating a missing key.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function scf_cfg($key, $default = '')
{
    return defined($key) ? (string)constant($key) : $default;
}

/**
 * True when a 'true'/'false' configuration constant is switched on.
 *
 * @param string $key
 * @param bool $default
 * @return bool
 */
function scf_cfg_on($key, $default = false)
{
    return scf_cfg($key, $default ? 'true' : 'false') === 'true';
}

/**
 * Read a language constant, tolerating a missing one.
 *
 * The plugin's language file is loaded by Zen Cart's own loader, so in normal
 * operation these are always defined. The guard exists because an undefined
 * constant is a fatal error on PHP 8, and a missing translation should never
 * be able to take a storefront down.
 *
 * @param string $key
 * @param string $fallback
 * @return string
 */
function scf_lang($key, $fallback = '')
{
    return defined($key) ? (string)constant($key) : $fallback;
}

/**
 * The shared network definitions, loaded once per request.
 *
 * @return array
 */
function scf_networks()
{
    static $networks = null;

    if ($networks === null) {
        // .../<version>/catalog/includes/functions/extra_functions/<this file>
        $networks = require dirname(__DIR__, 4) . '/shared/networks.php';
    }

    return $networks;
}

/**
 * The subscribers table name.
 *
 * @return string
 */
function scf_subscribers_table()
{
    return defined('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS')
        ? TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS
        : DB_PREFIX . 'social_contact_footer_subscribers';
}

/**
 * Queue a one-off message for display inside the footer block.
 *
 * @param string $text
 * @param string $type  success|warning|error
 * @return void
 */
function scf_set_message($text, $type = 'success')
{
    $_SESSION['scf_message'] = ['text' => $text, 'type' => $type];
}

/**
 * Pull (and clear) the queued message.
 *
 * @return array|null
 */
function scf_take_message()
{
    if (empty($_SESSION['scf_message'])) {
        return null;
    }

    $message = $_SESSION['scf_message'];
    unset($_SESSION['scf_message']);

    return $message;
}

/**
 * Should the block be rendered on the page currently being built?
 *
 * @return bool
 */
function scf_block_is_visible()
{
    if (!scf_cfg_on('SCF_STATUS', false)) {
        return false;
    }

    $excluded = trim(scf_cfg('SCF_DISABLE_ON_PAGES'));
    if ($excluded === '') {
        return true;
    }

    $current = isset($_GET['main_page']) ? (string)$_GET['main_page'] : '';
    $excluded = array_filter(array_map('trim', explode(',', $excluded)));

    return !in_array($current, $excluded, true);
}

/**
 * Turn a stored configuration value into an href.
 *
 * On-site forms are deliberately first-class, because a link that keeps the
 * visitor on the store is nearly always the better one: it survives a domain
 * change, an http-to-https move and a staging-to-production copy, and it cannot
 * produce a mixed-content warning. Accepted, in order of preference:
 *
 *   contact_us            a Zen Cart page name -> resolved with zen_href_link()
 *   blog/                 a folder on the store -> prefixed with DIR_WS_CATALOG
 *   index.php?main_page=x a store URL           -> prefixed with DIR_WS_CATALOG
 *   /some/path            already relative to the web root -> used as-is
 *   sales@example.com     a bare address        -> mailto:
 *   https://example.com   off-site, the last resort
 *
 * Anything else -- notably `javascript:` and `data:` -- is rejected and the
 * link is simply not rendered.
 *
 * @param string $slug
 * @param string $value
 * @return string  Empty when the value cannot safely be linked.
 */
function scf_build_href($slug, $value)
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    // Explicit mail/telephone links.
    if (preg_match('~^(mailto:|tel:)~i', $value) === 1) {
        return $value;
    }

    // A bare email address.
    if (strpos($value, '@') !== false && strpos($value, '/') === false && strpos($value, ':') === false) {
        return 'mailto:' . $value;
    }

    // Off-site, fully qualified.
    if (preg_match('~^https?://~i', $value) === 1) {
        return $value;
    }

    // Protocol-relative, e.g. //example.com/x -- treat as off-site https.
    if (strpos($value, '//') === 0) {
        return 'https:' . $value;
    }

    // Already relative to the web root.
    if ($value[0] === '/') {
        return $value;
    }

    // Normalise away any leading "./" or "../". The target is on this store
    // either way, and climbing above the catalog root is not "keeping them on
    // the site". Doing this before the domain heuristic below also stops "./x"
    // being mistaken for a hostname, since its first segment is "." -- which
    // contains a dot.
    $value = preg_replace('~^(\./|\.\./)+~', '', $value);
    if ($value === '') {
        return '';
    }

    // Reject anything carrying a scheme, whitespace or other oddities before
    // the remaining rules get a chance to treat it as a path. A colon at this
    // point can only be a scheme we have not already handled.
    //
    // NOTE: the tilde inside the character class must stay escaped -- it is
    // also this pattern's delimiter, and leaving it bare silently turns the
    // rest of the class into "modifiers", making preg_match() return false and
    // rejecting every value that reaches here.
    if (strpos($value, ':') !== false || preg_match('~^[a-z0-9._\~/?&=+%#-]+$~i', $value) !== 1) {
        return '';
    }

    // A bare Zen Cart page name, e.g. contact_us, privacy, page_2. This is the
    // recommended way to point at something on the store.
    if (preg_match('~^[a-z0-9_]+$~i', $value) === 1) {
        return function_exists('zen_href_link') ? zen_href_link($value, '', 'SSL') : scf_catalog_path($value);
    }

    // Decide between "somewhere.com/path" and "path/on/this/store" by looking at
    // the first segment: a dot means a domain, unless it is a file extension.
    $head = preg_split('~[/?#]~', $value)[0];
    $looksLikeDomain = (strpos($head, '.') !== false)
        && preg_match('~\.(html?|php|xml|rss|atom|json|txt)$~i', $head) !== 1;

    if ($looksLikeDomain) {
        return 'https://' . $value;
    }

    return scf_catalog_path($value);
}

/**
 * Prefix a store-relative path with the catalog's web path, so it resolves
 * correctly whether the shop sits at the domain root or in a subdirectory.
 *
 * @param string $path
 * @return string
 */
function scf_catalog_path($path)
{
    $base = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';

    return $base . ltrim($path, '/');
}

/**
 * Does this href leave the store?
 *
 * Used to decide whether a link needs target/rel attributes -- opening an
 * on-site page in a new tab is just noise.
 *
 * @param string $href
 * @return bool
 */
function scf_is_external($href)
{
    // Relative paths, mailto: and tel: never leave the site in the sense that
    // matters here.
    if (preg_match('~^https?://~i', $href) !== 1) {
        return false;
    }

    $host = strtolower((string)parse_url($href, PHP_URL_HOST));
    if ($host === '') {
        return false;
    }

    $ourHosts = [];
    foreach (['HTTP_SERVER', 'HTTPS_SERVER'] as $constant) {
        if (defined($constant)) {
            $ourHost = strtolower((string)parse_url((string)constant($constant), PHP_URL_HOST));
            if ($ourHost !== '') {
                $ourHosts[] = $ourHost;
            }
        }
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        $ourHosts[] = strtolower(preg_replace('~:\d+$~', '', (string)$_SERVER['HTTP_HOST']));
    }

    return !in_array($host, $ourHosts, true);
}

/**
 * The networks the store owner has actually configured, in display order.
 *
 * @return array  slug => definition, with an added 'href' element.
 */
function scf_active_networks()
{
    $networks = scf_networks();
    $active = [];

    foreach ($networks as $slug => $network) {
        $href = !empty($network['derived'])
            ? scf_contact_href()
            : scf_network_href($slug, $network, scf_cfg('SCF_URL_' . strtoupper($slug)));

        if ($href === '') {
            continue;
        }
        $network['href'] = $href;
        $active[$slug] = $network;
    }

    if (empty($active)) {
        return [];
    }

    // Default order is the 'sort' value from the shared definitions.
    uasort($active, function ($a, $b) {
        if ($a['sort'] === $b['sort']) {
            return 0;
        }
        return ($a['sort'] < $b['sort']) ? -1 : 1;
    });

    $preferred = trim(scf_cfg('SCF_ICON_ORDER'));
    if ($preferred === '') {
        return $active;
    }

    $ordered = [];
    foreach (array_filter(array_map('trim', explode(',', strtolower($preferred)))) as $slug) {
        if (isset($active[$slug])) {
            $ordered[$slug] = $active[$slug];
            unset($active[$slug]);
        }
    }

    // Anything not named in SCF_ICON_ORDER keeps its default position, after.
    return $ordered + $active;
}

/**
 * Turn what the store owner typed into that network's address.
 *
 * The admin fields ask only for the distinguishing part -- the Facebook page
 * name, the Reddit subreddit, the WhatsApp number -- because a short fragment
 * is far harder to get wrong than a whole URL. Each network's `url_template`
 * says where that fragment goes.
 *
 * A complete address still works: anything containing "/" or ":" is passed
 * through to scf_build_href(), which covers profile URLs that do not fit the
 * template, and on-site paths.
 *
 * @param string $slug
 * @param array $network
 * @param string $value
 * @return string  Empty when nothing usable was supplied.
 */
function scf_network_href($slug, array $network, $value)
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    // Looks like a URL or a path rather than a bare fragment.
    if (strpos($value, '/') !== false || strpos($value, ':') !== false) {
        return scf_build_href($slug, $value);
    }

    // People habitually write handles with a leading @; the templates already
    // include one where the network wants it.
    $fragment = ltrim($value, '@');
    if ($fragment === '') {
        return '';
    }

    // Mastodon is federated, so the instance is part of the handle rather than
    // something we can hard-code: user@instance -> https://instance/@user
    if ($slug === 'mastodon') {
        if (strpos($fragment, '@') !== false) {
            list($user, $instance) = explode('@', $fragment, 2);
            $user = trim($user);
            $instance = trim($instance);
            if ($user === '' || $instance === '' || preg_match('~^[a-z0-9.-]+$~i', $instance) !== 1) {
                return '';
            }
            return 'https://' . $instance . '/@' . rawurlencode($user);
        }
        // Bare username with no instance -- we have no idea where they are.
        return '';
    }

    if (empty($network['url_template'])) {
        // No template (the RSS feed, for instance): treat it as a store path.
        return scf_build_href($slug, $value);
    }

    // WhatsApp wants digits only; everything else is a handle-ish token.
    if ($slug === 'whatsapp') {
        $fragment = preg_replace('~[^0-9]~', '', $fragment);
        if ($fragment === '') {
            return '';
        }
    } elseif (preg_match('~^[a-z0-9._-]+$~i', $fragment) !== 1) {
        return '';
    }

    return sprintf($network['url_template'], rawurlencode($fragment));
}

/**
 * Where the contact icon points.
 *
 * There is nothing for the store owner to type here: the store already knows
 * its own Contact Us page and owner address, so the setting is simply which of
 * those to use, or none.
 *
 * @return string
 */
function scf_contact_href()
{
    $choice = scf_cfg('SCF_CONTACT_LINK', 'Contact Us page');

    // The stored value is the option's own text, so the earlier spelling has to
    // keep working: a store that upgrades rather than re-installs still holds
    // "email" in configuration_value, and an upgrade never rewrites a value the
    // owner chose.
    if ($choice === 'Store owner E-Mail address' || $choice === 'Store owner email address') {
        $address = defined('STORE_OWNER_EMAIL_ADDRESS') ? trim(STORE_OWNER_EMAIL_ADDRESS) : '';
        return ($address === '') ? '' : 'mailto:' . $address;
    }

    if ($choice === 'Contact Us page' && defined('FILENAME_CONTACT_US')) {
        return zen_href_link(FILENAME_CONTACT_US, '', 'SSL');
    }

    return '';
}

/* --------------------------------------------------------------------------
 * Rendering
 * ----------------------------------------------------------------------- */

/**
 * The whole footer block, in display order: icons, blog link, newsletter form.
 *
 * @return string  Empty when there is nothing to show.
 */
function scf_render_block()
{
    if (!scf_block_is_visible()) {
        return '';
    }

    $icons = scf_render_icons();
    $blog = scf_render_blog_line();
    $subscribe = scf_render_subscribe_form();

    // A confirm/unsubscribe result is worth showing even when the form itself
    // is switched off, so the message is rendered here rather than inside it.
    $message = scf_take_message();

    if ($icons === '' && $blog === '' && $subscribe === '' && $message === null) {
        return '';
    }

    $html = scf_render_styles();
    $html .= '<div id="scfWrapper" class="scf-wrapper scf-align-' . strtolower(scf_cfg('SCF_ICON_ALIGN', 'Center'))
        . (scf_wrapper_background() === '' ? '' : ' scf-has-bg')
        . '">' . "\n";
    $html .= $icons;
    $html .= $blog;

    if ($message !== null) {
        $html .= '<p class="scf-message scf-message-' . zen_output_string_protected($message['type']) . '" role="status">'
            . zen_output_string_protected($message['text']) . '</p>' . "\n";
    }

    $html .= $subscribe;
    $html .= '</div>' . "\n";

    // Loaded after the markup it operates on, so it needs no defer or ready
    // handler of its own. Only emitted when there is a form to gate.
    if ($subscribe !== '') {
        $script = scf_script_href();
        if ($script !== '') {
            $html .= '<script src="' . zen_output_string_protected($script) . '"></script>' . "\n";
        }
    }

    return $html;
}

/**
 * The row of social icons.
 *
 * @return string
 */
function scf_render_icons()
{
    $networks = scf_active_networks();
    if (empty($networks)) {
        return '';
    }

    // Size is set in CSS (see scf_icon_size()), so the desktop and mobile
    // values can differ. This copy is only for the width/height attributes on
    // an <img> in image mode, which stop the page reflowing as icons load.
    $size = scf_icon_size('SCF_ICON_SIZE_DESKTOP', 32);

    $style = scf_cfg('SCF_ICON_STYLE', 'Brand colors');
    $shape = strtolower(scf_cfg('SCF_ICON_SHAPE', 'Rounded'));
    $target = scf_cfg('SCF_LINK_TARGET', '_blank');
    $useImages = (scf_cfg('SCF_ICON_SOURCE', 'Built-in SVG') === 'Image files');
    $mono = scf_cfg('SCF_ICON_MONO_COLOR', '#444444');

    $relAttr = ($target === '_blank') ? ' rel="noopener noreferrer"' : '';
    $targetAttr = ' target="' . zen_output_string_protected($target) . '"';

    $html = '<div class="scf-icons scf-shape-' . zen_output_string_protected($shape) . '">' . "\n";

    $heading = scf_resolve_heading('SCF_ICONS_HEADING', defined('SCF_DEFAULT_ICONS_HEADING') ? SCF_DEFAULT_ICONS_HEADING : '');
    if ($heading !== '') {
        $html .= '<h3 class="scf-heading">' . zen_output_string_protected($heading) . '</h3>' . "\n";
    }

    $html .= '<ul class="scf-icon-list">' . "\n";

    foreach ($networks as $slug => $network) {
        $label = $network['label'];
        $glyph = $useImages ? scf_icon_image($slug, $label, $size) : $network['svg'];

        if ($glyph === '') {
            continue;
        }

        // Only the brand colour is inline -- that is per-network data, not
        // styling. Everything else, size included, comes from the stylesheet so
        // a template can override it.
        $inline = '';
        if ($style === 'Brand colors' && $shape !== 'bare') {
            $inline .= 'background-color:' . scf_css_color($network['color']) . ';';
            $inline .= 'color:' . ($network['contrast'] === 'dark' ? '#1a1a1a' : '#ffffff') . ';';
        } elseif ($style === 'Brand colors') {
            $inline .= 'color:' . scf_css_color($network['color']) . ';';
        } elseif ($style === 'Monochrome' && $shape !== 'bare') {
            $inline .= 'background-color:' . scf_css_color($mono) . ';color:#ffffff;';
        } elseif ($style === 'Monochrome') {
            $inline .= 'color:' . scf_css_color($mono) . ';';
        }

        $html .= '  <li class="scf-icon-item">'
            . '<a class="scf-icon scf-icon-' . zen_output_string_protected($slug) . '"'
            . ' href="' . zen_output_string_protected($network['href']) . '"'
            . $targetAttr . $relAttr
            . ($inline === '' ? '' : ' style="' . zen_output_string_protected($inline) . '"')
            . ' title="' . zen_output_string_protected($label) . '">'
            . $glyph
            . '<span class="scf-visually-hidden">' . zen_output_string_protected($label) . '</span>'
            . '</a></li>' . "\n";
    }

    $html .= '</ul>' . "\n" . '</div>' . "\n";

    return $html;
}

/**
 * An `<img>` tag for image-file icon mode, or '' when no file is present.
 *
 * @param string $slug
 * @param string $label
 * @param int $size
 * @return string
 */
function scf_icon_image($slug, $label, $size)
{
    $dir = trim(scf_cfg('SCF_ICON_IMAGE_DIR', 'social_contact_footer/'));
    $dir = trim(str_replace(['..', '\\'], '', $dir), '/');
    $dir = ($dir === '') ? '' : $dir . '/';

    foreach (['svg', 'png', 'webp', 'gif', 'jpg'] as $extension) {
        $relative = DIR_WS_IMAGES . $dir . $slug . '.' . $extension;
        if (is_file(DIR_FS_CATALOG . $relative)) {
            return '<img src="' . zen_output_string_protected($relative) . '"'
                . ' alt="" width="' . (int)$size . '" height="' . (int)$size . '" loading="lazy">';
        }
    }

    return '';
}

/**
 * Whitelist a colour value so it can be dropped into a style attribute.
 *
 * @param string $color
 * @return string
 */
function scf_css_color($color)
{
    $color = trim($color);

    if (preg_match('~^(#[0-9a-f]{3,8}|[a-z]{3,20}|rgba?\([0-9,.%\s]+\)|hsla?\([0-9,.%\sdeg]+\))$~i', $color) === 1) {
        return $color;
    }

    return '#444444';
}

/**
 * Resolve a heading: the admin value wins, a single space means "no heading",
 * and an empty value falls back to the translatable default.
 *
 * @param string $configKey
 * @param string $default
 * @return string
 */
function scf_resolve_heading($configKey, $default)
{
    $value = scf_cfg($configKey, '');

    if ($value === '') {
        return trim($default);
    }

    return trim($value);
}

/**
 * Build wording around the store's own name, e.g. "Acme Widgets's Blog".
 *
 * The possessive itself lives in the language file so translators can move it,
 * drop it, or apply their own rule for names already ending in "s" -- English
 * style guides disagree, and other languages do not form possessives this way
 * at all.
 *
 * @param string $langKey   Language constant holding the pattern, with one %s.
 * @param string $fallback  Pattern used if that constant is missing.
 * @return string
 */
function scf_store_possessive($langKey, $fallback)
{
    $storeName = defined('STORE_NAME') ? trim(STORE_NAME) : '';
    if ($storeName === '') {
        return '';
    }

    $possessive = sprintf(scf_lang('SCF_STORE_POSSESSIVE', '%s\'s'), $storeName);

    return trim(sprintf(scf_lang($langKey, $fallback), $possessive));
}

/**
 * The blog line: "Visit Our Blog At: <link>".
 *
 * The blog is just a link -- there is nothing to sign up for, so it is entirely
 * separate from the newsletter form below it. An empty SCF_BLOG_URL hides it.
 *
 * @return string
 */
function scf_render_blog_line()
{
    $href = scf_build_href('blog', scf_cfg('SCF_BLOG_URL'));
    if ($href === '') {
        return '';
    }

    // The link text writes itself from the store's own name -- "Acme Widgets's
    // Blog" -- so the owner supplies an address and nothing else. There is no
    // wording for them to invent, and it stays right if they rename the store.
    $linkText = scf_store_possessive('SCF_BLOG_LINK_PATTERN', '%s Blog');

    $target = scf_cfg('SCF_LINK_TARGET', '_blank');
    $relAttr = ($target === '_blank') ? ' rel="noopener noreferrer"' : '';

    $label = trim(scf_lang('SCF_BLOG_LABEL'));

    return '<p class="scf-blog">'
        . ($label === '' ? '' : '<span class="scf-blog-label">' . zen_output_string_protected($label) . '</span> ')
        . '<a class="scf-blog-link" href="' . zen_output_string_protected($href) . '"'
        . ' target="' . zen_output_string_protected($target) . '"' . $relAttr . '>'
        . zen_output_string_protected($linkText)
        . '</a></p>' . "\n";
}

/**
 * The newsletter subscription form.
 *
 * Layout is a single row of fields -- name, email address, format preference,
 * and the Subscribe button -- which wraps onto separate lines on narrow
 * screens. See scf_render_styles().
 *
 * @return string
 */
function scf_render_subscribe_form()
{
    if (!scf_cfg_on('SCF_SUBSCRIBE_STATUS', false)) {
        return '';
    }

    $askFormat = scf_cfg_on('SCF_SUBSCRIBE_ASK_FORMAT', true);

    // The spam trap needs to know how long the form has been on screen.
    $_SESSION['scf_form_rendered'] = time();

    $returnPage = isset($_GET['main_page']) ? preg_replace('~[^a-z0-9_]~', '', (string)$_GET['main_page']) : '';

    $html = '<div class="scf-subscribe">' . "\n";

    // Like the blog line, the heading is built from the store's own name:
    // "Would You Like to Receive Acme Widgets's Newsletter?" -- so there is no
    // wording setting for the owner to fill in.
    $heading = scf_store_possessive('SCF_NEWSLETTER_HEADING', 'Would You Like to Receive %s Newsletter?');
    if ($heading === '') {
        $heading = scf_lang('SCF_NEWSLETTER_HEADING_FALLBACK');
    }
    if ($heading !== '') {
        $html .= '<h3 class="scf-heading">' . zen_output_string_protected($heading) . '</h3>' . "\n";
    }

    $html .= zen_draw_form('scf_subscribe', zen_href_link(FILENAME_DEFAULT, '', 'SSL'), 'post', 'class="scf-form"') . "\n";
    $html .= zen_draw_hidden_field('scf_action', 'subscribe');
    $html .= zen_draw_hidden_field('scf_return', $returnPage);

    $html .= '<div class="scf-fields">' . "\n";

    // No name field, and no setting to bring one back. A single free-text name
    // does not match Zen Cart's own first-name/last-name convention, so it could
    // only ever be guessed apart again, and nothing here needs it: the mail goes
    // to an address, and a registration invitation leaves the customer to enter
    // their own details. Asking for something unused is just friction on the one
    // form that has to be effortless.
    $html .= '  <div class="scf-field scf-field-email">'
        . '<label class="scf-label" for="scf_email">' . zen_output_string_protected(scf_lang('SCF_LABEL_EMAIL')) . '</label>'
        . zen_draw_input_field('scf_email', '', 'id="scf_email" class="scf-input" autocomplete="email" maxlength="190" required', 'email', false)
        . '</div>' . "\n";

    if ($askFormat) {
        // NEITHER option is pre-selected, deliberately. Pre-ticking a choice a
        // subscriber has not made is not consent, and in the United States
        // pre-selected defaults of this kind are not lawful. The subscriber
        // picks, or nothing is recorded -- enforced again server-side in
        // scf_handle_subscribe(), because a form control is not a guarantee.
        $html .= '  <fieldset class="scf-field scf-field-format">'
            . '<legend class="scf-label scf-legend">' . zen_output_string_protected(scf_lang('SCF_LABEL_FORMAT')) . '</legend>'
            // Each label carries its own <span class="scf-mark">, which the
            // stylesheet draws as the visible radio button. The real <input>
            // is still there and still does all the work -- it is what gets
            // submitted, focused and announced -- but nothing depends on the
            // browser painting it, so a template that hides or restyles native
            // radios can no longer leave the choice invisible.
            . '<span class="scf-radios">'
            . '<label class="scf-radio" for="scf_format_html">'
            . zen_draw_radio_field('scf_format', 'HTML', false, 'id="scf_format_html" required')
            . '<span class="scf-mark" aria-hidden="true"></span>'
            . '<span class="scf-radio-text">' . zen_output_string_protected(scf_lang('SCF_FORMAT_HTML')) . '</span></label>'
            . '<label class="scf-radio" for="scf_format_text">'
            . zen_draw_radio_field('scf_format', 'TEXT', false, 'id="scf_format_text" required')
            . '<span class="scf-mark" aria-hidden="true"></span>'
            . '<span class="scf-radio-text">' . zen_output_string_protected(scf_lang('SCF_FORMAT_TEXT')) . '</span></label>'
            . '</span>'
            . '</fieldset>' . "\n";
    } else {
        // The store owner has switched the question off. Plain text is used
        // rather than HTML: it is the conservative choice -- it renders
        // everywhere, carries no images and no tracking pixels -- so if a
        // preference has to be assumed, this is the one that assumes least.
        $html .= zen_draw_hidden_field('scf_format', 'TEXT');
    }

    // Sits to the right of the format choice when the row has room for it, and
    // drops onto its own line when it does not.
    $html .= '  <div class="scf-field scf-field-action">'
        . '<button type="submit" class="scf-button button">' . zen_output_string_protected(scf_lang('SCF_BUTTON_SUBSCRIBE')) . '</button>'
        . '</div>' . "\n";

    $html .= '</div>' . "\n";

    if (scf_cfg_on('SCF_SUBSCRIBE_HONEYPOT', true)) {
        // Left in the DOM but hidden from people and assistive technology --
        // only automated submitters fill it in.
        $html .= '<div class="scf-hp" aria-hidden="true">'
            . '<label for="scf_website">' . zen_output_string_protected(scf_lang('SCF_LABEL_HONEYPOT')) . '</label>'
            . '<input type="text" name="scf_website" id="scf_website" value="" tabindex="-1" autocomplete="off">'
            . '</div>' . "\n";
    }

    $html .= '</form>' . "\n";

    $privacyHref = scf_privacy_href();
    if ($privacyHref !== '') {
        $html .= '<p class="scf-privacy"><a href="' . zen_output_string_protected($privacyHref) . '">'
            . zen_output_string_protected(scf_lang('SCF_PRIVACY_LINK_TEXT')) . '</a></p>' . "\n";
    }

    $html .= '</div>' . "\n";

    return $html;
}

/**
 * Where the privacy link under the form should point.
 *
 * Defaults to Zen Cart's built-in Privacy Notice page, whose wording is edited
 * under Admin -> Tools -> Define Pages Editor (the `define_privacy` page). Set
 * SCF_SUBSCRIBE_PRIVACY_URL to send visitors somewhere else instead -- an
 * EZ-Page, for example.
 *
 * @return string  Empty when the link is switched off or cannot be built.
 */
function scf_privacy_href()
{
    if (!scf_cfg_on('SCF_SUBSCRIBE_PRIVACY_LINK', true)) {
        return '';
    }

    $override = scf_build_href('privacy', scf_cfg('SCF_SUBSCRIBE_PRIVACY_URL'));
    if ($override !== '') {
        return $override;
    }

    return defined('FILENAME_PRIVACY') ? zen_href_link(FILENAME_PRIVACY, '', 'SSL') : '';
}

/**
 * The plugin's stylesheet, linked into the document head at most once.
 *
 * Appearance is deliberately kept in a real stylesheet rather than inline, so
 * the store's template stays in charge of it:
 *
 *  1. If the active template supplies its own
 *     `includes/templates/<template>/css/social_contact_footer.css`, that file
 *     is linked and the plugin's own copy is skipped entirely.
 *  2. Otherwise the plugin's copy is linked from inside `zc_plugins/`, which
 *     Zen Cart's shipped `zc_plugins/.htaccess` explicitly allows for `.css`.
 *  3. Either way it loads before the template's own `style*.css` files, so
 *     rules a template adds there override it without needing !important.
 *
 * A small `<style>` block follows carrying only the custom properties that
 * come from admin settings -- the desktop and mobile icon sizes. Keeping them
 * as variables means the sizes are set in one place and the media query in the
 * stylesheet does the rest.
 *
 * @return string
 */
function scf_render_styles()
{
    static $written = false;

    if ($written) {
        return '';
    }
    $written = true;

    $html = '';

    // Always linked, with no setting to switch it off. The block's layout --
    // the icon row, the field row, the stacking breakpoint -- lives in this
    // stylesheet, so without it the markup collapses into an unreadable pile.
    // A store that wants full control overrides the file from its template
    // (see scf_stylesheet_href), which replaces these rules rather than
    // removing them.
    $href = scf_stylesheet_href();
    if ($href !== '') {
        $html .= '<link rel="stylesheet" href="' . zen_output_string_protected($href) . '">' . "\n";
    }

    $desktop = scf_icon_size('SCF_ICON_SIZE_DESKTOP', 32);
    $mobile = scf_icon_size('SCF_ICON_SIZE_MOBILE', 24);

    $vars = '--scf-icon-size:' . $desktop . 'px;';

    $background = scf_wrapper_background();
    if ($background !== '') {
        $vars .= '--scf-bg:' . $background . ';';
    }

    $html .= '<style>'
        . '#scfWrapper{' . $vars . '}'
        . '@media (max-width:767px){#scfWrapper{--scf-icon-size:' . $mobile . 'px}}'
        . '</style>' . "\n";

    return $html;
}

/**
 * Which stylesheet to link: the active template's override if it has one,
 * otherwise the plugin's own.
 *
 * @return string  A web path, or '' if neither file can be found.
 */
function scf_stylesheet_href()
{
    return scf_asset_href('css', 'social_contact_footer.css');
}

/**
 * The Subscribe-button gate script, with the same template-override rule.
 *
 * @return string  A web path, or '' if neither file can be found.
 */
function scf_script_href()
{
    return scf_asset_href('jscript', 'social_contact_footer.js');
}

/**
 * Resolve a plugin asset, preferring a copy supplied by the active template.
 *
 * @param string $subdir    'css' or 'jscript'
 * @param string $filename
 * @return string  A web path, or '' if neither file can be found.
 */
function scf_asset_href($subdir, $filename)
{
    // The template's own copy wins. DIR_WS_TEMPLATE is the active template's
    // web path, e.g. includes/templates/responsive_classic/
    if (defined('DIR_WS_TEMPLATE')) {
        $templatePath = DIR_WS_TEMPLATE . $subdir . '/' . $filename;
        if (is_file(DIR_FS_CATALOG . $templatePath)) {
            return scf_catalog_path($templatePath);
        }
    }

    // Fall back to the copy shipped inside the plugin. Worked out from this
    // file's own location so the plugin directory can be renamed or versioned
    // without breaking the link.
    $pluginRoot = dirname(__DIR__, 4);
    $pluginRelative = 'zc_plugins/' . basename(dirname($pluginRoot)) . '/' . basename($pluginRoot)
        . '/catalog/includes/templates/template_default/' . $subdir . '/' . $filename;

    if (is_file(DIR_FS_CATALOG . $pluginRelative)) {
        return scf_catalog_path($pluginRelative);
    }

    return '';
}

/**
 * The block's own background colour, if the store owner has set one.
 *
 * Empty means "inherit the template", which is the default and changes
 * nothing. Setting it -- #FFFFFF being the usual choice -- gives the block a
 * known surface, so the contrast of the text and the icon badges sitting on it
 * is determined by the plugin rather than by whatever the template happens to
 * put behind the footer.
 *
 * @return string  A validated CSS colour, or '' to inherit.
 */
function scf_wrapper_background()
{
    $value = trim(scf_cfg('SCF_WRAPPER_BACKGROUND'));
    if ($value === '' || strcasecmp($value, 'transparent') === 0) {
        return '';
    }

    $colour = scf_css_color($value);

    // scf_css_color() falls back to #444444 for anything it does not
    // recognise. A grey slab across the footer would be a startling result for
    // a typo, so treat an unrecognised value as "not set" instead.
    return ($colour === '#444444' && strcasecmp($value, '#444444') !== 0) ? '' : $colour;
}

/**
 * Read one of the icon-size settings, clamped to something sane.
 *
 * @param string $key
 * @param int $default
 * @return int
 */
function scf_icon_size($key, $default)
{
    $size = (int)scf_cfg($key, (string)$default);

    return ($size < 12 || $size > 128) ? $default : $size;
}

/* --------------------------------------------------------------------------
 * Request handling
 * ----------------------------------------------------------------------- */

/**
 * Entry point called from the plugin's init script on every storefront page.
 *
 * @return void
 */
function scf_handle_request()
{
    // Confirm/unsubscribe links carry a token, so redirect to a clean URL once
    // it has been acted on. That keeps the token out of the address bar, the
    // browser history and any outbound Referer header.
    if (!empty($_GET['scf_confirm'])) {
        scf_handle_confirm((string)$_GET['scf_confirm']);
        zen_redirect(zen_href_link(FILENAME_DEFAULT, '', 'SSL'));
        return;
    }

    if (!empty($_GET['scf_unsubscribe'])) {
        scf_handle_unsubscribe((string)$_GET['scf_unsubscribe']);
        zen_redirect(zen_href_link(FILENAME_DEFAULT, '', 'SSL'));
        return;
    }

    // Accepting a registration invitation. Sent to the login page afterwards,
    // since the emailed password is what they need next.
    if (!empty($_GET['scf_activate'])) {
        scf_handle_activate((string)$_GET['scf_activate']);
        zen_redirect(zen_href_link(defined('FILENAME_LOGIN') ? FILENAME_LOGIN : FILENAME_DEFAULT, '', 'SSL'));
        return;
    }

    if (isset($_POST['scf_action']) && $_POST['scf_action'] === 'subscribe') {
        scf_handle_subscribe();
    }
}

/**
 * Send the visitor back to the page the form was submitted from.
 *
 * Only a bare `main_page` token is honoured, so this cannot be turned into an
 * open redirect.
 *
 * @param string $params
 * @return void
 */
function scf_redirect_back($params = '')
{
    $page = isset($_POST['scf_return']) ? preg_replace('~[^a-z0-9_]~', '', (string)$_POST['scf_return']) : '';
    if ($page === '') {
        $page = FILENAME_DEFAULT;
    }

    zen_redirect(zen_href_link($page, $params, 'SSL'));
}

/**
 * Process a subscription request.
 *
 * @return void
 */
function scf_handle_subscribe()
{
    global $db;

    if (!scf_cfg_on('SCF_SUBSCRIBE_STATUS', false)) {
        scf_redirect_back();
        return;
    }

    // Zen Cart's zen_draw_form() adds this token to every POST form.
    $posted = isset($_POST['securityToken']) ? (string)$_POST['securityToken'] : '';
    if (empty($_SESSION['securityToken']) || !hash_equals((string)$_SESSION['securityToken'], $posted)) {
        scf_set_message(scf_lang('SCF_ERROR_SESSION'), 'error');
        scf_redirect_back();
        return;
    }

    if (scf_cfg_on('SCF_SUBSCRIBE_HONEYPOT', true)) {
        // A filled honeypot is never a person, so give the bot the same reply a
        // real signup gets and quietly discard it.
        if (!empty($_POST['scf_website'])) {
            scf_set_message(scf_lang('SCF_SUCCESS_PENDING'), 'success');
            scf_redirect_back();
            return;
        }

        // The time check is only a heuristic, so a failure is reported honestly
        // and the visitor can simply submit again -- silently swallowing a
        // genuine signup would be far worse than one extra click.
        if (!scf_form_dwell_time_ok()) {
            scf_set_message(scf_lang('SCF_ERROR_TOO_FAST'), 'warning');
            scf_redirect_back();
            return;
        }
    }

    $email = trim((string)($_POST['scf_email'] ?? ''));
    if ($email === '' || !zen_validate_email($email)) {
        scf_set_message(scf_lang('SCF_ERROR_EMAIL'), 'error');
        scf_redirect_back();
        return;
    }

    // No format is assumed. If the subscriber was asked and did not answer,
    // the signup is refused rather than silently defaulted -- picking for them
    // is exactly what must not happen. The form hides its Subscribe button
    // until a choice is made, so reaching here means the gate was bypassed.
    $posted = (string)($_POST['scf_format'] ?? '');
    if (scf_cfg_on('SCF_SUBSCRIBE_ASK_FORMAT', true)) {
        if ($posted !== 'HTML' && $posted !== 'TEXT') {
            scf_set_message(scf_lang('SCF_ERROR_FORMAT'), 'error');
            scf_redirect_back();
            return;
        }
        $format = $posted;
    } else {
        // Question switched off by the store owner; see the note where the
        // hidden field is written.
        $format = 'TEXT';
    }

    $existing = scf_find_subscriber($email);

    // Already subscribed and confirmed -- update the preference and say so.
    if ($existing !== null && (int)$existing['status'] === 1) {
        // subscriber_name is not touched. Nothing collects it any more, but a
        // store that ran an earlier build may hold one, and overwriting that
        // with an empty string would destroy data for no reason.
        scf_update_subscriber((int)$existing['subscriber_id'], [
            'email_format' => $format,
        ]);
        scf_sync_customer_record($email, $format, true);
        scf_set_message(scf_lang('SCF_NOTICE_ALREADY_SUBSCRIBED'), 'warning');
        scf_redirect_back();
        return;
    }

    $token = scf_make_token();

    // subscriber_name is absent from this list on purpose: the column stays for
    // rows an earlier build filled in, and this upsert must not blank one of
    // those when somebody re-subscribes.
    $assignments = [
        "customers_id = " . (int)scf_customer_id_for_email($email),
        "email_format = '" . $db->prepare_input($format) . "'",
        // Always 0 = awaiting confirmation. Confirmation is not optional: an
        // address that is never confirmed is never mailed, never copied onto a
        // customer account, and never counted -- so an unanswered signup costs
        // one row that the pending sweep can later clear, and nothing else.
        "status = 0",
        "confirm_token = '" . $db->prepare_input($token) . "'",
        "token_expires = DATE_ADD(now(), INTERVAL 7 DAY)",
        "ip_address = '" . $db->prepare_input(scf_client_ip()) . "'",
        "language_id = " . (int)(isset($_SESSION['languages_id']) ? $_SESSION['languages_id'] : 1),
        "last_modified = now()",
    ];

    // A single upsert rather than "look, then insert or update": the email
    // column is UNIQUE, so two near-simultaneous submissions of the same
    // address would otherwise race into a duplicate-key error.
    $db->Execute(
        "INSERT INTO " . scf_subscribers_table() . "
            SET subscriber_email = '" . $db->prepare_input($email) . "',
                date_added = now(),
                " . implode(",\n                ", $assignments) . "
         ON DUPLICATE KEY UPDATE
                " . implode(",\n                ", $assignments)
    );

    // The address doubles as the recipient name; zen_mail() needs something,
    // and it is the only thing we know about them.
    scf_send_confirmation_email($email, $email, $format, $token);
    scf_set_message(scf_lang('SCF_SUCCESS_PENDING'), 'success');

    scf_redirect_back();
}

/**
 * Confirm a double opt-in subscription.
 *
 * @param string $token
 * @return void
 */
function scf_handle_confirm($token)
{
    global $db;

    // Reduce to what actually matched the stored token, so the value reused
    // below for the welcome message's unsubscribe link is the real one and not
    // whatever decoration arrived in the query string.
    $token = preg_replace('~[^a-f0-9]~i', '', (string)$token);

    $row = scf_find_subscriber_by_token($token);
    if ($row === null || (int)$row['status'] === 2) {
        scf_set_message(scf_lang('SCF_ERROR_BAD_TOKEN'), 'error');
        return;
    }

    if ((int)$row['status'] === 1) {
        scf_set_message(scf_lang('SCF_NOTICE_ALREADY_SUBSCRIBED'), 'warning');
        return;
    }

    if (!empty($row['token_expires']) && strtotime($row['token_expires']) < time()) {
        scf_set_message(scf_lang('SCF_ERROR_TOKEN_EXPIRED'), 'error');
        return;
    }

    $db->Execute(
        "UPDATE " . scf_subscribers_table() . "
            SET status = 1,
                date_confirmed = now(),
                last_modified = now(),
                customers_id = " . (int)scf_customer_id_for_email($row['subscriber_email']) . "
          WHERE subscriber_id = " . (int)$row['subscriber_id'] . "
          LIMIT 1"
    );

    // Now, and only now, is the address known to be real and wanted: copy the
    // preference onto any matching customer account and tell the store owner.
    scf_sync_customer_record($row['subscriber_email'], $row['email_format'], true);
    scf_notify_store_owner($row['subscriber_email'], $row['email_format']);

    // A keepable message carrying their unsubscribe link, and the request to
    // add the store to their address book now that they have engaged.
    // The token we were called with is the same one stored against the row --
    // that is how the row was found -- so use it rather than reading the column
    // back out, which would break if the query ever stopped selecting it.
    // A name is only ever present on a row an earlier build created; the
    // address stands in otherwise.
    $recipientName = trim((string)($row['subscriber_name'] ?? ''));
    scf_send_welcome_email(
        $row['subscriber_email'],
        $recipientName !== '' ? $recipientName : $row['subscriber_email'],
        $row['email_format'],
        $token
    );

    scf_set_message(scf_lang('SCF_SUCCESS_CONFIRMED'), 'success');
}

/**
 * Accept a registration invitation.
 *
 * The account already exists but was created pending, so it cannot be used
 * until this link is clicked. That is what makes an ignored invitation
 * harmless: it leaves a dormant account, not a live login for someone who only
 * ever asked for a newsletter.
 *
 * Declining is simply not clicking. Nothing here touches their subscription.
 *
 * @param string $token
 * @return void
 */
function scf_handle_activate($token)
{
    global $db;

    $token = preg_replace('~[^a-f0-9]~i', '', (string)$token);
    if (strlen($token) < 32) {
        scf_set_message(scf_lang('SCF_ERROR_BAD_TOKEN'), 'error');
        return;
    }

    $result = $db->Execute(
        "SELECT subscriber_id, customers_id, invite_accepted
           FROM " . scf_subscribers_table() . "
          WHERE invite_token = '" . $db->prepare_input($token) . "'
          LIMIT 1"
    );

    if ($result->EOF || (int)$result->fields['customers_id'] === 0) {
        scf_set_message(scf_lang('SCF_ERROR_BAD_TOKEN'), 'error');
        return;
    }

    $row = $result->fields;

    if (!empty($row['invite_accepted'])) {
        scf_set_message(scf_lang('SCF_INVITE_ALREADY_ACCEPTED'), 'warning');
        return;
    }

    // 0 = approved, mirroring core's Customer::AUTH_OK.
    $db->Execute(
        "UPDATE " . TABLE_CUSTOMERS . "
            SET customers_authorization = 0
          WHERE customers_id = " . (int)$row['customers_id'] . "
          LIMIT 1"
    );

    // Zen Cart v2.2.0 added this column; older releases do not have it.
    if (scf_customers_column_exists('activation_required')) {
        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET activation_required = 0
              WHERE customers_id = " . (int)$row['customers_id'] . "
              LIMIT 1"
        );
    }

    $db->Execute(
        "UPDATE " . scf_subscribers_table() . "
            SET invite_accepted = now(),
                last_modified = now()
          WHERE subscriber_id = " . (int)$row['subscriber_id'] . "
          LIMIT 1"
    );

    scf_set_message(scf_lang('SCF_INVITE_ACCEPTED'), 'success');
}

/**
 * Does a column exist on the customers table? Cached per request.
 *
 * @param string $column
 * @return bool
 */
function scf_customers_column_exists($column)
{
    static $cache = [];

    if (isset($cache[$column])) {
        return $cache[$column];
    }

    global $db;
    $result = $db->Execute(
        "SHOW COLUMNS FROM " . TABLE_CUSTOMERS . " LIKE '" . $db->prepare_input($column) . "'"
    );
    $cache[$column] = ($result !== false && !$result->EOF);

    return $cache[$column];
}

/**
 * Honour an unsubscribe link.
 *
 * @param string $token
 * @return void
 */
function scf_handle_unsubscribe($token)
{
    global $db;

    $row = scf_find_subscriber_by_token($token);
    if ($row === null) {
        scf_set_message(scf_lang('SCF_ERROR_BAD_TOKEN'), 'error');
        return;
    }

    $db->Execute(
        "UPDATE " . scf_subscribers_table() . "
            SET status = 2,
                date_unsubscribed = now(),
                last_modified = now()
          WHERE subscriber_id = " . (int)$row['subscriber_id'] . "
          LIMIT 1"
    );

    scf_sync_customer_record($row['subscriber_email'], $row['email_format'], false);
    scf_set_message(scf_lang('SCF_SUCCESS_UNSUBSCRIBED'), 'success');
}

/* --------------------------------------------------------------------------
 * Data access
 * ----------------------------------------------------------------------- */

/**
 * @param string $email
 * @return array|null
 */
function scf_find_subscriber($email)
{
    global $db;

    $result = $db->Execute(
        "SELECT * FROM " . scf_subscribers_table() . "
          WHERE subscriber_email = '" . $db->prepare_input($email) . "'
          LIMIT 1"
    );

    return $result->EOF ? null : $result->fields;
}

/**
 * @param string $token
 * @return array|null
 */
function scf_find_subscriber_by_token($token)
{
    global $db;

    $token = preg_replace('~[^a-f0-9]~i', '', (string)$token);
    if (strlen($token) < 32) {
        return null;
    }

    $result = $db->Execute(
        "SELECT * FROM " . scf_subscribers_table() . "
          WHERE confirm_token = '" . $db->prepare_input($token) . "'
          LIMIT 1"
    );

    return $result->EOF ? null : $result->fields;
}

/**
 * @param int $subscriberId
 * @param array $fields  column => value (strings only)
 * @return void
 */
function scf_update_subscriber($subscriberId, array $fields)
{
    global $db;

    if (empty($fields)) {
        return;
    }

    $assignments = [];
    foreach ($fields as $column => $value) {
        $column = preg_replace('~[^a-z_]~', '', $column);
        $assignments[] = "$column = '" . $db->prepare_input((string)$value) . "'";
    }
    $assignments[] = 'last_modified = now()';

    $db->Execute(
        "UPDATE " . scf_subscribers_table() . "
            SET " . implode(', ', $assignments) . "
          WHERE subscriber_id = " . (int)$subscriberId . "
          LIMIT 1"
    );
}

/**
 * The customers_id owning an address, or 0.
 *
 * @param string $email
 * @return int
 */
function scf_customer_id_for_email($email)
{
    global $db;

    $result = $db->Execute(
        "SELECT customers_id FROM " . TABLE_CUSTOMERS . "
          WHERE customers_email_address = '" . $db->prepare_input($email) . "'
          LIMIT 1"
    );

    return $result->EOF ? 0 : (int)$result->fields['customers_id'];
}

/**
 * Mirror the subscription onto a matching customer account so Zen Cart's own
 * Newsletter Manager sees it.
 *
 * @param string $email
 * @param string $format  HTML|TEXT
 * @param bool $subscribed
 * @return void
 */
function scf_sync_customer_record($email, $format, $subscribed)
{
    global $db;

    // Not optional, and not exposed as a setting: this only ever runs for an
    // address whose owner has clicked the confirmation link, so there is no
    // scenario in which a store would want it switched off.
    $customerId = scf_customer_id_for_email($email);
    if ($customerId === 0) {
        return;
    }

    $format = ($format === 'TEXT') ? 'TEXT' : 'HTML';

    $db->Execute(
        "UPDATE " . TABLE_CUSTOMERS . "
            SET customers_newsletter = '" . ($subscribed ? '1' : '0') . "',
                customers_email_format = '" . $db->prepare_input($format) . "'
          WHERE customers_id = " . (int)$customerId . "
          LIMIT 1"
    );
}

/* --------------------------------------------------------------------------
 * Mail + anti-spam
 * ----------------------------------------------------------------------- */

/**
 * @return string  40 hex characters.
 */
function scf_make_token()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(20));
    }

    return sha1(uniqid((string)mt_rand(), true));
}

/**
 * @return string
 */
function scf_client_ip()
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/**
 * Minimum time-on-form check.
 *
 * Scripted submitters post the instant they see the form. Two seconds is
 * deliberately lenient: this only ever produces a "please try again" message,
 * so it must not get in a real visitor's way.
 *
 * @return bool  True when the form was on screen long enough.
 */
function scf_form_dwell_time_ok()
{
    $rendered = isset($_SESSION['scf_form_rendered']) ? (int)$_SESSION['scf_form_rendered'] : 0;

    // No render timestamp means the POST did not come from a form we drew.
    return ($rendered > 0 && (time() - $rendered) >= 2);
}

/**
 * The permanent opt-out link for a subscriber.
 *
 * `confirm_token` is deliberately never rotated, so this address keeps working
 * for the life of the subscription. Store owners should paste this pattern into
 * their newsletter template -- see docs/CONFIGURATION.md.
 *
 * @param string $token
 * @return string
 */
function scf_unsubscribe_url($token)
{
    return zen_href_link(FILENAME_DEFAULT, 'scf_unsubscribe=' . $token, 'SSL', false);
}

/**
 * Send the double opt-in confirmation message.
 *
 * @param string $email
 * @param string $name
 * @param string $format  HTML|TEXT
 * @param string $token
 * @return void
 */
function scf_send_confirmation_email($email, $name, $format, $token)
{
    $confirmUrl = zen_href_link(FILENAME_DEFAULT, 'scf_confirm=' . $token, 'SSL', false);

    scf_send_subscriber_email(
        $email,
        $name,
        $format,
        scf_lang('SCF_EMAIL_CONFIRM_SUBJECT'),
        scf_lang('SCF_EMAIL_CONFIRM_TEXT'),
        scf_lang('SCF_EMAIL_CONFIRM_HTML'),
        $confirmUrl,
        scf_unsubscribe_url($token),
        'social_contact_footer'
    );
}

/**
 * Send the single opt-in welcome message.
 *
 * @param string $email
 * @param string $name
 * @param string $format  HTML|TEXT
 * @param string $token
 * @return void
 */
function scf_send_welcome_email($email, $name, $format, $token)
{
    $unsubscribeUrl = scf_unsubscribe_url($token);

    scf_send_subscriber_email(
        $email,
        $name,
        $format,
        scf_lang('SCF_EMAIL_WELCOME_SUBJECT'),
        scf_lang('SCF_EMAIL_WELCOME_TEXT'),
        scf_lang('SCF_EMAIL_WELCOME_HTML'),
        $unsubscribeUrl,
        $unsubscribeUrl,
        'social_contact_footer'
    );
}

/**
 * Shared plumbing for the subscriber-facing messages.
 *
 * The templates take three placeholders: %1$s store name, %2$s the message's
 * primary link, %3$s the unsubscribe link. An HTML part is only supplied when
 * the subscriber asked for HTML, which is what makes the plain-text choice on
 * the form actually mean something.
 *
 * @return void
 */
function scf_send_subscriber_email($email, $name, $format, $subjectTemplate, $textTemplate, $htmlTemplate, $primaryUrl, $unsubscribeUrl, $module)
{
    $storeName = defined('STORE_NAME') ? STORE_NAME : '';
    $fromAddress = defined('EMAIL_FROM') ? EMAIL_FROM : '';

    $block = [];
    if ($format === 'HTML') {
        $block['EMAIL_MESSAGE_HTML'] = sprintf(
            $htmlTemplate,
            zen_output_string_protected($storeName),
            zen_output_string_protected($primaryUrl),
            zen_output_string_protected($unsubscribeUrl),
            zen_output_string_protected($fromAddress)
        );
    }

    zen_mail(
        $name !== '' ? $name : $email,
        $email,
        sprintf($subjectTemplate, $storeName),
        sprintf($textTemplate, $storeName, $primaryUrl, $unsubscribeUrl, $fromAddress),
        $storeName,
        $fromAddress,
        $block,
        $module
    );
}

/**
 * Optionally tell the store owner about a new confirmed subscriber.
 *
 * @param string $email
 * @param string $format
 * @return void
 */
function scf_notify_store_owner($email, $format)
{
    // Also not a setting. Only a confirmed subscriber reaches this point, so
    // the store owner is told about real signups and nothing else.
    $storeName = defined('STORE_NAME') ? STORE_NAME : '';
    $ownerName = defined('STORE_OWNER') ? STORE_OWNER : $storeName;
    $ownerEmail = defined('STORE_OWNER_EMAIL_ADDRESS') ? STORE_OWNER_EMAIL_ADDRESS : '';

    if ($ownerEmail === '') {
        return;
    }

    zen_mail(
        $ownerName,
        $ownerEmail,
        sprintf(scf_lang('SCF_EMAIL_ADMIN_SUBJECT'), $storeName),
        sprintf(scf_lang('SCF_EMAIL_ADMIN_TEXT'), $email, $format),
        $storeName,
        defined('EMAIL_FROM') ? EMAIL_FROM : '',
        [],
        'social_contact_footer_admin'
    );
}
