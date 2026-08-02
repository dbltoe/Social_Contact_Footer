<?php
/**
 * Social Contact Footer -- bulk import of newsletter subscribers from CSV.
 *
 * The case this exists for: addresses collected on paper, typically at a trade
 * show or an event, using the printable sign-up sheet in this plugin's `pdf/`
 * directory. Somebody types them into a spreadsheet afterwards and loads them
 * here rather than keying them into the storefront form one at a time.
 *
 * IMPORTED SUBSCRIBERS ARE PENDING, AND ARE SENT A CONFIRMATION REQUEST
 * ---------------------------------------------------------------------
 * They are NOT marked subscribed. That is not a limitation waiting to be
 * lifted; it is the whole point:
 *
 *   - Double opt-in is not optional anywhere else in this plugin, and an import
 *     is exactly where it would be most tempting and least defensible to skip
 *     it. A row in a spreadsheet is a claim that somebody consented. The
 *     confirmation click is evidence of it.
 *   - Handwriting is misread. "rn" becomes "m", a 1 becomes a 7, and the
 *     address that gets typed in belongs to somebody who never heard of the
 *     store. Sending that person a newsletter is spam however good the
 *     intention; sending them one confirmation request is not.
 *   - It matches the store owner's own Admin Add Customer plugin, which
 *     likewise refuses to bulk-import an already-active customer.
 *
 * The signed paper form is what justifies sending the confirmation request. It
 * is not a substitute for the confirmation.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

/**
 * Rows accepted from one upload.
 *
 * Every accepted row sends a confirmation email, so an unbounded file would
 * stall the request and flood the mail queue. The remainder of a larger file is
 * reported rather than silently dropped, and the owner can split the file.
 */
define('SCF_IMPORT_MAX_ROWS', 250);

/**
 * Bytes. Comfortably more than SCF_IMPORT_MAX_ROWS of addresses, and small
 * enough that a mis-selected file is rejected before it is read.
 */
define('SCF_IMPORT_MAX_BYTES', 512000);

/**
 * Column headings understood, in any order, from the first non-blank row.
 *
 * Deliberately two. Nothing collects a name any more, and the storefront form
 * asks only these two things, so the file that feeds it asks the same.
 */
define('SCF_IMPORT_HEADING_EMAIL', 'email');
define('SCF_IMPORT_HEADING_FORMAT', 'email_format');

/**
 * The example file offered for download, as rows.
 *
 * Built here rather than shipped as a static file so it cannot drift from what
 * the importer accepts: the headings below are the same constants the reader
 * matches on, so renaming one changes both at once. A sample that quietly
 * stopped matching the parser would be worse than no sample at all -- the owner
 * would follow it exactly and still be told there is no email column.
 *
 * The addresses are example.com, which RFC 2606 reserves precisely so that
 * documentation cannot name a real person's mailbox.
 *
 * @return array
 */
function scf_admin_import_sample_rows()
{
    return [
        [SCF_IMPORT_HEADING_EMAIL, SCF_IMPORT_HEADING_FORMAT],
        ['someone@example.com', 'HTML'],
        ['another.person@example.com', 'TEXT'],
        // Left blank on purpose: the column is optional, and an empty value
        // means TEXT -- which is also Zen Cart's own default for a new account.
        ['third.person@example.com', ''],
    ];
}

/**
 * Read an uploaded CSV and create pending subscribers from it.
 *
 * @param array $file A single entry from $_FILES.
 * @return array {
 *     @type bool   $ok       False only when nothing could be read at all.
 *     @type string $message  Ready to show the store owner.
 *     @type int    $added    New pending subscribers created, and mailed.
 *     @type int    $skipped  Rows deliberately passed over.
 *     @type array  $problems Human-readable, per row, capped for display.
 * }
 */
function scf_admin_import_csv(array $file)
{
    global $db;

    $blank = ['ok' => false, 'added' => 0, 'skipped' => 0, 'problems' => []];

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return array_merge($blank, ['message' => scf_admin_import_upload_error($file)]);
    }

    // Size first, because it is the one an ordinary store owner will actually
    // hit, and it deserves the message that tells them what to do about it.
    // It touches nothing on disk -- it is a comparison against the number the
    // upload reported -- so it does not weaken the guard below.
    if ((int)$file['size'] > SCF_IMPORT_MAX_BYTES) {
        return array_merge($blank, [
            'message' => sprintf(SCF_ADMIN_IMPORT_ERROR_TOO_BIG, (int)(SCF_IMPORT_MAX_BYTES / 1024)),
        ]);
    }

    // is_uploaded_file() is the check that matters, and it still stands between
    // everything above and any attempt to open the file: it refuses any path
    // that did not arrive with this request, so a crafted POST cannot make the
    // importer read an arbitrary file on the server.
    if (!is_uploaded_file($file['tmp_name'])) {
        return array_merge($blank, ['message' => SCF_ADMIN_IMPORT_ERROR_NOT_UPLOADED]);
    }

    return scf_admin_import_rows($file['tmp_name']);
}

