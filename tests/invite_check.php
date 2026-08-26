<?php
/**
 * Invite Registration: eligibility, account creation, the emailed links, the
 * re-send, and the storefront side that accepts an invitation.
 *
 * A recording fake database stands in for the real one, so this asserts what SQL
 * is issued rather than what the code looks like. Both halves run in one process
 * because the catalog function library only requires IS_ADMIN_FLAG to be
 * defined, not to be false.
 *
 * Run with `activation-column` to exercise a v2.2.0+ schema; the default run
 * spawns that as a child, because the column lookup is memoised per request.
 */

require __DIR__ . '/_bootstrap.php';

define('IS_ADMIN_FLAG', true);
define('DB_PREFIX', 'zen_');
define('CHARSET', 'utf-8');
define('TABLE_CUSTOMERS', 'zen_customers');
define('TABLE_ADDRESS_BOOK', 'zen_address_book');
define('TABLE_CUSTOMERS_INFO', 'zen_customers_info');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');
define('STORE_NAME', 'Test Store');
define('EMAIL_FROM', 'store@example.com');
define('ENTRY_PASSWORD_MIN_LENGTH', 8);
define('ENABLE_SSL_CATALOG', 'true');
define('HTTPS_CATALOG_SERVER', 'https://shop.example.com');
define('DIR_WS_HTTPS_CATALOG', '/');
define('HTTP_CATALOG_SERVER', 'https://shop.example.com');
define('HTTP_SERVER', 'https://shop.example.com');
define('DIR_WS_CATALOG', '/');
define('FILENAME_DEFAULT', 'index');
define('FILENAME_LOGIN', 'login');
define('DIR_FS_CATALOG', __DIR__ . '/fakestore/');
define('DIR_WS_IMAGES', 'images/');

$PLUGIN = scf_plugin_dir();
foreach (require $PLUGIN . '/admin/includes/languages/english/lang.social_contact_footer_subscribers.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}
foreach (require $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}
foreach ([
    'SCF_STATUS' => 'true', 'SCF_DISABLE_ON_PAGES' => '', 'SCF_SUBSCRIBE_STATUS' => 'true',
    'SCF_SUBSCRIBE_ASK_FORMAT' => 'true', 'SCF_SUBSCRIBE_HONEYPOT' => 'true',
    'SCF_SUBSCRIBE_PRIVACY_LINK' => 'true', 'SCF_SUBSCRIBE_PRIVACY_URL' => '',
    'SCF_BLOG_URL' => '', 'SCF_ICONS_HEADING' => '', 'SCF_ICON_SIZE_DESKTOP' => '32',
    'SCF_ICON_SIZE_MOBILE' => '24', 'SCF_ICON_STYLE' => 'Monochrome',
    'SCF_ICON_MONO_COLOR' => '#444444', 'SCF_ICON_SHAPE' => 'Circle', 'SCF_ICON_ALIGN' => 'Left',
    'SCF_ICON_SOURCE' => 'Built-in SVG', 'SCF_ICON_IMAGE_DIR' => 'social_contact_footer/',
    'SCF_ICON_ORDER' => '', 'SCF_LINK_TARGET' => '_self',
] as $k => $v) { define($k, $v); }

class InviteDb extends ScfDb
{
    public $subscriber = null;
    public $customerId = 0;
    public $hasActivationColumn = false;

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));

        if (stripos($sql, 'SHOW COLUMNS') !== false) {
            $wanted = (stripos($sql, 'activation_required') !== false
                || stripos($sql, 'welcome_email_sent') !== false);
            return new ScfResult(($wanted && $this->hasActivationColumn) ? [['Field' => 'x']] : []);
        }
        if (stripos($sql, 'SELECT customers_id FROM zen_customers') !== false) {
            return new ScfResult($this->customerId ? [['customers_id' => $this->customerId]] : []);
        }
        if (stripos($sql, 'FROM zen_social_contact_footer_subscribers') !== false) {
            return new ScfResult($this->subscriber ? [$this->subscriber] : []);
        }
        return new ScfResult([]);
    }
}

$db = new InviteDb();

require $PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_invite.php';
require $PLUGIN . '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php';

