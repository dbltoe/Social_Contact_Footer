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
 * The Read Me link is built from DIR_WS_CATALOG rather than hard-coded, so it
 * works whatever the store lives at and whatever the admin directory has been
 * renamed to. Zen Cart's shipped `zc_plugins/.htaccess` denies everything then
 * explicitly re-allows `.html`, so readme.html is reachable by design.
 *
 * On v2.2 and later the description is refreshed from this file on every Plugin
 * Manager scan, so the URL self-corrects if the store ever moves. On v1.5.8,
 * v2.0 and v2.1 it is not: those releases update only the `infs` column for a
 * row that already exists, so the description is whatever was captured the
 * first time Plugin Manager saw the plugin. Uninstall and re-install is the
 * only thing that refreshes it there.
 *
 * That is also why nothing state-dependent belongs in this description -- see
 * the note on the name below.
 */
$scfPluginDir = 'zc_plugins/SocialContactFooter/v1.0.0/';
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
 * the thread existed: a "Forum Support Thread" link that 404s is worse than no
 * link, because somebody with a problem follows it.
 *
 * Stored as the permalink to the opening post rather than the bare thread
 * address, on purpose. That post is the authoritative description of the
 * plugin; the thread around it accumulates whatever a support thread
 * accumulates. Somebody arriving from Plugin Manager should land on the former.
 * Anyone who then wants the discussion is one click from it.
 */
$scfForumUrl = 'https://www.zen-cart.com/showthread.php/231169-Social-Contact-Footer?p=1412052#post1412052';

/**
 * Both are styled as buttons matching Plugin Manager's own controls, which use
 * exactly `class="btn btn-primary" role="button"` for Install, Enable, Disable
 * and Uninstall.
 *
 * They end up side by side because Zen Cart wraps every setBoxContent() item in
 * its own `<div class="row">` -- the core buttons are each a separate item, so
 * they stack, whereas this whole description is a single item, so two
 * inline-block buttons within it line up on one row. That holds in v1.5.8 and
 * v3.0.0-dev alike; both build the box through the same tableBlock() method.
 *
 * Spacing is set explicitly rather than inherited. Admin themes differ on
 * whether `.btn` carries a margin, so relying on it left the two buttons
 * touching. Every gap around the pair is the same 6px: the wrapper supplies
 * the space before the first button, and each button supplies the space after
 * itself -- giving before = between = after.
 *
 * The margins are inline on the anchors, which beats any theme `.btn` margin
 * outright, so the result is the same whatever admin template is in use.
 *
 * The wrapper's 10px top margin separates the pair from the description text
 * above. The core's own buttons each sit in their own `<div class="row">` and
 * follow a short Author line, so they need no such gap; these follow a
 * paragraph inside the same row and do.
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
 * The banner below carries the visual weight instead: it goes in the
 * description, which is a TEXT column with no such limit.
 */
$scfName = 'Social Contact Footer';
if ($scfIsOff) {
    $scfName .= ' - Mod Not Turned On';
}

$scfButtonGap = '6px';

$scfLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $scfButtonGap . '">'
    . '<a href="' . $scfReadmeUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $scfButtonGap . ' 0 0">Read Me</a>'
    . '<a href="' . $scfGithubUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $scfButtonGap . ' 0 0">GitHub</a>'
    . '</div>';

/**
 * The support thread, as an ordinary link rather than a third button.
 *
 * Deliberately not a button: Install, Uninstall, Disable, Read Me and GitHub are
 * all things the owner does with the plugin. Asking for help is a different kind
 * of act, and giving it the same weight as Uninstall would be misleading. It
 * sits on its own line below them, left-aligned with the buttons above.
 *
 * Renders nothing while $scfForumUrl is empty.
 */
$scfForumLink = '';
if ($scfForumUrl !== '') {
    $scfForumLink =
        '<div style="margin:8px 0 0;padding:0 0 0 ' . $scfButtonGap . '">'
        . '<a href="' . $scfForumUrl . '" target="_blank" rel="noopener noreferrer">'
        . 'Forum Support Thread</a>'
        . '</div>';
}

return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => $scfName,
    'pluginDescription' =>
        'Adds an owner-configurable block to the storefront footer: social-media icons, '
        . 'a blog link, and an optional newsletter signup that asks each subscriber '
        . 'whether they would prefer HTML or plain-text mail. Every icon stays hidden '
        . 'until you supply a link for it, so nothing appears until you want it to.'
        . $scfLinks
        . $scfForumLink,
    // Shown as the Author in Plugin Manager, and stored in
    // plugin_control.author / plugin_control_versions.author (varchar(64)).
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => 0, // ID from the Zen Cart Plugins Library; 0 until published.
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $scfGithubUrl,
    'pluginGroups' => [],
];
