<?php
/**
 * The storefront block: what gets rendered, and what each configuration value
 * turns into.
 *
 * These functions are pure enough to call directly, so this harness runs the
 * real code rather than reading it. The database is only reached by the request
 * handlers, which are covered separately in subscribe_flow_check.php.
 */

require __DIR__ . '/_bootstrap.php';

define('IS_ADMIN_FLAG', false);
define('DB_PREFIX', 'zen_');
define('CHARSET', 'utf-8');
define('DIR_WS_CATALOG', '/shop/');
define('DIR_WS_IMAGES', 'images/');
/* The repository root doubles as the store root: zc_plugins/ sits directly
 * under it, exactly as it does on a real install, so asset resolution is
 * genuinely exercised rather than stubbed. */
define('DIR_FS_CATALOG', dirname(scf_plugin_dir(), 3) . '/');
define('DIR_WS_TEMPLATE', '/shop/includes/templates/responsive_classic/');
define('HTTP_SERVER', 'https://shop.example.com');
define('HTTPS_SERVER', 'https://shop.example.com');
define('STORE_NAME', 'Acme Widgets');
define('STORE_OWNER_EMAIL_ADDRESS', 'sales@shop.example.com');
define('FILENAME_CONTACT_US', 'contact_us');
define('FILENAME_DEFAULT', 'index');
define('FILENAME_LOGIN', 'login');
define('FILENAME_PRIVACY', 'privacy');
define('STORE_OWNER', 'Acme Widgets');
define('EMAIL_FROM', 'sales@shop.example.com');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');

$PLUGIN = scf_plugin_dir();

/* The plugin's own defaults come from its language file; loading the real one
 * keeps the harness honest about what the visitor actually sees. */
$defines = require $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php';
foreach ($defines as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}

/* Configuration constants. Defined once, so each behaviour is exercised by
 * choosing what to define rather than by re-defining -- which PHP will not do. */
$config = [
    'SCF_STATUS' => 'true',
    'SCF_DISABLE_ON_PAGES' => 'checkout_payment, checkout_confirmation',
    'SCF_ICON_ALIGN' => 'Center',
    'SCF_ICON_STYLE' => 'Brand colors',
    'SCF_ICON_SHAPE' => 'Rounded',
    'SCF_ICON_SOURCE' => 'Built-in SVG',
    'SCF_ICON_SIZE_DESKTOP' => '32',
    'SCF_ICON_SIZE_MOBILE' => '28',
    'SCF_ICON_MONO_COLOR' => '#444444',
    'SCF_ICON_ORDER' => '',
    'SCF_LINK_TARGET' => '_blank',
    'SCF_CONTACT_LINK' => 'Contact Us page',
    'SCF_WRAPPER_BACKGROUND' => '',
    'SCF_BLOG_URL' => 'blog/',
    'SCF_SUBSCRIBE_STATUS' => 'true',
    'SCF_SUBSCRIBE_ASK_FORMAT' => 'true',
    'SCF_SUBSCRIBE_HONEYPOT' => 'true',
    'SCF_SUBSCRIBE_PRIVACY_LINK' => 'true',
    'SCF_SUBSCRIBE_PRIVACY_URL' => '',
    'SCF_URL_FACEBOOK' => 'AcmeWidgets',
    'SCF_URL_X' => '@acme',
    'SCF_URL_MASTODON' => 'acme@mastodon.social',
    'SCF_URL_WHATSAPP' => '+1 (555) 010-9999',
    'SCF_URL_RSS' => 'feed.xml',
];
foreach ($config as $k => $v) { define($k, $v); }

require $PLUGIN . '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php';

section('what a store owner can type into a link field');
/* Every one of these was a decision, and each is the kind of thing a store
 * owner types without thinking about it. */
$hrefs = [
    'a Zen Cart page name stays on the store' => ['contact_us', 'https://shop.example.com/index.php?main_page=contact_us'],
    'a store folder is prefixed with the catalog path' => ['blog/', '/shop/blog/'],
    'a root-relative path is left alone' => ['/news/', '/news/'],
    'a bare address becomes mailto:' => ['sales@example.com', 'mailto:sales@example.com'],
    'an explicit mailto: is left alone' => ['mailto:x@example.com', 'mailto:x@example.com'],
    'tel: is left alone' => ['tel:+15550109999', 'tel:+15550109999'],
    'a full address is used as given' => ['https://example.com/x', 'https://example.com/x'],
    'a protocol-relative address becomes https' => ['//example.com/x', 'https://example.com/x'],
    'a bare domain gets https://' => ['example.com/acme', 'https://example.com/acme'],
    'a leading ./ is normalized away' => ['./blog/', '/shop/blog/'],
    'climbing above the catalog root is not allowed' => ['../../etc/passwd', '/shop/etc/passwd'],
    'a file extension is a path, not a domain' => ['feed.xml', '/shop/feed.xml'],
];
foreach ($hrefs as $label => $pair) {
    $got = scf_build_href('facebook', $pair[0]);
    check("$label: " . var_export($pair[0], true) . ' -> ' . var_export($got, true),
        $got === $pair[1]);
}