/**
 * Read a CSV already known to be safe to open, and create the subscribers.
 *
 * Split from the checks above so the reading rules -- which rows are taken,
 * which are passed over, what gets written -- can be exercised directly. The
 * upload guards stay in the caller: `is_uploaded_file()` is false for any file
 * not delivered by the current request, so a test could never satisfy it, and
 * loosening it to make testing easier would remove the guard that matters.
 *
 * @param string $path
 * @return array Same shape as scf_admin_import_csv().
 */
function scf_admin_import_rows($path)
{
    global $db;

    $blank = ['ok' => false, 'added' => 0, 'skipped' => 0, 'problems' => []];

    $handle = fopen($path, 'r');
    if ($handle === false) {
        return array_merge($blank, ['message' => SCF_ADMIN_IMPORT_ERROR_UNREADABLE]);
    }

    $headings = null;
    $lineNumber = 0;
    $added = 0;
    $skipped = 0;
    $problems = [];
    $seen = [];

    /* Every argument is given explicitly, including $escape.
     *
     * PHP 8.4 deprecates leaving $escape implicit because PHP 9 changes its
     * default, and an implicit call would emit a deprecation notice there and
     * quietly change behaviour later. '' is also the correct value for real
     * CSV: a backslash has no special meaning in the format, and it is not what
     * Excel writes. Stating it pins identical parsing on 7.4 through 8.5. */
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $lineNumber++;

        // fgetcsv returns [null] for a blank line.
        if ($row === [null] || $row === false) {
            continue;
        }
        $joined = trim(implode('', array_map('strval', $row)));
        if ($joined === '') {
            continue;
        }

        if ($headings === null) {
            $headings = scf_admin_import_map_headings($row);
            if (!isset($headings[SCF_IMPORT_HEADING_EMAIL])) {
                fclose($handle);
                return array_merge($blank, [
                    'message' => sprintf(SCF_ADMIN_IMPORT_ERROR_NO_EMAIL_COLUMN, SCF_IMPORT_HEADING_EMAIL),
                ]);
            }
            continue;
        }

        if ($added >= SCF_IMPORT_MAX_ROWS) {
            $skipped++;
            continue;
        }

        $email = isset($row[$headings[SCF_IMPORT_HEADING_EMAIL]])
            ? trim((string)$row[$headings[SCF_IMPORT_HEADING_EMAIL]])
            : '';

        if ($email === '') {
            $skipped++;
            $problems[] = sprintf(SCF_ADMIN_IMPORT_PROBLEM_NO_EMAIL, $lineNumber);
            continue;
        }

        if (!zen_validate_email($email)) {
            $skipped++;
            $problems[] = sprintf(SCF_ADMIN_IMPORT_PROBLEM_BAD_EMAIL, $lineNumber, $email);
            continue;
        }

        // Same address twice in one file: take it once, quietly.
        $key = strtolower($email);
        if (isset($seen[$key])) {
            $skipped++;
            continue;
        }
        $seen[$key] = true;

        $format = 'TEXT';
        if (isset($headings[SCF_IMPORT_HEADING_FORMAT], $row[$headings[SCF_IMPORT_HEADING_FORMAT]])) {
            $format = scf_admin_import_normalise_format((string)$row[$headings[SCF_IMPORT_HEADING_FORMAT]]);
        }

        $existing = $db->Execute(
            "SELECT subscriber_id, status FROM " . TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS . "
              WHERE subscriber_email = '" . zen_db_input($email) . "'
              LIMIT 1"
        );

        if (!$existing->EOF) {
            // Already known. An import must never resurrect somebody who
            // unsubscribed, and must not re-mail somebody already subscribed.
            $skipped++;
            $problems[] = sprintf(SCF_ADMIN_IMPORT_PROBLEM_KNOWN, $lineNumber, $email);
            continue;
        }

        if (scf_admin_import_add_subscriber($email, $format)) {
            $added++;
        } else {
            $skipped++;
            $problems[] = sprintf(SCF_ADMIN_IMPORT_PROBLEM_FAILED, $lineNumber, $email);
        }
    }

    fclose($handle);

    if ($headings === null) {
        return array_merge($blank, ['message' => SCF_ADMIN_IMPORT_ERROR_EMPTY]);
    }

    zen_record_admin_activity(
        'Social Contact Footer: imported ' . $added . ' pending subscriber(s) from CSV; '
        . $skipped . ' row(s) skipped.',
        'warning'
    );

    return [
        'ok' => true,
        'added' => $added,
        'skipped' => $skipped,
        'problems' => $problems,
        'message' => sprintf(SCF_ADMIN_IMPORT_SUCCESS, $added, $skipped),
    ];
}