function subscriber(array $over = [])
{
    return array_merge([
        'subscriber_id' => 7,
        'subscriber_email' => 'ada@example.com',
        'subscriber_name' => 'Ada Lovelace',
        'email_format' => 'HTML',
        'status' => 1,
        'confirm_token' => str_repeat('c', 40),
        'customers_id' => 0,
        'invite_token' => null,
        'invite_sent' => null,
        'invite_accepted' => null,
    ], $over);
}
function reset_all()
{
    global $db;
    $db->reset();
    $db->subscriber = null;
    $db->customerId = 0;
    $db->nextInsertId = 0;
    $_GET = ['main_page' => 'index'];
    $_POST = [];
    $_SESSION = ['securityToken' => 'TOK', 'languages_id' => 1];
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
}
function performed($table) { return $GLOBALS['SCF_PERFORMED'][$table] ?? []; }
function sent() { return $GLOBALS['SCF_SENT_MAIL']; }

/* The column lookup memoises for the life of the request -- correct in
 * production, where the schema cannot change under a page load, but it means a
 * store WITH the v2.2.0 columns needs its own process, before anything else has
 * populated that cache. */
if (($argv[1] ?? '') === 'activation-column') {
    section('v2.2.0 columns present');
    reset_all();
    $db->hasActivationColumn = true;
    $db->subscriber = subscriber();
    scf_admin_send_invite(7);
    $created = performed(TABLE_CUSTOMERS)[0];
    check('activation_required IS written when the column exists',
        isset($created['activation_required']) && (int)$created['activation_required'] === 1);
    check('welcome_email_sent is written alongside it',
        array_key_exists('welcome_email_sent', $created));
    check('and is flipped to 1 after the invitation goes out',
        strpos($db->matching('welcome_email_sent = 1'), 'customers_id = 4242') !== false);
    scf_done('v2.2.0 schema handled');
}

section('eligibility');
reset_all();
check('a confirmed subscriber with no account is eligible',
    scf_admin_invite_eligible(subscriber()) === true);
reset_all();
check('someone awaiting confirmation is NOT eligible -- they never verified the address',
    scf_admin_invite_eligible(subscriber(['status' => 0])) === false);
reset_all();
check('an unsubscribed person is NOT eligible',
    scf_admin_invite_eligible(subscriber(['status' => 2])) === false);
reset_all();
check('someone who already accepted is NOT eligible',
    scf_admin_invite_eligible(subscriber(['invite_accepted' => '2026-01-01 00:00:00'])) === false);
reset_all();
$db->customerId = 55;
check('an address that already has a customer account is NOT eligible',
    scf_admin_invite_eligible(subscriber()) === false);

section('sending an invitation');
reset_all();
$db->subscriber = subscriber();
$res = scf_admin_send_invite(7);
check('reports success', $res['ok'] === true);
$cust = performed(TABLE_CUSTOMERS)[0] ?? [];
check('a customer row was created', !empty($cust));
check('the account is PENDING, not approved', (int)$cust['customers_authorization'] === 3);
check("SCF_AUTH_PENDING mirrors core's AUTH_NO_PURCHASE",
    SCF_AUTH_PENDING === 3 && SCF_AUTH_OK === 0);
check('the password is stored hashed, never in clear',
    strpos($cust['customers_password'], 'hashed:') === 0);
check('the chosen format carries over', $cust['customers_email_format'] === 'HTML');
check('the newsletter flag is set', $cust['customers_newsletter'] === '1');
check('"Ada Lovelace" split into first and last name',
    $cust['customers_firstname'] === 'Ada' && $cust['customers_lastname'] === 'Lovelace');
$addr = performed(TABLE_ADDRESS_BOOK)[0] ?? [];
check('a default address row was created', !empty($addr));
/* Left to the column defaults these can be NULL, and core's Customer::login()
 * then throws looking up country and zone for the address. */
check('country and zone are written, not left NULL',
    array_key_exists('entry_country_id', $addr) && array_key_exists('entry_zone_id', $addr));
check('customers_default_address_id points at that row',
    strpos($db->matching('customers_default_address_id'), '= 99') !== false);
check('a customers_info row was created', $db->matching('zen_customers_info') !== '');
check('the subscriber row records the invitation',
    strpos($db->matching('invite_token ='), 'invite_sent = now()') !== false);
check('the admin activity log records it', count($GLOBALS['SCF_ADMIN_LOG']) === 1);