section('and what it must refuse');
/* The value lands in an href attribute, so a scheme that executes is the whole
 * risk here. Escaping alone would not help: javascript: survives it intact. */
foreach ([
    'javascript:alert(1)',
    'JaVaScRiPt:alert(1)',
    'data:text/html;base64,PHNjcmlwdD4=',
    'vbscript:msgbox(1)',
    'file:///etc/passwd',
    "java\nscript:alert(1)",
    ' javascript:alert(1)',
] as $nasty) {
    $got = scf_build_href('facebook', $nasty);
    check('rejected: ' . var_export($nasty, true) . ($got === '' ? '' : ' -> ' . $got),
        $got === '' || preg_match('~^(javascript|data|vbscript|file):~i', $got) !== 1);
}
check('an empty value produces no link', scf_build_href('facebook', '') === '');
check('whitespace alone produces no link', scf_build_href('facebook', "   \t ") === '');

section('the handle-shaped fields');
/* The whole point of asking for a fragment rather than a URL is that a
 * fragment is hard to get wrong -- so the plugin has to be generous about how
 * it is written. */
$networks = scf_networks();
check('a bare handle is expanded by the template',
    scf_network_href('facebook', $networks['facebook'], 'AcmeWidgets')
        === 'https://www.facebook.com/AcmeWidgets');
check('a leading @ is stripped, since people write handles that way',
    scf_network_href('x', $networks['x'], '@acme') === scf_network_href('x', $networks['x'], 'acme'));
check('a full profile URL is still accepted',
    scf_network_href('x', $networks['x'], 'https://x.com/acme') === 'https://x.com/acme');
check('a handle is URL-encoded on the way in',
    strpos(scf_network_href('facebook', $networks['facebook'], 'a b'), ' ') === false);
check('a handle with markup in it is refused outright',
    scf_network_href('facebook', $networks['facebook'], '"><script>') === '');
check('WhatsApp keeps only the digits',
    scf_network_href('whatsapp', $networks['whatsapp'], '+1 (555) 010-9999')
        === scf_network_href('whatsapp', $networks['whatsapp'], '15550109999'));
check('a WhatsApp entry with no digits at all is refused',
    scf_network_href('whatsapp', $networks['whatsapp'], '(none)') === '');
/* Mastodon is federated: the instance is part of the handle and cannot be
 * assumed, so a bare username is not enough to build a link from. */
check('Mastodon needs the instance',
    scf_network_href('mastodon', $networks['mastodon'], 'acme') === '');
check('and builds instance/@user when it has one',
    scf_network_href('mastodon', $networks['mastodon'], 'acme@mastodon.social')
        === 'https://mastodon.social/@acme');
check('a Mastodon instance that is not a hostname is refused',
    scf_network_href('mastodon', $networks['mastodon'], 'acme@not a host') === '');

section('the contact icon is derived, never typed');
check('it points at the Contact Us page', scf_contact_href() !== '');
check('and that is an on-site link', scf_is_external(scf_contact_href()) === false);

section('external is decided by host, not by scheme');
check("the store's own https address is not external",
    scf_is_external('https://shop.example.com/blog/') === false);
check('another host is external', scf_is_external('https://facebook.com/acme') === true);
check('a relative path is not external', scf_is_external('/shop/blog/') === false);
check('mailto: is not external in the sense that matters', scf_is_external('mailto:a@b.com') === false);

section('the icon row');
$icons = scf_render_icons();
check('icons are rendered', $icons !== '');
check('every configured network appears once',
    substr_count($icons, '<li class="scf-icon-item">') === count(scf_active_networks()));
check('the contact icon is among them', strpos($icons, 'scf-icon-email') !== false);
check('an unconfigured network is absent', strpos($icons, 'scf-icon-tiktok') === false);
/* An icon is a link with no text, so the accessible name has to come from
 * somewhere -- here a visually hidden span, which also survives CSS failing. */
