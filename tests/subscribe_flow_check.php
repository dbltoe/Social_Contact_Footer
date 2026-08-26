<?php
/**
 * Subscribe, confirm, unsubscribe, and accepting a registration invitation.
 *
 * The whole double opt-in promise lives in these four handlers, so this runs
 * them for real against a recording database rather than reading the source.
 * Every handler ends in a redirect, which the stubbed zen_redirect() throws --
 * scf_post() catches it and hands back the destination.
 */

require __DIR__ . '/_bootstrap.php';

/* The child re-runs everything above the case it exists to reach; that is the
 * only way past the static cache, but it does not need saying twice. */
$SCF_CHILD = ($argv[1] ?? '') === 'no-activation-column';
scf_quiet($SCF_CHILD);

define('IS_ADMIN_FLAG', false);
define('DB_PREFIX', 'zen_');
define('CHARSET', 'utf-8');
define('DIR_WS_CATALOG', '/shop/');
define('DIR_WS_IMAGES', 'images/');
define('DIR_FS_CATALOG', dirname(scf_plugin_dir(), 3) . '/');
define('DIR_WS_TEMPLATE', '/shop/includes/templates/responsive_classic/');
define('HTTP_SERVER', 'https://shop.example.com');
define('HTTPS_SERVER', 'https://shop.example.com');
define('STORE_NAME', 'Acme Widgets');
define('STORE_OWNER', 'Acme Widgets');
define('STORE_OWNER_EMAIL_ADDRESS', 'owner@shop.example.com');
define('EMAIL_FROM', 'sales@shop.example.com');
define('FILENAME_DEFAULT', 'index');
define('FILENAME_LOGIN', 'login');
define('FILENAME_PRIVACY', 'privacy');
define('FILENAME_CONTACT_US', 'contact_us');
define('TABLE_CUSTOMERS', 'zen_customers');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');

$PLUGIN = scf_plugin_dir();
foreach (require $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}
foreach ([
    'SCF_STATUS' => 'true',
    'SCF_SUBSCRIBE_STATUS' => 'true',
    'SCF_SUBSCRIBE_ASK_FORMAT' => 'true',
    'SCF_SUBSCRIBE_HONEYPOT' => 'true',
    'SCF_SUBSCRIBE_PRIVACY_LINK' => 'true',
    'SCF_ICON_ALIGN' => 'Center',
    'SCF_CONTACT_LINK' => 'None',
] as $k => $v) { define($k, $v); }

require $PLUGIN . '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php';

$TOKEN = str_repeat('a1b2', 10);   // 40 hex characters, the shape scf_make_token() produces

class FlowDb extends ScfDb
{
    /** The subscriber row scf_find_subscriber* should return, or null. */
    public $subscriber = null;
    /** customers_id for the address, 0 when there is no account. */
    public $customerId = 0;
    /** The row scf_handle_activate()'s invite lookup should return. */
    public $inviteRow = null;
    public $hasActivationColumn = true;

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));

        if (stripos($sql, 'SELECT customers_id FROM zen_customers') !== false) {
            return new ScfResult($this->customerId ? [['customers_id' => $this->customerId]] : []);
        }
        if (stripos($sql, 'SHOW COLUMNS') !== false) {
            return new ScfResult($this->hasActivationColumn ? [['Field' => 'activation_required']] : []);
        }
        if (stripos($sql, 'invite_token') !== false) {
            return new ScfResult($this->inviteRow === null ? [] : [$this->inviteRow]);
        }
        if (stripos($sql, 'SELECT * FROM zen_social_contact_footer_subscribers') !== false) {
            return new ScfResult($this->subscriber === null ? [] : [$this->subscriber]);
        }
        return new ScfResult([]);
    }
}

$db = new FlowDb();

/** A confirmed subscriber row, with whatever needs overriding. */
function row(array $over = [])
{
    global $TOKEN;
    return $over + [
        'subscriber_id' => 7,
        'subscriber_email' => 'reader@example.com',
        'subscriber_name' => '',
        'email_format' => 'HTML',
        'status' => 1,
        'confirm_token' => $TOKEN,
        'token_expires' => date('Y-m-d H:i:s', time() + 86400),
        'customers_id' => 0,
    ];
}