section('the invitation email');
check('exactly one message, to the subscriber',
    count(sent()) === 1 && sent()[0]['toAddr'] === 'ada@example.com');
$mail = sent()[0];
check('it carries the activation link', strpos($mail['text'], 'scf_activate=') !== false);
check('it carries an unsubscribe link', strpos($mail['text'], 'scf_unsubscribe=') !== false);
check('it asks the reader to whitelist the sending address',
    strpos($mail['text'], 'store@example.com') !== false);
check('the emailed password is the one hashed onto the account',
    preg_match('~\n\s+([A-Za-z0-9]{8,})\n~', $mail['text'], $m)
    && $cust['customers_password'] === 'hashed:' . sha1($m[1]));
check('an HTML part is supplied for an HTML subscriber',
    isset($mail['block']['EMAIL_MESSAGE_HTML']));
check('no unexpanded sprintf placeholder survives',
    strpos($mail['text'], '%') === false
    && strpos($mail['block']['EMAIL_MESSAGE_HTML'], '%') === false);
/* Clicking unsubscribe must never activate an account, and vice versa. */
preg_match('~scf_activate=([a-f0-9]+)~', $mail['text'], $act);
preg_match('~scf_unsubscribe=([a-f0-9]+)~', $mail['text'], $uns);
check('the activation token is distinct from the unsubscribe token',
    !empty($act[1]) && !empty($uns[1]) && $act[1] !== $uns[1]);
check('the activation token resists guessing', strlen($act[1]) >= 32);

reset_all();
$db->subscriber = subscriber(['email_format' => 'TEXT', 'subscriber_name' => '']);
scf_admin_send_invite(7);
check('a TEXT-Only subscriber gets no HTML part',
    !isset(sent()[0]['block']['EMAIL_MESSAGE_HTML']));
check('a nameless subscriber is addressed by their address',
    sent()[0]['toName'] === 'ada@example.com');
check('their account keeps TEXT, the value the varchar(4) column can hold',
    performed(TABLE_CUSTOMERS)[0]['customers_email_format'] === 'TEXT');

section('v2.2.0 columns are optional: absent');
reset_all();
$db->subscriber = subscriber();
scf_admin_send_invite(7);
$created = performed(TABLE_CUSTOMERS)[0];
check('activation_required is not written when the column is absent (v1.5.8-v2.1)',
    !array_key_exists('activation_required', $created));
check('nor is welcome_email_sent', !array_key_exists('welcome_email_sent', $created));

section('re-sending an invitation');
/* An invitation creates the account immediately, so the subscriber stops being
 * eligible the moment one goes out. Without a re-send there would be no button
 * at all, and an invitation that never arrived would be unfixable. */
reset_all();
$db->customerId = 4242;
$invited = subscriber(['invite_sent' => '2026-07-01 10:00:00', 'customers_id' => 4242]);
check('an invited subscriber is no longer eligible for a FIRST invitation',
    scf_admin_invite_eligible($invited) === false);
check('but the invitation can be sent again', scf_admin_invite_resendable($invited) === true);
reset_all();
$db->customerId = 4242;
check('somebody never invited is not resendable -- the ordinary path applies',
    scf_admin_invite_resendable(subscriber()) === false);
reset_all();
$db->customerId = 4242;
check('somebody who already accepted cannot be re-sent',
    scf_admin_invite_resendable(subscriber([
        'invite_sent' => '2026-07-01 10:00:00', 'invite_accepted' => '2026-07-02 09:00:00',
    ])) === false);
reset_all();
$db->customerId = 0;
check('with the account gone there is nothing to re-send to',
    scf_admin_invite_resendable(subscriber(['invite_sent' => '2026-07-01 10:00:00'])) === false);

reset_all();
$db->customerId = 4242;
$db->subscriber = subscriber(['invite_sent' => '2026-07-01 10:00:00', 'customers_id' => 4242]);
$res = scf_admin_resend_invite(7);
check('re-send reports success', $res['ok'] === true);
check('no second customer account is created', empty($GLOBALS['SCF_PERFORMED']));
check('a new password is written to the existing account',
    strpos($db->matching('customers_password'), 'customers_id = 4242') !== false);
check('the account is left pending -- only the link may authorise it',
    strpos($db->matching('customers_password'), 'customers_authorization') === false);
