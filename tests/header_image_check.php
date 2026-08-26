<?php
/**
 * The optional newsletter email header image.
 *
 * The rule: these three emails carry NO image unless the store owner supplies
 * one -- not the store logo, nothing -- and nothing here touches any other email
 * the store sends.
 *
 * That takes doing, because the <img> tag lives in Zen Cart's email template and
 * Email::setTemplateVarsFromDefines() fills its src from the store's settings
 * whenever the caller leaves it empty. Passing nothing gets you the logo. So the
 * tag is pointed at a 1x1 transparent PNG instead.
 */

require __DIR__ . '/_bootstrap.php';

define('HTTP_CATALOG_SERVER', 'https://shop.example.com');
define('HTTP_SERVER', 'https://shop.example.com');
define('DIR_WS_CATALOG', '/');
define('STORE_NAME', 'Test Store');
define('IS_ADMIN_FLAG', true);

$PLUGIN = scf_plugin_dir();
require $PLUGIN . '/shared/email_header.php';

section('with no header image supplied');
check('the fixture starts clean (delete email/header.* to run this)',
    scf_header_image_path() === '');

$block = scf_header_image_block();
check('a block is still returned -- an empty one would make Zen Cart insert the store logo',
    !empty($block));
check('the logo variable is supplied, so setTemplateVarsFromDefines() cannot fill it in',
    !empty($block['EMAIL_LOGO_FILE']));
check('it points at the transparent spacer',
    substr($block['EMAIL_LOGO_FILE'], -11) === '/spacer.png');
check('the spacer ships with the plugin', is_file($PLUGIN . '/email/spacer.png'));
$size = @getimagesize($PLUGIN . '/email/spacer.png');
check('the spacer is a real 1x1 PNG',
    is_array($size) && $size[0] === 1 && $size[1] === 1 && $size['mime'] === 'image/png');
check('and is tiny', filesize($PLUGIN . '/email/spacer.png') < 200);
/* A mail client with images blocked shows alt text. A store name there would be
 * a visible header by another route. */
check('the alt text is empty, so nothing shows even when images are blocked',
    $block['EMAIL_LOGO_ALT_TEXT'] === '' && $block['EMAIL_LOGO_ALT_TITLE_TEXT'] === '');
check('no width or height is forced onto it',
    $block['EMAIL_LOGO_WIDTH'] === '' && $block['EMAIL_LOGO_HEIGHT'] === '');

section('with a header image supplied');
$fixture = $PLUGIN . '/email/header.png';
copy($PLUGIN . '/email/spacer.png', $fixture);

check('the file is found', basename(scf_header_image_path()) === 'header.png');
$block = scf_header_image_block();
check('the logo variable points at it', substr($block['EMAIL_LOGO_FILE'], -11) === '/header.png');
check('the URL is absolute, so it resolves in a mail client',
    strpos($block['EMAIL_LOGO_FILE'], 'https://') === 0);
check("it is served from the plugin's own email directory, not the store's",
    strpos($block['EMAIL_LOGO_FILE'], '/zc_plugins/') !== false
    && strpos($block['EMAIL_LOGO_FILE'], '/email/') !== false);
check('the store name becomes the alt text once there is something to describe',
    $block['EMAIL_LOGO_ALT_TEXT'] === 'Test Store');
check('still no forced width or height, so the image is not distorted',
    $block['EMAIL_LOGO_WIDTH'] === '' && $block['EMAIL_LOGO_HEIGHT'] === '');

section('search order');
copy($PLUGIN . '/email/spacer.png', $PLUGIN . '/email/header.jpg');
check('jpg wins over png, matching the documented order',
    basename(scf_header_image_path()) === 'header.jpg');
unlink($PLUGIN . '/email/header.jpg');
check('png is used again once the jpg is gone',
    basename(scf_header_image_path()) === 'header.png');
unlink($fixture);
check('removing the file returns the emails to no image at all',
    scf_header_image_path() === ''
    && substr(scf_header_image_block()['EMAIL_LOGO_FILE'], -11) === '/spacer.png');

section('the spacer is never mistaken for a header');
check('spacer is not one of the names searched',
    !in_array('spacer', scf_header_image_extensions(), true) && scf_header_image_path() === '');
/* These five and no others: Zen Cart's shipped zc_plugins/.htaccess re-allows
 * exactly jpe?g|gif|webp|png among image types. */
check('the accepted formats are exactly those the zc_plugins allowlist serves',
    scf_header_image_extensions() === ['jpg', 'jpeg', 'png', 'gif', 'webp']);

section('nothing else the store sends is affected');
$catalog = file_get_contents($PLUGIN . '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php');
$invite = file_get_contents($PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_invite.php');
$upload = file_get_contents($PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_header_image.php');

check('the block is applied only where this plugin builds its own message',
    substr_count($catalog, 'scf_header_image_block()') === 1
    && substr_count($invite, 'scf_header_image_block()') === 1);
foreach (['EMAIL_LOGO_FILENAME', 'DIR_FS_EMAIL'] as $needle) {
    check("nothing writes to the store's own email settings ($needle)",
        strpos($upload, $needle) === false);
}
check("the uploader only ever writes inside the plugin's own directory",
    strpos($upload, 'SCF_HEADER_IMAGE_DIR') !== false && strpos($upload, 'DIR_FS_CATALOG') === false);
check('the image is applied to the HTML part only',
    preg_match('~if \(\$format === \'HTML\'\).{0,1400}scf_header_image_block~s', $catalog) === 1);

section('constants are guarded');
/* This ran on a storefront request while a visitor was subscribing, and used
 * HTTP_SERVER and DIR_WS_CATALOG unguarded -- a fatal Error on PHP 8. */
$shared = file_get_contents($PLUGIN . '/shared/email_header.php');
check('the server constant is checked with defined() before use',
    preg_match("~defined\('HTTP_CATALOG_SERVER'\)~", $shared) === 1
    && preg_match("~defined\('HTTP_SERVER'\)~", $shared) === 1);
check('DIR_WS_CATALOG is too', preg_match("~defined\('DIR_WS_CATALOG'\)~", $shared) === 1);

scf_done('no image unless one is supplied, and nothing else is touched');
