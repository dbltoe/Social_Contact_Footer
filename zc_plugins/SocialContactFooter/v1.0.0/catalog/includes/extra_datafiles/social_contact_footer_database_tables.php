<?php
/**
 * Social Contact Footer -- storefront table-name constant.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!defined('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS')) {
    define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', DB_PREFIX . 'social_contact_footer_subscribers');
}