/**
 * Submit the storefront form and report where it sent the visitor.
 *
 * @return array ['to' => redirect target, 'message' => the queued message]
 */
function scf_post(array $post, array $session = [])
{
    global $db;
    $db->reset();
    $_POST = $post + ['scf_action' => 'subscribe', 'securityToken' => 'tok'];
    $_GET = [];
    $_SESSION = $session + [
        'securityToken' => 'tok',
        'scf_form_rendered' => time() - 30,
        'languages_id' => 1,
    ];
    $to = '';
    try {
        scf_handle_request();
    } catch (ScfRedirect $e) {
        $to = $e->getMessage();
    }
    return ['to' => $to, 'message' => scf_take_message()];
}

/** Follow a GET link (confirm / unsubscribe / activate). */
function scf_visit(array $get)
{
    global $db;
    $db->reset();
    $_POST = [];
    $_GET = $get;
    $_SESSION = ['securityToken' => 'tok'];
    $to = '';
    try {
        scf_handle_request();
    } catch (ScfRedirect $e) {
        $to = $e->getMessage();
    }
    return ['to' => $to, 'message' => scf_take_message()];
}

function mail_count() { return count($GLOBALS['SCF_SENT_MAIL']); }
function last_mail() { return end($GLOBALS['SCF_SENT_MAIL']) ?: []; }

/* ====================================================================== */

section('a good signup');
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML']);
check('a row is written', $db->countMatching('INSERT INTO zen_social_contact_footer_subscribers') === 1);
/* The address column is UNIQUE, so two near-simultaneous submissions of the
 * same address would race into a duplicate-key error without this. */
check('as an upsert, so a re-subscribe cannot collide',
    stripos($db->matching('INSERT INTO zen_social'), 'ON DUPLICATE KEY UPDATE') !== false);
check('status is 0 -- awaiting confirmation, never subscribed outright',
    preg_match('~status = 0~', $db->matching('INSERT INTO zen_social')) === 1);
check('a confirmation token is stored',
    preg_match("~confirm_token = '[a-f0-9]{40}'~", $db->matching('INSERT INTO zen_social')) === 1);
check('the token expires', stripos($db->matching('INSERT INTO zen_social'), 'token_expires') !== false);
check('exactly one email goes out', mail_count() === 1);
check('to the address that signed up', last_mail()['toAddr'] === 'reader@example.com');
check('and it carries the confirmation link',
    strpos(last_mail()['text'], 'scf_confirm=') !== false);
check('the visitor is told to check their mail',
    $r['message']['text'] === SCF_SUCCESS_PENDING);
/* Nothing is mirrored onto a customer account, and the owner is not told,
 * until the address has been proven. */
check('no customer record is touched yet',
    $db->countMatching('UPDATE zen_customers') === 0);
check('the store owner is not notified yet', mail_count() === 1);

section('the address is not written into the query as it arrived');
$db->subscriber = null;
scf_post(['scf_email' => "x'@example.com", 'scf_format' => 'TEXT']);
$sql = $db->matching('INSERT INTO zen_social');
check('a quote in the address is escaped before it reaches the SQL',
    $sql === '' || strpos($sql, "x\\'@example.com") !== false);

section('what a signup must refuse');
foreach ([
    'no address' => ['scf_email' => '', 'scf_format' => 'HTML'],
    'a malformed address' => ['scf_email' => 'not-an-address', 'scf_format' => 'HTML'],
    'an address with a newline, which would be a header injection' =>
        ['scf_email' => "a@b.com\nBcc: x@y.com", 'scf_format' => 'HTML'],
] as $label => $post) {
    $db->subscriber = null;
    $r = scf_post($post);
    check("refused: $label", $db->countMatching('INSERT INTO zen_social') === 0);
    check("  and nothing was sent for it", mail_count() === 0);
    check("  and the visitor is told why", $r['message'] !== null && $r['message']['type'] === 'error');
}

