<?php
/**
 * Social Contact Footer -- storefront request handling.
 *
 * Loaded at autoload breakpoint 178 (see the plugin's auto_loaders config).
 * All of the work lives in the plugin's extra_functions file; this script only
 * decides whether there is anything to do.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!function_exists('scf_handle_request')) {
    // extra_functions did not load for some reason; do nothing rather than
    // taking the storefront down with a fatal error.
    return;
}

if (isset($_GET['scf_confirm']) || isset($_GET['scf_unsubscribe'])
    || isset($_GET['scf_activate']) || isset($_POST['scf_action'])
) {
    scf_handle_request();
}