check('each icon carries a visible-to-screen-readers name',
    substr_count($icons, 'scf-visually-hidden') === substr_count($icons, '<li class="scf-icon-item">'));
check('the SVGs are hidden from the accessibility tree, so the name is not doubled',
    substr_count($icons, 'aria-hidden="true"') === substr_count($icons, '<svg'));
check('new tabs get rel="noopener noreferrer"',
    substr_count($icons, 'rel="noopener noreferrer"') === substr_count($icons, 'target="_blank"'));
check('the E-Mail icon is labelled the way Zen Cart spells it',
    strpos($icons, 'title="E-Mail"') !== false);
check('nothing unescaped reached an attribute',
    preg_match('~(?:href|title|style)="[^"]*<~', $icons) === 0);

section('brand colors are data, so they go inline; everything else is CSS');
check('a brand background is set inline', strpos($icons, 'background-color:#') !== false);
check('no size is set inline -- the stylesheet owns that',
    preg_match('~style="[^"]*(?:width|height|font-size)~', $icons) === 0);
foreach (['#ABC', '#a1b2c3', '#a1b2c3ff', 'rebeccapurple', 'rgb(1, 2, 3)',
          'rgba(1,2,3,.5)', 'hsl(120, 50%, 50%)'] as $ok) {
    check("a valid color survives: $ok", scf_css_color($ok) !== '');
}
/* A rejected color falls back to a safe gray rather than to nothing: an icon
 * with no color at all reads as a rendering fault, whereas a gray badge just
 * looks deliberate. What matters is that none of the input survives. */
foreach (['red;background:url(x)', 'expression(alert(1))', '#fff"onload="x',
          'url(javascript:alert(1))', 'rgb(1,2,3);x:y', '#fff;}', ''] as $bad) {
    $got = scf_css_color($bad);
    check('rejected as a color: ' . var_export($bad, true) . ' -> ' . $got,
        $got === '#444444');
}
check('the fallback cannot itself break out of the style attribute',
    preg_match('~^#[0-9a-f]{6}$~i', scf_css_color('nonsense;')) === 1);

section('the icon size fields cannot produce broken CSS');
check('a plain number is used', scf_icon_size('SCF_ICON_SIZE_DESKTOP', 32) === 32);
check('a missing key falls back', scf_icon_size('SCF_NO_SUCH_SIZE', 40) === 40);

section('the whole block');
$block = scf_render_block();
check('the block renders', $block !== '');
check('it has the wrapper the stylesheet and script both look for',
    strpos($block, 'id="scfWrapper"') !== false);
check('icons come before the blog line',
    strpos($block, 'scf-icons') < strpos($block, 'scf-blog'));
check('the blog line comes before the newsletter form',
    strpos($block, 'scf-blog') < strpos($block, 'scf-subscribe'));
check('the script is loaded after the markup it gates, so it needs no ready handler',
    strpos($block, '<script') > strpos($block, 'scf-subscribe'));
/* Tag balance, not attribute syntax: void elements are self-closed and HTML5
 * boolean attributes given a value first, since both are valid HTML that an
 * XML parser refuses. An unbalanced tag here would leak into the template and
 * break the page around it, which is the thing worth catching. */
check('the block is balanced enough to parse',
    (static function ($html) {
        $html = preg_replace('~<(br|hr|img|input|meta|link)([^>]*?)/?>~i', '<$1$2/>', $html);
        foreach (['required', 'checked', 'disabled', 'readonly', 'multiple', 'selected', 'hidden'] as $flag) {
            $html = preg_replace('~(\s)' . $flag . '(?=[\s/>])~i', '$1' . $flag . '="' . $flag . '"', $html);
        }
        libxml_use_internal_errors(true);
        $ok = simplexml_load_string('<div>' . $html . '</div>') !== false;
        libxml_clear_errors();
        return $ok;
    })(preg_replace('~<script.*?</script>~s', '', $block)));

section('the newsletter form');
$form = scf_render_subscribe_form();
check('the form renders', $form !== '');
check('there is no name field -- nothing collects a name',
    stripos($form, 'name="scf_name"') === false && stripos($form, 'subscriber_name') === false);
/* Pre-selecting a format is the kind of default that turns a consent record
 * into a guess, so neither radio is checked. */
check('neither mail format is pre-selected', stripos($form, 'checked') === false);
check('both formats are offered',
    stripos($form, 'HTML') !== false && stripos($form, 'TEXT') !== false);
