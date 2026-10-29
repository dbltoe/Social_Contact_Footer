<?php
/**
 * Social Contact Footer -- plugin manifest.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

/**
 * Links shown in the Plugin Manager, alongside Install / Uninstall / Disable.
 *
 * Zen Cart stores `pluginDescription` in `plugin_control.description` (a TEXT
 * column) and echoes it into the Plugin Manager's info box **as raw HTML**,
 * without escaping, on every release from v1.5.8 to v3.0.0. That box is also
 * where the action buttons live, and the description is not used in the plugin
 * list table -- so markup placed here appears exactly once, right where the
 * store owner is already looking, with no core file changed and no notifier to
 * hook (there isn't one for that panel).
 *
 * The ReadMe link is built from DIR_WS_CATALOG rather than hard-coded, so it
 * works whatever the store lives at and whatever the admin directory has been
 * renamed to. Zen Cart's shipped `zc_plugins/.htaccess` denies everything then
 * explicitly re-allows `.html`, so readme.html is reachable by design.
 *
 * On v2.2 and later the description is refreshed from this file on every Plugin
 * Manager scan, so the URL self-corrects if the store ever moves. On v1.5.8,
 * v2.0 and v2.1 it is not: those releases update only the `infs` column for a
 * row that already exists, so the description is whatever was captured the
 * first time Plugin Manager saw the plugin, and only this plugin's installer
 * refreshes it there (at Install, Upgrade and Uninstall). The one
 * state-dependent part, the Settings button, is handled below.
 */
$scfPluginDir = 'zc_plugins/SocialContactFooter/v1.0.5/';
$scfReadmeUrl = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . $scfPluginDir . 'readme.html';
$scfGithubUrl = 'https://github.com/dbltoe/Social_Contact_Footer';

/**
 * The Zen Cart forum's support thread for this plugin.
 *
 * PUT THE URL HERE BEFORE THE FIRST RELEASE, not after.
 *
 * On v2.2 and later a later edit would be picked up on the next Plugin Manager
 * scan. On v1.5.8, v2.0 and v2.1 it would not: those releases write
 * plugin_control.description only on the INSERT that first creates the row, so
 * whatever is here the first time a store scans the plugin is what that store
 * shows for good. Adding the link after people have installed reaches nobody on
 * half the supported range short of an uninstall and re-install.
 *
 * An empty string renders nothing at all, which was the right outcome before
 * the thread existed: a "Forum Thread" link that 404s is worse than no
 * link, because somebody with a problem follows it.
 *
 * Stored as the permalink to the opening post rather than the bare thread
 * address, on purpose. That post is the authoritative description of the
 * plugin; the thread around it accumulates whatever a support thread
 * accumulates. Somebody arriving from Plugin Manager should land on the former.
 * Anyone who then wants the discussion is one click from it.
 */
$scfForumUrl = 'https://www.zen-cart.com/threads/207241';

/**
 * The Settings button: this plugin's Configuration page, shown only once the
 * plugin is installed. Its gID is assigned at install, so it differs from
 * store to store and can't be written here; it's read from the admin page the
 * installer registers (page key configSocialContactFooter). The link is
 * relative ("index.php?cmd=configuration&gID=N") because Plugin Manager is an
 * admin page, so it survives a domain or admin-folder rename.
 *
 * Zen Cart reads manifests only on the Plugin Manager page, but the lookup is
 * still guarded so a manifest read anywhere else (no database, catalog side,
 * CLI) just leaves the button out. On v2.2 and later this file is re-read on
 * every Plugin Manager visit, so the button follows the install state by
 * itself; on v1.5.8, v2.0 and v2.1 the installer saves the re-read
 * description at Install, Upgrade and Uninstall
 * (scfRefreshPluginDescription).
 */
$scfSettingsUrl = '';
if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true && defined('TABLE_ADMIN_PAGES')
    && isset($GLOBALS['db']) && is_object($GLOBALS['db'])) {
    $scfPage = $GLOBALS['db']->Execute(
        "SELECT main_page, page_params FROM " . TABLE_ADMIN_PAGES
        . " WHERE page_key = 'configSocialContactFooter' LIMIT 1"
    );
    if (!$scfPage->EOF && defined($scfPage->fields['main_page'])) {
        $scfSettingsUrl = 'index.php?cmd=' . constant($scfPage->fields['main_page'])
            . '&' . $scfPage->fields['page_params'];
    }
}

/**
 * The buttons are styled like Plugin Manager's own controls, which use
 * exactly `class="btn btn-primary" role="button"` for Install, Enable, Disable
 * and Uninstall.
 *
 * They end up side by side because Zen Cart wraps every setBoxContent() item in
 * its own `<div class="row">` -- the core buttons are each a separate item, so
 * they stack, whereas this whole description is a single item, so the
 * inline-block buttons within it line up on one row. That holds in v1.5.8 and
 * v3.0.0 alike; both build the box through the same tableBlock() method.
 *
 * One row: ReadMe | Settings | Forum Thread | GitHub. A second line would push
 * the Author line and the core buttons further down the panel. Spacing is set
 * inline, which beats any theme `.btn` margin: every gap is 4px (the wrapper
 * supplies the space before the first button, each button the space after
 * itself) and the side padding is 8px rather than Bootstrap's 12px, so all
 * four fit on one line in Plugin Manager's side panel from a 1366px-wide
 * window up (measured on the v2.2 admin).
 *
 * The wrapper's 10px top margin separates the row from the description text
 * above it.
 */
