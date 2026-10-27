<?php
/**
 * Social Contact Footer -- storefront autoloader configuration.
 *
 * Breakpoint 178 runs immediately after `init_observers.php` (175) and before
 * `init_header.php` (180), so the subscription POST is handled -- and any
 * redirect issued -- before a page module has produced output. Sessions,
 * language files, `$messageStack` and the general function library are all in
 * place well before this point.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[178][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_social_contact_footer.php',
];
