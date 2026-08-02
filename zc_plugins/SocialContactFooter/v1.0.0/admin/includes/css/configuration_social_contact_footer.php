<?php
/**
 * Social Contact Footer -- larger type on this plugin's Configuration page.
 *
 * WHY THIS FILE IS A .php AND NOT A .css
 * --------------------------------------
 * `admin/includes/admin_html_head.php` walks every installed plugin and, for
 * the page being viewed, pulls in from that plugin's `admin/includes/css/`:
 *
 *     <page>.css          linked
 *     <page>_*.css        linked
 *     <page>_*.php        REQUIRED -- executed, and whatever it prints lands
 *                         inside <head>
 *
 * Identical in v1.5.8, v2.0, v2.1, v2.2, v2.3 and v3.0, so this is safe on the
 * whole supported range.
 *
 * A plain `configuration.css` would work, but it would apply to *every*
 * configuration group in the store -- General, Minimum Values, E-Mail Options,
 * everyone's. Restyling another developer's settings page as a side effect of
 * installing this plugin is not on. Being PHP, this file can read the group id
 * and print nothing at all unless the store owner is actually looking at the
 * Social Contact Footer group.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

/**
 * The group being viewed. Zen Cart's configuration page takes it as `gID`.
 */
$scfViewedGroup = isset($_GET['gID']) ? (int)$_GET['gID'] : 0;

if ($scfViewedGroup > 0 && isset($db)) {
    // Looked up by title rather than stored, because the id is assigned by the
    // store when the group is created and differs between installations.
    $scfGroup = $db->Execute(
        "SELECT configuration_group_id
           FROM " . TABLE_CONFIGURATION_GROUP . "
          WHERE configuration_group_title = 'Social Contact Footer'
          LIMIT 1"
    );

    if (!$scfGroup->EOF && (int)$scfGroup->fields['configuration_group_id'] === $scfViewedGroup) {
        /* 1.2rem floor, same as the Footer Newsletter Subscribers page.
         *
         * Form controls are listed explicitly: font-size is not inherited into
         * input, select, textarea or button, so a rule on the container alone
         * would leave every field and button at the browser default while the
         * text around them grew. */
        echo '<style>' . "\n"
            . '.container-fluid{font-size:1.2rem}' . "\n"
            . '.container-fluid input,.container-fluid select,'
            . '.container-fluid textarea,.container-fluid button,'
            . '.container-fluid .btn{font-size:1.2rem;line-height:1.4}' . "\n"
            . '.container-fluid td,.container-fluid th,'
            . '.container-fluid label,.container-fluid p,'
            . '.container-fluid a{font-size:1.2rem}' . "\n"
            /* The help text under each setting is the part most worth reading
             * and the part usually set smallest. */
            . '.container-fluid .configurationDescription{font-size:1.2rem;line-height:1.5}' . "\n"
            /* Bootstrap sets `code` to font-size:90%, which inside a 1.2rem
             * container lands at 1.08rem -- under the floor, and applied to
             * exactly the strings that must be read character by character:
             * page names, paths, true/false. Only the size is overridden, so
             * the familiar pink-on-grey styling is left alone. */
            . '.container-fluid code,.container-fluid kbd,'
            . '.container-fluid pre,.container-fluid samp{font-size:1.2rem}' . "\n"
            . '</style>' . "\n";
    }
}
