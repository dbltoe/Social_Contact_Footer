<?php
/**
 * Social Contact Footer -- Invite Registration.
 *
 * Turns a confirmed footer subscriber into a pending customer account and
 * emails them a link plus a generated password. Clicking the link authorises
 * the account; from there Zen Cart's own account and address-book pages
 * collect everything else.
 *
 * WHY THIS IS SELF-CONTAINED
 * --------------------------
 * The same idea exists in the separate "Admin Add Customer" plugin, but a
 * store owner should not have to install a second plugin to use this button.
 * The account-creation sequence below therefore reimplements what that plugin
 * does, using only core Zen Cart functions present in v1.5.8 through v3.0.0.
 *
 * It deliberately does NOT reuse that plugin's activation table or catalog
 * page: this plugin already has a subscriber row and a storefront request
 * handler, so an invite needs three extra columns and one extra query
 * parameter rather than a table and a page module of its own.
 *
 * CONSENT
 * -------
 * Only confirmed subscribers can be invited -- somebody who never answered the
 * double opt-in has not verified their address and must not be given an
 * account. The account is created *pending*, so an invitation that is ignored
 * leaves a dormant account rather than a usable login, and ignoring it changes
 * nothing about their newsletter subscription.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

/**
 * Mirrors core's Customer::AUTH_OK -- fully approved.
 */
define('SCF_AUTH_OK', 0);

/**
 * Mirrors core's Customer::AUTH_NO_PURCHASE -- may browse with prices, may not
 * buy. The least restrictive pending state, chosen so an invited person can
 * look around before deciding, but cannot transact until they accept.
 */
define('SCF_AUTH_PENDING', 3);

/**
 * Does a column exist on a table? Cached per request.
 *
 * MySQL 5.7 has no `ADD COLUMN IF NOT EXISTS`, and several columns this touches
 * were added in later Zen Cart releases, so presence has to be checked.
 *
 * @param string $table
 * @param string $column
 * @return bool
 */
function scf_admin_column_exists($table, $column)
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    global $db;
    $result = $db->Execute("SHOW COLUMNS FROM " . $table . " LIKE '" . zen_db_input($column) . "'");
    $cache[$key] = ($result !== false && !$result->EOF);

    return $cache[$key];
}

/**
 * Is this subscriber a candidate for an invitation?
 *
 * @param array $row  A row from the subscribers table.
 * @return bool
 */
function scf_admin_invite_eligible(array $row)
{
    // Confirmed only. Pending and unsubscribed rows are never invited.
    if ((int)$row['status'] !== 1) {
        return false;
    }

    // Already invited and accepted -- they have an account.
    if (!empty($row['invite_accepted'])) {
        return false;
    }

    // Already a customer, whether or not through this plugin.
    return scf_admin_customer_id_for_email($row['subscriber_email']) === 0;
}

/**
 * Can this subscriber's invitation be sent again?
 *
 * An invitation creates the account on the spot, so once it has gone out the
 * subscriber stops being *eligible* -- correctly, because a second invitation
 * must not create a second account. Without this, though, an invitation that
 * never arrived would leave the store owner with no button at all and a pending
 * account nobody can reach.
 *
 * So: invited, has the account, has not accepted yet.
 *
 * @param array $row  A row from the subscribers table.
 * @return bool
 */
function scf_admin_invite_resendable(array $row)
{
    if ((int)$row['status'] !== 1) {
        return false;
    }

    // Never invited: the ordinary invite path applies, not this one.
    if (empty($row['invite_sent'])) {
        return false;
    }

    // Accepted already -- the account is live and its password is the
    // customer's own business now.
    if (!empty($row['invite_accepted'])) {
        return false;
    }

    // The account this plugin created must still be there. If the owner deleted
    // it, there is nothing to re-send *to*, and the ordinary invite path will
    // pick the subscriber up again.
    return scf_admin_customer_id_for_email($row['subscriber_email']) > 0;
}

/**
 * "3 minutes ago", for a datetime the database wrote.
 *
 * The store owner needs to know how long ago an invitation went out before
 * deciding to send it again -- a re-send invalidates the password in the
 * previous one, so doing it thirty seconds later because nothing appeared to
 * happen is exactly the mistake worth heading off.
 *
 * @param string $datetime  As stored by MySQL, or empty.
 * @return string  Empty when there is nothing to describe.
 */