check('a fresh activation token replaces the old one',
    strpos($db->matching('invite_token ='), 'invite_sent = now()') !== false);
check('invite_accepted is neither set nor cleared by a re-send',
    strpos($db->matching('invite_token ='), 'invite_accepted') === false);
$mail = sent()[0];
check('exactly one message goes out', count(sent()) === 1);
check('it carries the new password, matching the new hash',
    preg_match('~\n\s+([A-Za-z0-9]{8,})\n~', $mail['text'], $m)
    && strpos($db->matching('customers_password'), sha1($m[1])) !== false);
check('it still carries an unsubscribe link', strpos($mail['text'], 'scf_unsubscribe=') !== false);
/* If the first message was merely slow, the subscriber holds both -- so the
 * second has to say which credentials are live. */
check('the re-send is a different message from the first invitation',
    $mail['subject'] !== sprintf(SCF_EMAIL_INVITE_SUBJECT, STORE_NAME));
check('it says the old password no longer works',
    stripos($mail['text'], 'no longer works') !== false);
check('the HTML part says so too',
    stripos($mail['block']['EMAIL_MESSAGE_HTML'], 'no longer works') !== false);

reset_all();
$db->customerId = 4242;
$db->subscriber = subscriber(['invite_sent' => '2026-07-01 10:00:00', 'invite_accepted' => '2026-07-02 09:00:00']);
$res = scf_admin_resend_invite(7);
check('re-sending to somebody who accepted is refused', $res['ok'] === false);
check('and nothing is written or sent',
    $db->countMatching('customers_password') === 0 && count(sent()) === 0);

section('how long ago it went out');
/* The decision to re-send hinges on this: it invalidates a password that may
 * still be in flight. */
check('nothing to say when no invitation was sent', scf_admin_time_ago('') === '');
check('an unparseable value says nothing rather than guessing', scf_admin_time_ago('not a date') === '');
check('a zero date says nothing', scf_admin_time_ago('0000-00-00 00:00:00') === '');
check('seconds ago reads as "moments ago"',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 20)) === SCF_ADMIN_TIME_JUST_NOW);
check('one minute is singular',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 90)) === SCF_ADMIN_TIME_MINUTE);
check('minutes are counted',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 25 * 60)) === sprintf(SCF_ADMIN_TIME_MINUTES, 25));
check('one hour is singular',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 90 * 60)) === SCF_ADMIN_TIME_HOUR);
check('hours are counted',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 5 * 3600)) === sprintf(SCF_ADMIN_TIME_HOURS, 5));
check('a day reads as "yesterday"',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 30 * 3600)) === SCF_ADMIN_TIME_DAY);
check('days are counted',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() - 9 * 86400)) === sprintf(SCF_ADMIN_TIME_DAYS, 9));
/* A server whose database and PHP disagree on the clock would otherwise produce
 * "in four hours", which reads as a bug. */
check('a future timestamp says "moments ago", never "in 4 hours"',
    scf_admin_time_ago(date('Y-m-d H:i:s', time() + 4 * 3600)) === SCF_ADMIN_TIME_JUST_NOW);

section('the impatient second click');
check('an invitation sent two minutes ago counts as hasty',
    scf_admin_invite_sent_recently(subscriber(['invite_sent' => date('Y-m-d H:i:s', time() - 120)])) === true);
check('one sent an hour ago does not',
    scf_admin_invite_sent_recently(subscriber(['invite_sent' => date('Y-m-d H:i:s', time() - 3600)])) === false);
check('a row never invited is not hasty', scf_admin_invite_sent_recently(subscriber()) === false);
check('a future timestamp is not treated as hasty either',
    scf_admin_invite_sent_recently(subscriber(['invite_sent' => date('Y-m-d H:i:s', time() + 600)])) === false);
check('the hasty warning names the delivery delay as the likely explanation',
    stripos(SCF_ADMIN_CONFIRM_REINVITE_HASTY, 'few minutes to arrive') !== false);
check('both confirmations warn that the sent password stops working',
    stripos(SCF_ADMIN_CONFIRM_REINVITE, 'stop working') !== false
    && stripos(SCF_ADMIN_CONFIRM_REINVITE_HASTY, 'invalidate') !== false);
/* These are interpolated into an inline onsubmit="confirm('...')", so a newline
 * would break the button. */