/**
 * Create one pending subscriber and send the confirmation request.
 *
 * @return bool
 */
function scf_admin_import_add_subscriber($email, $format)
{
    global $db;

    $token = scf_admin_make_token();

    $db->Execute(
        "INSERT INTO " . TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS . "
            SET subscriber_email = '" . zen_db_input($email) . "',
                customers_id = " . (int)scf_admin_customer_id_for_email($email) . ",
                email_format = '" . zen_db_input($format) . "',
                status = 0,
                confirm_token = '" . zen_db_input($token) . "',
                token_expires = DATE_ADD(now(), INTERVAL 7 DAY),
                ip_address = '',
                language_id = 1,
                date_added = now(),
                last_modified = now()"
    );

    if ((int)$db->Insert_ID() === 0) {
        return false;
    }

    scf_admin_import_send_confirmation($email, $format, $token);

    return true;
}

/**
 * The same confirmation request the storefront form sends.
 *
 * Built here rather than called across from the catalog function library,
 * because that library is not loaded on the admin side.
 *
 * @return void
 */
function scf_admin_import_send_confirmation($email, $format, $token)
{
    $storeName = defined('STORE_NAME') ? STORE_NAME : '';
    $fromAddress = defined('EMAIL_FROM') ? EMAIL_FROM : '';
    $confirmUrl = scf_admin_catalog_base() . 'index.php?main_page=index&scf_confirm=' . $token;
    $unsubscribeUrl = scf_admin_unsubscribe_url($token);

    $block = [];
    if ($format === 'HTML') {
        $block['EMAIL_MESSAGE_HTML'] = sprintf(
            SCF_EMAIL_CONFIRM_HTML,
            zen_output_string_protected($storeName),
            zen_output_string_protected($confirmUrl),
            zen_output_string_protected($unsubscribeUrl),
            zen_output_string_protected($fromAddress)
        );
    }

    zen_mail(
        $email,
        $email,
        sprintf(SCF_EMAIL_CONFIRM_SUBJECT, $storeName),
        sprintf(SCF_EMAIL_CONFIRM_TEXT, $storeName, $confirmUrl, $unsubscribeUrl, $fromAddress),
        $storeName,
        $fromAddress,
        $block,
        'social_contact_footer'
    );
}

/**
 * Map heading name -> column index, from the file's first non-blank row.
 *
 * Case and surrounding space are ignored, and a UTF-8 BOM is stripped: Excel
 * writes one, and it would otherwise make the first heading unmatchable.
 *
 * @return array
 */
function scf_admin_import_map_headings(array $row)
{
    $map = [];
    foreach ($row as $index => $value) {
        $name = strtolower(trim((string)$value));
        $name = preg_replace('~^\xEF\xBB\xBF~', '', $name);
        // Tolerate the spellings a person would reasonably use -- including the
        // wording printed on the sign-up sheet, since somebody typing the sheet
        // up will copy its column titles.
        if (in_array($name, ['e-mail', 'email address', 'e-mail address'], true)) {
            $name = SCF_IMPORT_HEADING_EMAIL;
        }
        if (in_array($name, [
            'format', 'preference',
            'email format', 'e-mail format',
            'email preference', 'e-mail preference',
        ], true)) {
            $name = SCF_IMPORT_HEADING_FORMAT;
        }
        if ($name !== '') {
            $map[$name] = $index;
        }
    }

    return $map;
}

/**
 * Anything that is not recognisably HTML becomes TEXT.
 *
 * TEXT is Zen Cart's own default for a new account, and the column is
 * varchar(4) in every supported release, so 'TEXT-Only' would be truncated --
 * that is a display label, never a stored value.
 *
 * @return string 'HTML' or 'TEXT'
 */
function scf_admin_import_normalise_format($value)
{
    $value = strtoupper(trim($value));

    return ($value === 'HTML') ? 'HTML' : 'TEXT';
}

/**
 * Turn a PHP upload error code into something a store owner can act on.
 *
 * @return string
 */
function scf_admin_import_upload_error(array $file)
{
    $code = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;

    switch ($code) {
        case UPLOAD_ERR_NO_FILE:
            return SCF_ADMIN_IMPORT_ERROR_NO_FILE;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return sprintf(SCF_ADMIN_IMPORT_ERROR_TOO_BIG, (int)(SCF_IMPORT_MAX_BYTES / 1024));
        case UPLOAD_ERR_PARTIAL:
            return SCF_ADMIN_IMPORT_ERROR_PARTIAL;
        default:
            // NO_TMP_DIR, CANT_WRITE, EXTENSION: all server-side, and all
            // outside anything the store owner can fix from this page.
            return SCF_ADMIN_IMPORT_ERROR_SERVER;
    }
}