function scf_admin_time_ago($datetime)
{
    $datetime = trim((string)$datetime);
    if ($datetime === '' || $datetime === '0000-00-00 00:00:00') {
        return '';
    }

    $then = strtotime($datetime);
    if ($then === false) {
        return '';
    }

    // The timestamp came from the database and this arithmetic is PHP's, so a
    // server whose two clocks or timezones disagree can produce a negative
    // interval. Say "just now" rather than "in 4 hours".
    $seconds = time() - $then;
    if ($seconds < 60) {
        return SCF_ADMIN_TIME_JUST_NOW;
    }

    $minutes = (int)floor($seconds / 60);
    if ($minutes < 60) {
        return ($minutes === 1)
            ? SCF_ADMIN_TIME_MINUTE
            : sprintf(SCF_ADMIN_TIME_MINUTES, $minutes);
    }

    $hours = (int)floor($minutes / 60);
    if ($hours < 24) {
        return ($hours === 1)
            ? SCF_ADMIN_TIME_HOUR
            : sprintf(SCF_ADMIN_TIME_HOURS, $hours);
    }

    $days = (int)floor($hours / 24);

    return ($days === 1)
        ? SCF_ADMIN_TIME_DAY
        : sprintf(SCF_ADMIN_TIME_DAYS, $days);
}

/**
 * Below this, a re-send is far more likely to be an impatient second click than
 * a considered decision, so the confirmation says so more loudly.
 */
define('SCF_REINVITE_HASTY_SECONDS', 600);

/**
 * @param array $row
 * @return bool  True when the last invitation went out only moments ago.
 */
function scf_admin_invite_sent_recently(array $row)
{
    if (empty($row['invite_sent'])) {
        return false;
    }

    $then = strtotime((string)$row['invite_sent']);
    if ($then === false) {
        return false;
    }

    $seconds = time() - $then;

    return ($seconds >= 0 && $seconds < SCF_REINVITE_HASTY_SECONDS);
}

/**
 * @param string $email
 * @return int  0 when no customer account exists for the address.
 */
function scf_admin_customer_id_for_email($email)
{
    global $db;

    $result = $db->Execute(
        "SELECT customers_id FROM " . TABLE_CUSTOMERS . "
          WHERE customers_email_address = '" . zen_db_input($email) . "'
          LIMIT 1"
    );

    return $result->EOF ? 0 : (int)$result->fields['customers_id'];
}

/**
 * Create a pending customer account for a subscriber and email the invitation.
 *
 * @param int $subscriberId
 * @return array  ['ok' => bool, 'message' => string]
 */