section('a format is never assumed');
/* Pre-selecting a mail format is not consent, so the form leaves both radios
 * clear -- and the server has to enforce that too, since a form control is not
 * a guarantee. */
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => '']);
check('a signup with no format chosen is refused',
    $db->countMatching('INSERT INTO zen_social') === 0);
check('and says so', $r['message']['text'] === SCF_ERROR_FORMAT);
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML-Only']);
check('an invented format is refused rather than coerced',
    $db->countMatching('INSERT INTO zen_social') === 0);

section('the security token');
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML', 'securityToken' => 'wrong']);
check('a POST with the wrong token writes nothing',
    $db->countMatching('INSERT INTO zen_social') === 0);
check('and mails nothing', mail_count() === 0);
$db->subscriber = null;
$_SESSION = [];
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML'], ['securityToken' => '']);
check('an empty session token is not treated as a match',
    $db->countMatching('INSERT INTO zen_social') === 0);

section('the spam trap');
$db->subscriber = null;
$r = scf_post(['scf_email' => 'bot@example.com', 'scf_format' => 'HTML', 'scf_website' => 'http://spam']);
check('a filled honeypot writes nothing',
    $db->countMatching('INSERT INTO zen_social') === 0);
check('and mails nothing', mail_count() === 0);
/* Told the same thing a real signup is told: a bot that learns it was caught
 * simply comes back having worked out why. */
check('but the bot is given the ordinary success message',
    $r['message']['text'] === SCF_SUCCESS_PENDING);

$db->subscriber = null;
$r = scf_post(['scf_email' => 'fast@example.com', 'scf_format' => 'HTML'],
    ['scf_form_rendered' => time()]);
check('a submission faster than a person could manage is held back',
    $db->countMatching('INSERT INTO zen_social') === 0);
/* Unlike the honeypot, this one is a heuristic -- so it says what happened and
 * lets a real visitor simply submit again. Silently swallowing a genuine
 * signup would be far worse than one extra click. */
check('and is told honestly, so they can try again',
    $r['message']['text'] === SCF_ERROR_TOO_FAST && $r['message']['type'] === 'warning');
$db->subscriber = null;
$r = scf_post(['scf_email' => 'direct@example.com', 'scf_format' => 'HTML'], ['scf_form_rendered' => 0]);
check('a POST with no render stamp at all did not come from our form',
    $db->countMatching('INSERT INTO zen_social') === 0);

section('someone who is already subscribed');
$db->subscriber = row(['email_format' => 'HTML']);
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'TEXT']);
check('no second row is created', $db->countMatching('INSERT INTO zen_social') === 0);
check('their format preference is updated',
    stripos($db->matching('UPDATE zen_social'), "email_format = 'TEXT'") !== false);
check('and they are not made to confirm again', mail_count() === 0);
check('they are told they were already on the list',
    $r['message']['text'] === SCF_NOTICE_ALREADY_SUBSCRIBED);
/* subscriber_name is absent from the update on purpose: nothing collects a
 * name any more, but a row an earlier build created may hold one, and blanking
 * it would destroy data for no reason. */
check('an existing name is not blanked',
    stripos($db->matching('UPDATE zen_social'), 'subscriber_name') === false);

section('confirming');
$db->subscriber = row(['status' => 0]);
$db->customerId = 0;
$r = scf_visit(['scf_confirm' => $TOKEN]);
check('the row is confirmed',
    preg_match('~SET status = 1~', $db->matching('UPDATE zen_social')) === 1);
check('the confirmation date is recorded',
    stripos($db->matching('UPDATE zen_social'), 'date_confirmed') !== false);
check('a welcome message follows', mail_count() >= 1);
check('the visitor is told it worked', $r['message']['text'] === SCF_SUCCESS_CONFIRMED);
/* The token is in the query string, so it would otherwise sit in the address
 * bar, in browser history and in any outbound Referer header. */
check('the visitor is redirected to a clean URL', $r['to'] !== '');
check('and the token is not in it', strpos($r['to'], $TOKEN) === false);

