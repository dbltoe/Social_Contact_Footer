<?php
/**
 * Social Contact Footer -- keeps the Plugin Manager row telling the truth.
 *
 * THE PROBLEM
 * -----------
 * `manifest.php` appends " - Mod Not Turned On" to the plugin name while
 * SCF_STATUS is not 'true', so an installed-but-dormant plugin says so in the
 * Plugin Manager list. That only works if the stored row is refreshed from the
 * manifest, and it is not refreshed everywhere:
 *
 *   v2.2, v2.3, v3.0   PluginManager::updatePluginControl() calls
 *                      upsertMany(), whose SQL ends
 *                      "ON DUPLICATE KEY UPDATE name = VALUES(name),
 *                       description = VALUES(description), ..."   -> refreshed.
 *
 *   v1.5.8, v2.0, v2.1 the same method calls Eloquent's
 *                      upsert($values, ['id'], ['infs']). The third argument is
 *                      the list of columns to update on conflict, and it holds
 *                      only 'infs'. So `name` and `description` are written by
 *                      the INSERT that first created the row and never again.
 *
 * On those three releases the notice would therefore be permanent: the store
 * owner switches the plugin on, and Plugin Manager goes on saying it is off
 * forever. That is worse than not having the notice at all.
 *
 * THE FIX
 * -------
 * Zen Cart loads every installed plugin's `admin/includes/functions/
 * extra_functions/` on every admin page -- through
 * `FileSystem::loadFilesFromPluginsDirectory()` in application_bootstrap.php on
 * v1.5.8 through v2.2, and through `includes/modules/extra_functions.php` on
 * v2.2+ -- so this file runs wherever the store owner happens to be. It
 * compares the stored name with what the current setting implies and corrects
 * it when they differ.
 *
 * Cost: one indexed SELECT on the Plugin Manager page only, and an UPDATE just
 * on the visit after the setting changes. Nothing at all on any other page.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

/**
 * The plugin_control.unique_key this plugin owns -- its directory name under
 * zc_plugins/.
 */
define('SCF_PLUGIN_KEY', 'SocialContactFooter');

/**
 * The name with no notice appended. Must match manifest.php exactly.
 */
define('SCF_PLUGIN_BASE_NAME', 'Social Contact Footer');

/**
 * Appended while the plugin is installed but switched off.
 *
 * Plain text, and it has to stay that way: `plugin_control.name` is varchar(64)
 * in every supported release, and both the v1.5.8 table_view and the v2.2+
 * plugin_manager template echo that column unescaped. Markup would spend 20-40
 * of those 64 characters, and an overflow is truncated -- silently on a normal
 * server, fatally under MySQL STRICT mode -- which mid-tag would put broken
 * HTML into the plugin list.
 */
define('SCF_PLUGIN_OFF_SUFFIX', ' - Mod Not Turned On');

/**
 * Correct plugin_control.name if it disagrees with the current setting.
 *
 * @return void
 */
function scf_admin_sync_plugin_name()
{
    global $db;

    // Only the Plugin Manager displays this column, so only that page needs to
    // pay for the check.
    $page = isset($_GET['cmd']) ? (string)$_GET['cmd'] : '';
    if ($page !== 'plugin_manager') {
        return;
    }

    if (!defined('TABLE_PLUGIN_CONTROL') || !defined('SCF_STATUS')) {
        return;
    }

    $expected = SCF_PLUGIN_BASE_NAME;
    if (SCF_STATUS !== 'true') {
        $expected .= SCF_PLUGIN_OFF_SUFFIX;
    }

    $result = $db->Execute(
        "SELECT name FROM " . TABLE_PLUGIN_CONTROL . "
          WHERE unique_key = '" . zen_db_input(SCF_PLUGIN_KEY) . "'
          LIMIT 1"
    );

    // No row yet: Plugin Manager has not scanned since the files were uploaded.
    // Its own INSERT will carry the right name, so there is nothing to correct.
    if ($result->EOF || $result->fields['name'] === $expected) {
        return;
    }

    $db->Execute(
        "UPDATE " . TABLE_PLUGIN_CONTROL . "
            SET name = '" . zen_db_input($expected) . "'
          WHERE unique_key = '" . zen_db_input(SCF_PLUGIN_KEY) . "'
          LIMIT 1"
    );
}

// The Plugin Manager builds its table after all of this has loaded, so
// correcting the row here means the page renders the corrected value on the
// same request rather than one refresh later.
scf_admin_sync_plugin_name();