function scf_admin_send_invite($subscriberId)
{
    global $db;

    $subscriberId = (int)$subscriberId;
    $table = TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS;

    $result = $db->Execute("SELECT * FROM $table WHERE subscriber_id = $subscriberId LIMIT 1");
    if ($result->EOF) {
        return ['ok' => false, 'message' => SCF_ADMIN_INVITE_ERROR_NOT_FOUND];
    }
    $row = $result->fields;

    if (!scf_admin_invite_eligible($row)) {
        return ['ok' => false, 'message' => SCF_ADMIN_INVITE_ERROR_NOT_ELIGIBLE];
    }

    $email = $row['subscriber_email'];

    // The subscriber's own format choice carries over to the account, so the
    // preference they gave in the footer is the one the store then uses.
    $format = ($row['email_format'] === 'HTML') ? 'HTML' : 'TEXT';

    // subscriber_name is one optional field; customers has two. Split on the
    // last space so "Ada Lovelace" lands sensibly, and leave both blank when
    // nothing was given -- the customer completes them after logging in.
    $firstName = '';
    $lastName = '';
    $name = trim((string)$row['subscriber_name']);
    if ($name !== '') {
        $pos = strrpos($name, ' ');
        if ($pos === false) {
            $firstName = $name;
        } else {
            $firstName = substr($name, 0, $pos);
            $lastName = substr($name, $pos + 1);
        }
    }

    $minLength = (int)(defined('ENTRY_PASSWORD_MIN_LENGTH') ? ENTRY_PASSWORD_MIN_LENGTH : 0);
    $password = zen_create_PADSS_password($minLength > 0 ? $minLength : 8);

    $customer = [
        'customers_firstname' => $firstName,
        'customers_lastname' => $lastName,
        'customers_email_address' => $email,
        'customers_nick' => '',
        'customers_telephone' => '',
        'customers_fax' => '',
        'customers_newsletter' => '1',
        'customers_email_format' => $format,
        'customers_authorization' => SCF_AUTH_PENDING,
        'customers_password' => zen_encrypt_password($password),
        'registration_ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        'last_login_ip' => '',
    ];

    // Only write these when the store has the matching feature switched on;
    // otherwise the columns may not be in play at all.
    if (defined('ACCOUNT_GENDER') && ACCOUNT_GENDER === 'true') {
        $customer['customers_gender'] = '';
    }
    if (defined('ACCOUNT_DOB') && ACCOUNT_DOB === 'true') {
        $customer['customers_dob'] = '0001-01-01 00:00:00';
    }

    // Added in Zen Cart v2.2.0; absent on v1.5.8 through v2.1.
    if (scf_admin_column_exists(TABLE_CUSTOMERS, 'activation_required')) {
        $customer['activation_required'] = 1;
        $customer['welcome_email_sent'] = 0;
    }

    zen_db_perform(TABLE_CUSTOMERS, $customer);
    $customerId = (int)$db->Insert_ID();
    if ($customerId === 0) {
        return ['ok' => false, 'message' => SCF_ADMIN_INVITE_ERROR_FAILED];
    }

    // A blank default address is still required: customers_default_address_id
    // has to point at a real row.
    //
    // entry_country_id and entry_zone_id are written even though no address is
    // collected. Left to the column defaults they can end up NULL, and core's
    // own Customer::login() then throws looking up country/zone information for
    // the address.
    $address = [
        'customers_id' => $customerId,
        'entry_firstname' => $firstName,
        'entry_lastname' => $lastName,
        'entry_street_address' => '',
        'entry_postcode' => '',
        'entry_city' => '',
        'entry_country_id' => 0,
        'entry_zone_id' => '0',
        'entry_state' => '',
    ];
    if (defined('ACCOUNT_COMPANY') && ACCOUNT_COMPANY === 'true') {
        $address['entry_company'] = '';
    }
    if (defined('ACCOUNT_SUBURB') && ACCOUNT_SUBURB === 'true') {
        $address['entry_suburb'] = '';
    }

    zen_db_perform(TABLE_ADDRESS_BOOK, $address);
    $addressId = (int)$db->Insert_ID();

    $db->Execute(
        "UPDATE " . TABLE_CUSTOMERS . "
            SET customers_default_address_id = $addressId
          WHERE customers_id = $customerId
          LIMIT 1"
    );

    $db->Execute(
        "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
            (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created)
         VALUES
            ($customerId, 0, now())"
    );

    // A token distinct from confirm_token, which doubles as the permanent
    // unsubscribe link -- an unsubscribe click must never activate an account.
    $token = scf_admin_make_token();

    $db->Execute(
        "UPDATE $table
            SET customers_id = $customerId,
                invite_token = '" . zen_db_input($token) . "',
                invite_sent = now(),
                invite_accepted = NULL,
                last_modified = now()
          WHERE subscriber_id = $subscriberId
          LIMIT 1"
    );

    // confirm_token is the subscriber's permanent unsubscribe token, so the
    // invitation can offer an opt-out without minting anything new.
    scf_admin_send_invite_email($email, $firstName, $format, $password, $token, (string)$row['confirm_token']);

    if (scf_admin_column_exists(TABLE_CUSTOMERS, 'welcome_email_sent')) {
        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET welcome_email_sent = 1
              WHERE customers_id = $customerId
              LIMIT 1"
        );
    }

    zen_record_admin_activity(
        'Social Contact Footer: invited subscriber ' . $subscriberId . ' to register; created customer ' . $customerId . '.',
        'warning'
    );

    return ['ok' => true, 'message' => sprintf(SCF_ADMIN_INVITE_SUCCESS, $email)];
}

/**
 * Send an existing invitation again, with a fresh password and a fresh link.
 *
 * The original password was hashed on the way in and never kept, so there is
 * nothing to re-send -- a new one has to be generated. That is safe precisely
 * because the account is still pending: nobody has signed in with the old one,
 * and if they had, this row would carry invite_accepted and be refused above.
 *
 * The old activation token is replaced at the same time, so a link that has
 * leaked from an undelivered mailbox stops working.
 *
 * @param int $subscriberId
 * @return array  ['ok' => bool, 'message' => string]
 */
