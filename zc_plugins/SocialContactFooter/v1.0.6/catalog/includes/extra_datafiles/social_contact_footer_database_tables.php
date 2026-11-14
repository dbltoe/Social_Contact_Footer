<?php
/**
 * Social Contact Footer -- storefront table-name constant.
 *
 * @package  SocialContactFooter
 * @license  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!defined('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS')) {
    define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', DB_PREFIX . 'social_contact_footer_subscribers');
}
