<?php
/**
 * Social Contact Footer -- admin menu labels (English).
 *
 * These are referenced by the `language_key` values the ScriptedInstaller
 * writes into the `admin_pages` table, so they must exist before the menus are
 * drawn -- hence `extra_definitions`.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$define = [
    'BOX_CONFIGURATION_SOCIAL_CONTACT_FOOTER' => 'Social Contact Footer',
    // "Newsletter" is in the name deliberately: the footer's only signup is the
    // newsletter, and the Tools menu is where a store owner looks for the
    // newsletter tools.
    'BOX_TOOLS_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS' => 'Footer Newsletter Subscribers',
];

return $define;