function scf_admin_resend_invite($subscriberId)
{
    global $db;

    $subscriberId = (int)$subscriberId;
    $table = TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS;

    $result = $db->Execute("SELECT * FROM $table WHERE subscriber_id = $subscriberId LIMIT 1");
    if ($result->EOF) {
        return ['ok' => false, 'message' => SCF_ADMIN_INVITE_ERROR_NOT_FOUND];
    }
    $row = $result->fields;

    if (!scf_admin_invite_resendable($row)) {
        return ['ok' => false, 'message' => SCF_ADMIN_REINVITE_ERROR_NOT_ELIGIBLE];
    }

    $email = $row['subscriber_email'];
    $customerId = scf_admin_customer_id_for_email($email);
    $format = ($row['email_format'] === 'HTML') ? 'HTML' : 'TEXT';

    $minLength = (int)(defined('ENTRY_PASSWORD_MIN_LENGTH') ? ENTRY_PASSWORD_MIN_LENGTH : 0);
    $password = zen_create_PADSS_password($minLength > 0 ? $minLength : 8);
    $token = scf_admin_make_token();

    // Left pending. Only following the link authorises the account, exactly as
    // with the first invitation.
    $db->Execute(
        "UPDATE " . TABLE_CUSTOMERS . "
            SET customers_password = '" . zen_db_input(zen_encrypt_password($password)) . "'
          WHERE customers_id = $customerId
          LIMIT 1"
    );

    $db->Execute(
        "UPDATE $table
            SET invite_token = '" . zen_db_input($token) . "',
                invite_sent = now(),
                last_modified = now()
          WHERE subscriber_id = $subscriberId
          LIMIT 1"
    );

    $firstName = '';
    $name = trim((string)($row['subscriber_name'] ?? ''));
    if ($name !== '') {
        $pos = strrpos($name, ' ');
        $firstName = ($pos === false) ? $name : substr($name, 0, $pos);
    }

    scf_admin_send_invite_email($email, $firstName, $format, $password, $token, (string)$row['confirm_token'], true);

    zen_record_admin_activity(
        'Social Contact Footer: re-sent the registration invitation for subscriber ' . $subscriberId
        . ' (customer ' . $customerId . '); a new password was issued.',
        'warning'
    );

    return ['ok' => true, 'message' => sprintf(SCF_ADMIN_REINVITE_SUCCESS, $email)];
}

/**
 * @return string  40 hex characters.
 */
function scf_admin_make_token()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(20));
    }

    return sha1(uniqid((string)mt_rand(), true));
}

/**
 * The storefront root, as the customer will see it.
 *
 * Built from the catalog's own settings rather than the admin ones, because
 * these links have to land on the storefront.
 *
 * @return string
 */
function scf_admin_catalog_base()
{
    return (defined('ENABLE_SSL_CATALOG') && ENABLE_SSL_CATALOG === 'true')
        ? HTTPS_CATALOG_SERVER . DIR_WS_HTTPS_CATALOG
        : HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
}

/**
 * The single link in the invitation.
 *
 * It both activates the account and redirects to the login page, which is why
 * the message only ever mentions one address -- the recipient is told to log in
 * "at the following link" and that is exactly where they arrive.
 *
 * @param string $token
 * @return string
 */
function scf_admin_activation_url($token)
{
    return scf_admin_catalog_base() . 'index.php?main_page=index&scf_activate=' . $token;
}

/**
 * @param string $confirmToken  The subscriber's permanent unsubscribe token.
 * @return string
 */
function scf_admin_unsubscribe_url($confirmToken)
{
    return scf_admin_catalog_base() . 'index.php?main_page=index&scf_unsubscribe=' . $confirmToken;
}

/**
 * Send the invitation.
 *
 * The HTML part is only supplied when the subscriber asked for HTML, which is
 * what makes their footer choice mean something here too.
 *
 * @return void
 */
function scf_admin_send_invite_email($email, $name, $format, $password, $token, $confirmToken, $resend = false)
{
    $storeName = defined('STORE_NAME') ? STORE_NAME : '';
    $fromAddress = defined('EMAIL_FROM') ? EMAIL_FROM : '';
    $activationUrl = scf_admin_activation_url($token);
    $unsubscribeUrl = scf_admin_unsubscribe_url($confirmToken);

    // A re-send is a different message, not the same one twice. It carries a
    // different password by necessity, so it has to say so -- otherwise a
    // recipient whose first message merely arrived late is holding two of these
    // with no way to tell which credentials still work.
    $subject = $resend ? SCF_EMAIL_REINVITE_SUBJECT : SCF_EMAIL_INVITE_SUBJECT;
    $textBody = $resend ? SCF_EMAIL_REINVITE_TEXT : SCF_EMAIL_INVITE_TEXT;
    $htmlBody = $resend ? SCF_EMAIL_REINVITE_HTML : SCF_EMAIL_INVITE_HTML;

    $block = [];
    if ($format === 'HTML') {
        $block['EMAIL_MESSAGE_HTML'] = sprintf(
            $htmlBody,
            zen_output_string_protected($storeName),
            zen_output_string_protected($activationUrl),
            zen_output_string_protected($email),
            zen_output_string_protected($password),
            zen_output_string_protected($unsubscribeUrl),
            zen_output_string_protected($fromAddress)
        );
    }

    zen_mail(
        $name !== '' ? $name : $email,
        $email,
        sprintf($subject, $storeName),
        sprintf($textBody, $storeName, $activationUrl, $email, $password, $unsubscribeUrl, $fromAddress),
        $storeName,
        $fromAddress,
        $block,
        'social_contact_footer_invite'
    );
}
