<?php
/**
 * The Footer Newsletter Subscribers page, and the Configuration page stylesheet.
 *
 * Static: the page needs a full Zen Cart admin to render, so this reads the
 * source. Enough for what is being checked -- landmark and table structure, and
 * the declared colours -- and the contrast is computed rather than remembered.
 */

require __DIR__ . '/_bootstrap.php';

$PLUGIN = scf_plugin_dir();
$page = file_get_contents($PLUGIN . '/admin/social_contact_footer_subscribers.php');
$configCss = file_get_contents($PLUGIN . '/admin/includes/css/configuration_social_contact_footer.php');
$lang = require $PLUGIN . '/admin/includes/languages/english/lang.social_contact_footer_subscribers.php';

function lum($hex)
{
    $hex = ltrim($hex, '#');
    $c = [];
    foreach ([0, 2, 4] as $i) {
        $v = hexdec(substr($hex, $i, 2)) / 255;
        $c[] = ($v <= 0.03928) ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function ratio($a, $b)
{
    $x = lum($a);
    $y = lum($b);
    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

section('landmarks');
/* Zen Cart's admin header supplies a <nav> on v2.2+ and its footer a <footer>,
 * but no release wraps the page body in <main> -- so without this the whole page
 * sits outside any landmark, which is what an audit reports. */
check('the content is inside a <main> landmark',
    substr_count($page, '<main ') === 1 && substr_count($page, '</main>') === 1);
check('<main> is named by the page heading rather than a duplicated string',
    strpos($page, 'aria-labelledby="scfPageHeading"') !== false
    && strpos($page, 'id="scfPageHeading"') !== false);
check('the filter form is a search landmark', strpos($page, 'role="search"') !== false);
check('and that landmark is labelled', strpos($page, 'SCF_ADMIN_ARIA_FILTERS') !== false);
check('the import block is a labelled region',
    strpos($page, 'aria-labelledby="scfImportHeading"') !== false
    && strpos($page, 'id="scfImportHeading"') !== false);
check('the header-image block is a labelled region too',
    strpos($page, 'aria-labelledby="scfHeaderHeading"') !== false
    && strpos($page, 'id="scfHeaderHeading"') !== false);
check('every section is closed',
    substr_count($page, '</section>') === substr_count($page, '<section '));
check('paging is a labelled <nav>',
    strpos($page, '<nav class="scf-admin-pager"') !== false
    && strpos($page, 'SCF_ADMIN_ARIA_PAGER') !== false);
check('the pager nav is closed', substr_count($page, '</nav>') === substr_count($page, '<nav '));

section('table semantics');
check('the table has a caption', strpos($page, '<caption') !== false);
/* Clipped, not display:none -- the latter removes it from the accessibility
 * tree as well as the screen. */
check('the caption is clipped, so it is still announced',
    preg_match('~\.scf-visually-hidden\{[^}]*clip:~', $page) === 1
    && preg_match('~\.scf-visually-hidden\{[^}]*display:\s*none~', $page) === 0);
check('every column heading carries scope="col"', substr_count($page, '<th scope="col">') === 7);
check('no heading is left without a scope',
    substr_count($page, '<th scope=') === substr_count($page, '<th '));
check('the address is the row header, so cells are announced with it',
    strpos($page, '<th scope="row">') !== false);
check('the Name column is gone -- nothing collects a name',
    strpos($page, 'SCF_ADMIN_HEADING_NAME') === false);

section('buttons are distinguishable out of context');
/* Fifty rows of "Delete" with nothing to tell them apart is the classic
 * ambiguous-control finding. */
foreach (['DELETE', 'UNSUBSCRIBE', 'CONFIRM', 'INVITE', 'REINVITE'] as $which) {
    check("the $which button names its subscriber",
        strpos($page, 'SCF_ADMIN_ARIA_' . $which) !== false);
    check("SCF_ADMIN_ARIA_$which takes the address as a placeholder",
        isset($lang['SCF_ADMIN_ARIA_' . $which])
        && strpos($lang['SCF_ADMIN_ARIA_' . $which], '%s') !== false);
}
check('every aria-label is escaped before it reaches the attribute',
    substr_count($page, 'aria-label="<?php echo zen_output_string_protected(')
    === substr_count($page, 'aria-label="<?php'));

section('contrast, computed');
preg_match('~\.scf-admin-status-0\{color:(#[0-9a-f]{6})~i', $page, $m0);
preg_match('~\.scf-admin-status-1\{color:(#[0-9a-f]{6})~i', $page, $m1);
preg_match('~\.scf-admin-status-2\{color:(#[0-9a-f]{6})~i', $page, $m2);
/* Against white AND against the light zebra stripe some admin themes paint
 * behind table rows -- the theme decides which is actually behind the text. */
foreach ([
    'awaiting confirmation' => $m0[1] ?? '',
    'subscribed' => $m1[1] ?? '',
    'unsubscribed' => $m2[1] ?? '',
] as $label => $colour) {
    check("$label declares a colour", $colour !== '');
    if ($colour === '') { continue; }
    foreach (['#ffffff' => 'white', '#f5f5f5' => 'a zebra stripe'] as $bg => $bgName) {
        $r = ratio($colour, $bg);
        check(sprintf('%s (%s) clears 4.5:1 on %s -- %.2f:1', $label, $colour, $bgName, $r), $r >= 4.5);
    }
}
check('unsubscribed is no lighter than the #767676 floor that was asked for',
    isset($m2[1]) && lum($m2[1]) <= lum('#767676'));
/* Unsubscribed reads as the same red as the Delete button, but uses the
 * button's border/hover shade: the face colour reaches 4.5:1 only with white
 * text ON it, and fails as text on a near-white row. */
preg_match('~\.btn-danger:hover[^{]*\{background-color:(#[0-9A-Fa-f]{6})~', $page, $hover);
check('unsubscribed uses a colour the Delete button actually carries',
    isset($m2[1], $hover[1]) && strcasecmp($m2[1], $hover[1]) === 0);
preg_match('~\.btn-danger\{background-color:(#[0-9A-Fa-f]{6})~', $page, $face);
check('the delete button declares its own background', !empty($face[1]));
if (!empty($face[1])) {
    $r = ratio('#ffffff', $face[1]);
    check(sprintf('white on %s clears 4.5:1 -- %.2f:1', $face[1], $r), $r >= 4.5);
    check("it is not Bootstrap's #d9534f, which is 3.96:1", strtolower($face[1]) !== '#d9534f');
}
preg_match_all('~\.btn-danger[^{]*\{background-color:(#[0-9A-Fa-f]{6})~', $page, $all);
foreach (array_unique($all[1]) as $shade) {
    $r = ratio('#ffffff', $shade);
    check(sprintf('hover/active shade %s also clears 4.5:1 -- %.2f:1', $shade, $r), $r >= 4.5);
}

section('text size floor');
/* Browsers do not inherit font-size into form controls, so a rule on the
 * container alone leaves every field and button at the default. */
foreach (['input', 'select', 'textarea', 'button', '.btn'] as $ctrl) {
    check("form control $ctrl is given an explicit size",
        preg_match('~\.scf-admin-page[^{]*' . preg_quote($ctrl, '~') . '[^{]*\{[^}]*font-size:1\.2rem~', $page) === 1);
}
/* Bootstrap sets code to 90%, which inside a 1.2rem container lands under the
 * floor -- on exactly the strings read character by character. */
check('code spans are pinned to the floor too',
    preg_match('~\.scf-admin-page code[^{]*\{font-size:1\.2rem~', $page) === 1);
$sizes = [];
preg_match_all('~font-size:\s*([0-9.]+)rem~', $page, $sm);
foreach ($sm[1] as $s) { $sizes[] = (float)$s; }
check('nothing on the page is declared below 1.2rem: min is ' . (min($sizes) ?: 0) . 'rem',
    min($sizes) >= 1.2);

section('the configuration page stylesheet');
/* A plain configuration.css would restyle every settings group in the store.
 * Being PHP, this one can read the group id and print nothing otherwise. */
check("it only prints for this plugin's own group",
    strpos($configCss, "configuration_group_title = 'Social Contact Footer'") !== false);
check('it prints nothing when no group is being viewed',
    strpos($configCss, '$scfViewedGroup > 0') !== false);
check('it is guarded against direct access',
    strpos($configCss, "defined('IS_ADMIN_FLAG')") !== false);

section('one submission per click');
/* Every writing action here creates an account or sends mail, so a double-click
 * must not become two requests. Disabling before submit would cancel the very
 * submission it protects. */
check('the guard disables the button after submission begins, via setTimeout',
    preg_match('~setTimeout\(function \(\) \{\s*button\.disabled = true~', $page) === 1);
check('export is exempt -- it downloads without navigating away',
    strpos($page, "form[name=\"scfExport\"]") === false);

scf_done('landmarks, table semantics and contrast all check out');