check('the honeypot is present', strpos($form, 'scf_website') !== false);
check('and it is hidden from screen readers as well as from sight',
    preg_match('~scf_website.{0,400}~s', $form) === 1
    && (strpos($form, 'aria-hidden="true"') !== false || strpos($form, 'tabindex="-1"') !== false));
/* The dwell time is kept in the session, not in a form field: a hidden
 * timestamp is the first thing a submitter edits. */
check('rendering the form stamps the session for the dwell-time trap',
    isset($_SESSION['scf_form_rendered']) && $_SESSION['scf_form_rendered'] > 0);
check('the privacy link is offered', stripos($form, 'scf-privacy') !== false);
check('the address field is a real email input, so a phone shows the right keyboard',
    strpos($form, 'type="email"') !== false);
check('every input is labelled',
    substr_count($form, '<label') >= substr_count($form, '<input type="radio"'));

section('the block knows when to stay away');
/* A social block in the middle of paying is a distraction at exactly the wrong
 * moment, and one that can lose a sale. */
$_GET['main_page'] = 'checkout_payment';
check('it is hidden on an excluded page', scf_block_is_visible() === false);
$_GET['main_page'] = 'checkout_confirmation';
check('the exclusion list is split on commas and trimmed', scf_block_is_visible() === false);
$_GET['main_page'] = 'index';
check('and shown everywhere else', scf_block_is_visible() === true);
$_GET['main_page'] = 'checkout_paymen';
check('the match is exact, not a prefix', scf_block_is_visible() === true);
unset($_GET['main_page']);

section('assets are served from the plugin, and only from there');
$css = scf_stylesheet_href();
$js = scf_script_href();
check('the stylesheet resolves', $css !== '');
check('the script resolves', $js !== '');
foreach (['stylesheet' => $css, 'script' => $js] as $what => $href) {
    check("the $what is loaded from this store", preg_match('~^https?://~i', $href) !== 1);
    /* zc_plugins/.htaccess denies everything and re-allows a fixed list of
     * extensions; css and js are both on it. */
    check("the $what has an extension that .htaccess allows",
        preg_match('~\.(css|js)$~', $href) === 1);
}

section('the observer');
require $PLUGIN . '/catalog/includes/classes/observers/auto.social_contact_footer.php';
/* Zen Cart derives the class name from the filename via base::camelize(), so
 * the two have to stay in step or the observer is silently never instantiated. */
check('the class name matches the filename Zen Cart derives it from',
    class_exists('zcObserverSocialContactFooter'));
$observer = new zcObserverSocialContactFooter();
$attached = $GLOBALS['SCF_ATTACHED'];
/* NOTIFY_FOOTER_END has been there since long before v1.5.8 and is still on
 * v3.0.0 -- it is the one that has to be present, because the newer
 * NOTIFY_FOOTER_AFTER_NAVSUPP only arrived in v2.2.0 and would leave the block
 * invisible on half the supported range if it were relied on alone. */
check('it observes NOTIFY_FOOTER_END, present on every supported release',
    in_array('NOTIFY_FOOTER_END', $attached, true));
check('and NOTIFY_FOOTER_AFTER_NAVSUPP, the better spot where it exists',
    in_array('NOTIFY_FOOTER_AFTER_NAVSUPP', $attached, true));
check('the stylesheet goes in the head, not the body',
    in_array('NOTIFY_HTML_HEAD_END', $attached, true));

/* Both footer notifiers fire on a v2.2+ store. Without the guard the whole
 * block -- form included -- would be emitted twice on the same page. */
ob_start();
$observer->updateNotifyFooterAfterNavsupp(null, 'NOTIFY_FOOTER_AFTER_NAVSUPP');
$observer->updateNotifyFooterEnd(null, 'NOTIFY_FOOTER_END');
$emitted = ob_get_clean();
check('the block is emitted', strpos($emitted, 'id="scfWrapper"') !== false);
check('and only once, though both footer notifiers fired',
    substr_count($emitted, 'id="scfWrapper"') === 1);

/* Whichever fires first should win, so the fallback works on a store whose
 * template has no navsupp notifier at all. */
$fallbackOnly = new zcObserverSocialContactFooter();
ob_start();
$fallbackOnly->updateNotifyFooterEnd(null, 'NOTIFY_FOOTER_END');
$out = ob_get_clean();
check('the fallback notifier alone still emits the block',
    substr_count($out, 'id="scfWrapper"') === 1);

scf_done('the storefront block renders and refuses what it should');