foreach (['SCF_ADMIN_CONFIRM_REINVITE', 'SCF_ADMIN_CONFIRM_REINVITE_HASTY',
          'SCF_ADMIN_CONFIRM_INVITE', 'SCF_ADMIN_CONFIRM_INVITE_ALL',
          'SCF_ADMIN_CONFIRM_DELETE'] as $key) {
    check("$key is a single line, safe inside an inline confirm()",
        strpos(constant($key), "\n") === false);
}

section('refusals');
reset_all();
$res = scf_admin_send_invite(7);
check('an unknown subscriber is refused', $res['ok'] === false);
check('nothing was created', empty($GLOBALS['SCF_PERFORMED']) && count(sent()) === 0);
reset_all();
$db->subscriber = subscriber(['status' => 0]);
$res = scf_admin_send_invite(7);
check('an unconfirmed subscriber is refused', $res['ok'] === false);
check('no account is created and no mail is sent',
    empty($GLOBALS['SCF_PERFORMED']) && count(sent()) === 0);

section('accepting the invitation (storefront)');
reset_all();
$token = str_repeat('a', 40);
$db->subscriber = ['subscriber_id' => 7, 'customers_id' => 4242, 'invite_accepted' => null];
$_GET = ['main_page' => 'index', 'scf_activate' => $token];
$redirect = null;
try { scf_handle_request(); } catch (ScfRedirect $e) { $redirect = $e->getMessage(); }
check('the account is approved (0)',
    strpos($db->matching('customers_authorization = 0'), 'customers_id = 4242') !== false);
check('the subscriber row is marked accepted',
    strpos($db->matching('invite_accepted = now()'), 'subscriber_id = 7') !== false);
check('the visitor lands on the login page, where the emailed password is used',
    strpos((string)$redirect, 'main_page=login') !== false);
check('the token is not left in the address bar', strpos((string)$redirect, $token) === false);
check('a success message is queued',
    ($_SESSION['scf_message']['text'] ?? '') === SCF_INVITE_ACCEPTED);

reset_all();
$db->subscriber = ['subscriber_id' => 7, 'customers_id' => 4242, 'invite_accepted' => '2026-01-01 00:00:00'];
$_GET = ['main_page' => 'index', 'scf_activate' => str_repeat('a', 40)];
try { scf_handle_request(); } catch (ScfRedirect $e) {}
check('a second visit does not re-approve anything',
    $db->countMatching('customers_authorization = 0') === 0);
check('and says so rather than failing silently',
    ($_SESSION['scf_message']['text'] ?? '') === SCF_INVITE_ALREADY_ACCEPTED);

reset_all();
$_GET = ['main_page' => 'index', 'scf_activate' => 'short'];
try { scf_handle_request(); } catch (ScfRedirect $e) {}
check('a too-short token is rejected without touching the database',
    $db->countMatching('UPDATE') === 0
    && ($_SESSION['scf_message']['text'] ?? '') === SCF_ERROR_BAD_TOKEN);

reset_all();
$_GET = ['main_page' => 'index', 'scf_activate' => str_repeat('b', 40)];
try { scf_handle_request(); } catch (ScfRedirect $e) {}
check('an unknown token approves nothing',
    $db->countMatching('customers_authorization = 0') === 0
    && ($_SESSION['scf_message']['text'] ?? '') === SCF_ERROR_BAD_TOKEN);

section('escaping into the admin message stack');
/* Every Zen Cart admin page echoes $messageStack->output() as raw HTML, so a
 * stored address placed in a message has to be escaped first. This was a real
 * defect, found by the pre-release security scan. */
$inviteSrc = file_get_contents($PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_invite.php');
check('the invitation success message escapes the address',
    preg_match('~SCF_ADMIN_INVITE_SUCCESS,\s*zen_output_string_protected~', $inviteSrc) === 1);
check('the re-send success message escapes it too',
    preg_match('~SCF_ADMIN_REINVITE_SUCCESS,\s*zen_output_string_protected~', $inviteSrc) === 1);

/* The v2.2.0 schema needs its own process. */
$out = [];
$status = 1;
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' activation-column 2>&1', $out, $status);
echo "\n" . implode("\n", $out) . "\n";
if ($status !== 0) { $GLOBALS['scf_failures']++; }

scf_done('invite registration verified');