section('every message this plugin sends carries a way out');
$withUnsub = 0;
foreach ($GLOBALS['SCF_SENT_MAIL'] as $mail) {
    if (strpos($mail['text'], 'scf_unsubscribe=') !== false) { $withUnsub++; }
}
check('the welcome message carries the unsubscribe link', $withUnsub >= 1);
check('the unsubscribe link uses the confirm token, which is never rotated',
    strpos(last_mail()['text'], 'scf_unsubscribe=' . $TOKEN) !== false);

section('confirming, with a customer account on the same address');
$db->subscriber = row(['status' => 0, 'email_format' => 'TEXT']);
$db->customerId = 25;
$r = scf_visit(['scf_confirm' => $TOKEN]);
/* This is what makes the address show up in Zen Cart's own Newsletter
 * Manager, and it happens only now -- once the address is proven. */
check('the customer account is flagged as a newsletter subscriber',
    stripos($db->matching('UPDATE zen_customers'), "customers_newsletter = '1'") !== false);
check('and their format preference is copied across',
    stripos($db->matching('UPDATE zen_customers'), "customers_email_format = 'TEXT'") !== false);
/* customers_email_format is varchar(4): "TEXT-Only" would be truncated
 * silently on a normal server and rejected outright under STRICT mode. */
check('the stored format fits the varchar(4) column',
    preg_match("~customers_email_format = '(HTML|TEXT)'~", $db->matching('UPDATE zen_customers')) === 1);
check('the store owner is told about the new subscriber', mail_count() >= 2);

section('confirmation links that should not work');
$db->subscriber = row(['status' => 0, 'token_expires' => date('Y-m-d H:i:s', time() - 60)]);
$r = scf_visit(['scf_confirm' => $TOKEN]);
check('an expired token confirms nothing',
    $db->countMatching('UPDATE zen_social') === 0);
check('and says the link has expired', $r['message']['text'] === SCF_ERROR_TOKEN_EXPIRED);

$db->subscriber = row(['status' => 2]);
$r = scf_visit(['scf_confirm' => $TOKEN]);
check('a token belonging to an unsubscribed row does not resurrect them',
    $db->countMatching('UPDATE zen_social') === 0);

$db->subscriber = row(['status' => 1]);
$r = scf_visit(['scf_confirm' => $TOKEN]);
check('confirming twice changes nothing', $db->countMatching('UPDATE zen_social') === 0);
check('and says they are already on the list',
    $r['message']['text'] === SCF_NOTICE_ALREADY_SUBSCRIBED);

$db->subscriber = null;
foreach (['short', '', "' OR 1=1 --", str_repeat('z', 40)] as $bad) {
    $r = scf_visit(['scf_confirm' => $bad]);
    check('rejected as a token: ' . var_export($bad, true),
        $db->countMatching('UPDATE zen_social') === 0);
}
/* Anything that is not hex is stripped before the lookup, so a token can never
 * carry SQL, and a stripped-down value below 32 characters never reaches the
 * database at all. */
$db->subscriber = null;
scf_visit(['scf_confirm' => "' OR 1=1 --"]);
check('a token carrying SQL never reaches a query',
    $db->countMatching('SELECT * FROM zen_social') === 0);

section('unsubscribing');
$db->subscriber = row(['status' => 1]);
$db->customerId = 25;
$r = scf_visit(['scf_unsubscribe' => $TOKEN]);
check('the row is marked unsubscribed',
    preg_match('~SET status = 2~', $db->matching('UPDATE zen_social')) === 1);
check('the date is recorded',
    stripos($db->matching('UPDATE zen_social'), 'date_unsubscribed') !== false);
/* Not deleted: the row is the record of both the consent and its withdrawal,
 * and deleting it would let the same address be re-added by an import with
 * nothing to say they had opted out. */
check('the row is not deleted', $db->countMatching('DELETE') === 0);
check('the customer account is un-flagged too',
    stripos($db->matching('UPDATE zen_customers'), "customers_newsletter = '0'") !== false);
check('and they are told it worked', $r['message']['text'] === SCF_SUCCESS_UNSUBSCRIBED);
check('no farewell message is sent -- they asked to stop hearing from the store',
    mail_count() === 0);
$db->subscriber = null;
$r = scf_visit(['scf_unsubscribe' => $TOKEN]);
check('an unknown token changes nothing', $db->countMatching('UPDATE') === 0);