/**
 * "Installed, but not switched on yet."
 *
 * The master switch now defaults to false, so the gap between installing and
 * configuring is a place a store owner can lose track of things: the plugin is
 * listed under Installed, everything looks done, and the storefront shows
 * nothing. This says so where they are already looking.
 *
 * Detection: SCF_STATUS is a configuration constant, and Zen Cart turns every
 * configuration row into a constant during application_top -- which runs before
 * plugin_manager.php calls inspectAndUpdate() to re-read this file. So:
 *
 *   - constant undefined  -> not installed, or the group was removed. Say
 *                            nothing; "not turned on" would be wrong and the
 *                            list already reports Not Installed.
 *   - defined and 'true'  -> live. Say nothing.
 *   - defined, not 'true' -> installed and dormant. Say so.
 *
 * IT DOES NOT CLEAR ITSELF ON EVERY VERSION, which is why this file is only
 * half the mechanism. On v2.2, v2.3 and v3.0 the scan's `upsertMany()` refreshes
 * `name` and `description` on duplicate key, so a re-scan picks this up. On
 * v1.5.8, v2.0 and v2.1 the scan calls Eloquent's
 * `upsert($values, ['id'], ['infs'])` -- and that third argument is the list of
 * columns to update on conflict, so **only `infs` is written**. `name` and
 * `description` are frozen at whatever they were when the row was first
 * inserted, no matter how often Plugin Manager is opened.
 *
 * `scf_admin_sync_plugin_name()`, in this plugin's admin extra_functions,
 * corrects the stored row on every version. Without it this notice would be
 * permanent on half the supported range.
 */
$scfIsOff = defined('SCF_STATUS') && SCF_STATUS !== 'true';

/**
 * The name suffix is deliberately PLAIN TEXT, with no markup at all.
 *
 * `plugin_control.name` is varchar(64) in every supported release, and the scan
 * writes this value straight into it. Markup would cost 20-40 characters of a
 * 64-character budget, and a value that overflows is truncated -- silently on a
 * normal server, fatally under MySQL STRICT mode. Truncation mid-tag would emit
 * broken HTML into the plugin list, because both the v1.5.8 table_view and the
 * v2.2+ plugin_manager template echo this column unescaped.
 *
 *   "Social Contact Footer - Mod Not Turned On" = 41 characters, 23 to spare.
 *
 * The name is also the only place this notice CAN live. `plugin_control.description`
 * looks like the roomier option -- it is a TEXT column, and the info box echoes it
 * as raw HTML -- but on v1.5.8/v2.0/v2.1 updatePluginControl() calls
 * upsert($values, ['id'], ['infs']), so the description is written by the INSERT
 * that creates the row and never again. Anything state-dependent there freezes at
 * whatever was true on the very first scan. The name is rewritten directly by
 * admin/includes/functions/extra_functions/social_contact_footer_admin.php, which
 * is what lets the notice clear when the switch is turned on.
 */
$scfName = 'Social Contact Footer';
if ($scfIsOff) {
    $scfName .= ' - Mod Not Turned On';
}

$scfButtonGap = '4px';
$scfButtonStyle = ' style="margin:0 ' . $scfButtonGap . ' 0 0;padding-left:8px;padding-right:8px"';
$scfButton = static function ($url, $label, $newTab) use ($scfButtonStyle) {
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
        . ($newTab ? ' target="_blank" rel="noopener noreferrer"' : '')
        . ' class="btn btn-primary" role="button"' . $scfButtonStyle . '>' . $label . '</a>';
};

$scfLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $scfButtonGap . '">'
    . $scfButton($scfReadmeUrl, 'ReadMe', true)
    . ($scfSettingsUrl !== '' ? $scfButton($scfSettingsUrl, 'Settings', false) : '')
    // The support thread (every free dbltoe plugin has one, 2026-10-05).
    // Renders nothing while $scfForumUrl is empty.
    . ($scfForumUrl !== '' ? $scfButton($scfForumUrl, 'Forum Thread', true) : '')
    . $scfButton($scfGithubUrl, 'GitHub', true)
    . '</div>';

return [
    'pluginVersion' => 'v1.0.5',
    'pluginName' => $scfName,
    'pluginDescription' =>
        'Adds an owner-configurable block to the storefront footer: social-media icons, '
        . 'a blog link, and an optional newsletter signup that asks each subscriber '
        . 'whether they would prefer HTML or plain-text mail. Every icon stays hidden '
        . 'until you supply a link for it, so nothing appears until you want it to.'
        . $scfLinks,
    // Shown as the Author in Plugin Manager, and stored in
    // plugin_control.author / plugin_control_versions.author (varchar(64)).
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    // ID from the Zen Cart Plugins Library -- the number after "vb" in
    // https://www.zen-cart.com/plugins/social-contact-footer-vb2447
    //
    // Written to plugin_control.zc_contrib_id, and the only thing that makes
    // "a new version is available" work in Plugin Manager. It has to be right
    // the FIRST time a store installs the plugin: on v1.5.8/v2.0/v2.1,
    // updatePluginControl() calls upsert($values, ['id'], ['infs']), so on an
    // existing row only `infs` is rewritten and zc_contrib_id keeps whatever
    // the original INSERT put there. v2.2 and later use upsertMany(), which
    // refreshes it on every scan.
    'pluginId' => 2447,
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $scfGithubUrl,
    'pluginGroups' => [],
];
