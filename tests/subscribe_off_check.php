<?php
/**
 * The newsletter form switched off.
 *
 * Its own process because SCF_SUBSCRIBE_STATUS is a configuration constant and
 * a constant cannot be redefined. Run from subscribe_flow_check.php.
 */

require __DIR__ . '/_bootstrap.php';

define('IS_ADMIN_FLAG', false);
define('DB_PREFIX', 'zen_');
define('CHARSET', 'utf-8');
define('DIR_WS_CATALOG', '/shop/');
define('DIR_WS_IMAGES', 'images/');
define('DIR_FS_CATALOG', dirname(scf_plugin_dir(), 3) . '/');
define('DIR_WS_TEMPLATE', '/shop/includes/templates/responsive_classic/');
define('HTTP_SERVER', 'https://shop.example.com');
define('STORE_NAME', 'Acme Widgets');
define('STORE_OWNER', 'Acme Widgets');
define('STORE_OWNER_EMAIL_ADDRESS', 'owner@shop.example.com');
define('EMAIL_FROM', 'sales@shop.example.com');
define('FILENAME_DEFAULT', 'index');
define('FILENAME_LOGIN', 'login');
define('FILENAME_PRIVACY', 'privacy');
define('TABLE_CUSTOMERS', 'zen_customers');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');

$PLUGIN = scf_plugin_dir();
foreach (require $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}
define('SCF_STATUS', 'true');
define('SCF_SUBSCRIBE_STATUS', 'false');    // the one under test
define('SCF_SUBSCRIBE_ASK_FORMAT', 'true');
define('SCF_SUBSCRIBE_HONEYPOT', 'true');
define('SCF_SUBSCRIBE_PRIVACY_LINK', 'true');
define('SCF_ICON_ALIGN', 'Center');
define('SCF_CONTACT_LINK', 'Contact Us page');
define('FILENAME_CONTACT_US', 'contact_us');
define('SCF_URL_FACEBOOK', 'AcmeWidgets');

require $PLUGIN . '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php';

$db = new ScfDb();

section('the newsletter form switched off');
check('no form is rendered', scf_render_subscribe_form() === '');
/* The icons and the blog link are the point of the plugin; switching the
 * newsletter off must not take them with it. */
$block = scf_render_block();
check('the icons still appear', strpos($block, 'scf-icons') !== false);
check('and no form markup is left behind', strpos($block, 'scf-subscribe') === false);
check('and no gate script is loaded for a form that is not there',
    strpos($block, '<script') === false);

$_POST = ['scf_action' => 'subscribe', 'securityToken' => 'tok',
    'scf_email' => 'reader@example.com', 'scf_format' => 'HTML'];
$_GET = [];
$_SESSION = ['securityToken' => 'tok', 'scf_form_rendered' => time() - 30];
try {
    scf_handle_request();
} catch (ScfRedirect $e) {
    // expected
}
/* A POST aimed straight at the handler must be refused too -- the form being
 * absent from the page is not the same as the endpoint being closed. */
check('a hand-crafted POST writes nothing',
    $db->countMatching('INSERT INTO zen_social') === 0);
check('and sends nothing', count($GLOBALS['SCF_SENT_MAIL']) === 0);

section('an unsubscribe link still works with the form switched off');
/* Someone who signed up while it was on must still be able to get out, and
 * the store owner switching the form off cannot be allowed to trap them. */
$offDb = new class extends ScfDb {
    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));
        if (stripos($sql, 'SELECT * FROM zen_social') !== false) {
            return new ScfResult([[
                'subscriber_id' => 7, 'subscriber_email' => 'reader@example.com',
                'email_format' => 'HTML', 'status' => 1, 'subscriber_name' => '',
                'confirm_token' => str_repeat('a1b2', 10), 'customers_id' => 0,
            ]]);
        }
        return new ScfResult([]);
    }
};
$db = $offDb;
$_POST = [];
$_GET = ['scf_unsubscribe' => str_repeat('a1b2', 10)];
try {
    scf_handle_request();
} catch (ScfRedirect $e) {
    // expected
}
check('the unsubscribe is honoured',
    preg_match('~SET status = 2~', $db->matching('UPDATE zen_social')) === 1);

scf_done('the off state behaves');