section('accepting a registration invitation');
$db->subscriber = null;
/* Zen Cart v2.2.0 added customers.activation_required; v1.5.8/v2.0/v2.1 do
 * not have it. scf_customers_column_exists() caches its answer per request, so
 * the two cases cannot share a process -- the second would read the first
 * one's cached result and pass without testing anything. */
$db->hasActivationColumn = !$SCF_CHILD;
scf_quiet(false);
if ($SCF_CHILD) { section('a release with no activation_required column'); }
$db->inviteRow = ['subscriber_id' => 7, 'customers_id' => 42, 'invite_accepted' => null];
$r = scf_visit(['scf_activate' => $TOKEN]);
/* 0 = approved, mirroring core's Customer::AUTH_OK. The account was created
 * pending, which is what makes an ignored invitation harmless. */
check('the account is approved',
    stripos($db->matching('UPDATE zen_customers'), 'customers_authorization = 0') !== false);
check('the acceptance is recorded on the subscriber row',
    stripos($db->matching('UPDATE zen_social'), 'invite_accepted = now()') !== false);
check('and they are sent to the login page, where the emailed password is used',
    strpos($r['to'], 'main_page=login') !== false);
check('the column is looked for before it is written to',
    $db->countMatching("SHOW COLUMNS FROM zen_customers LIKE 'activation_required'") === 1);
if ($db->hasActivationColumn) {
    check('activation_required is cleared where the column exists',
        $db->countMatching('SET activation_required = 0') === 1);
} else {
    check('and left alone on a release that does not have it',
        $db->countMatching('SET activation_required') === 0);
    /* The rest of the acceptance has to work either way -- the missing column
     * is not a reason to leave the account unapproved. */
    check('the account is still approved without it',
        stripos($db->matching('customers_authorization'), 'customers_authorization = 0') !== false);
    scf_done('the invitation is accepted on a release with no activation_required column');
}

$db->inviteRow = ['subscriber_id' => 7, 'customers_id' => 42, 'invite_accepted' => '2026-01-01 00:00:00'];
$r = scf_visit(['scf_activate' => $TOKEN]);
check('accepting twice changes nothing', $db->countMatching('UPDATE') === 0);
check('and says so', $r['message']['text'] === SCF_INVITE_ALREADY_ACCEPTED);

$db->inviteRow = null;
$r = scf_visit(['scf_activate' => $TOKEN]);
check('an unknown invitation token changes nothing', $db->countMatching('UPDATE') === 0);
$db->inviteRow = ['subscriber_id' => 7, 'customers_id' => 0, 'invite_accepted' => null];
$r = scf_visit(['scf_activate' => $TOKEN]);
check('an invitation with no account behind it changes nothing',
    $db->countMatching('UPDATE') === 0);

section('the return page cannot become an open redirect');
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML',
    'scf_return' => 'https://evil.example/steal']);
check('only a bare main_page token is honoured: ' . $r['to'],
    strpos($r['to'], 'evil.example') === false);
check('and the visitor stays on the store',
    strpos($r['to'], 'https://shop.example.com/') === 0);
$db->subscriber = null;
$r = scf_post(['scf_email' => 'reader@example.com', 'scf_format' => 'HTML',
    'scf_return' => 'index.php?x=1&y=2']);
check('punctuation is stripped out of the return page',
    preg_match('~main_page=[a-z0-9_]+$~', $r['to']) === 1);

section('the cases that need their own process');
/* SCF_SUBSCRIBE_STATUS is a configuration constant and scf_customers_column_exists()
 * caches per request, so neither can be flipped inside a running process. */
foreach ([
    __FILE__ => 'no-activation-column',
    __DIR__ . '/subscribe_off_check.php' => '',
] as $script => $arg) {
    $out = [];
    $status = 1;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
        . ($arg === '' ? '' : ' ' . $arg) . ' 2>&1', $out, $status);
    echo "\n" . implode("\n", $out) . "\n";
    if ($status !== 0) { $GLOBALS['scf_failures']++; }
}

scf_done('the subscribe flow holds up');
