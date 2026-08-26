<?php
/**
 * CSV import of newsletter subscribers.
 *
 * The load-bearing assertion is that an import cannot create a *subscribed*
 * person. Everything else is about not trusting a file somebody typed up after
 * an event.
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

$PLUGIN = scf_plugin_dir();
foreach (require $PLUGIN . '/admin/includes/languages/english/lang.social_contact_footer_subscribers.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}
foreach (require $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php' as $k => $v) {
    if (!defined($k)) { define($k, $v); }
}

class ImportDb extends ScfDb
{
    /** lowercase email => status, for addresses already on the list */
    public $known = [];
    public $customerId = 0;
    public $failInsert = false;
    private $nextId = 1;

    public function Insert_ID() { return $this->failInsert ? 0 : $this->nextId++; }

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));
        if (stripos($sql, 'SELECT subscriber_id, status FROM') !== false) {
            if (preg_match("~subscriber_email = '([^']*)'~", $sql, $m)) {
                $key = strtolower($m[1]);
                if (isset($this->known[$key])) {
                    return new ScfResult([['subscriber_id' => 9, 'status' => $this->known[$key]]]);
                }
            }
            return new ScfResult([]);
        }
        if (stripos($sql, 'SELECT customers_id FROM') !== false) {
            return new ScfResult($this->customerId ? [['customers_id' => $this->customerId]] : []);
        }
        return new ScfResult([]);
    }

    public function inserts()
    {
        return array_values(array_filter($this->log, static function ($s) {
            return stripos($s, 'INSERT INTO zen_social_contact_footer_subscribers') !== false;
        }));
    }
}

$db = new ImportDb();
require $PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_invite.php';
require $PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_import.php';

$tmpFiles = [];
function csvFile($content)
{
    global $tmpFiles;
    $path = tempnam(sys_get_temp_dir(), 'scfcsv');
    file_put_contents($path, $content);
    $tmpFiles[] = $path;
    return ['name' => 'list.csv', 'tmp_name' => $path, 'size' => strlen($content), 'error' => UPLOAD_ERR_OK];
}
function reset_all()
{
    global $db;
    $db->reset();
    $db->known = [];
    $db->customerId = 0;
    $db->failInsert = false;
}
/* is_uploaded_file() is false for anything this harness writes, so the entry
 * point cannot be driven end to end from CLI. The reading rules are exercised
 * directly; the upload guards are asserted separately, and are NOT loosened to
 * make testing easier. */
function import($content)
{
    reset_all();
    $f = csvFile($content);
    return scf_admin_import_rows($f['tmp_name']);
}
function sent() { return $GLOBALS['SCF_SENT_MAIL']; }

section('the upload guard');
$src = file_get_contents($PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_import.php');
check('is_uploaded_file() gates the read, so a crafted POST cannot name a server file',
    strpos($src, 'is_uploaded_file(') !== false);
check('the guard runs before the file is opened',
    strpos($src, 'is_uploaded_file(') < strpos($src, 'fopen('));
reset_all();
$res = scf_admin_import_csv(csvFile("email\na@example.com\n"));
check('a file that did not arrive as an upload is refused', $res['ok'] === false);
check('and nothing is written or sent', count($db->inserts()) === 0 && count(sent()) === 0);

section('a straightforward file');
$res = import("email,email_format\nada@example.com,HTML\ngrace@example.com,TEXT\n");
check('both rows imported', $res['added'] === 2);
check('nothing skipped', $res['skipped'] === 0);
$ins = $db->inserts();
/* The rule this whole feature turns on. */
check('every row is written as PENDING, never subscribed',
    count($ins) === 2 && strpos($ins[0], 'status = 0') !== false && strpos($ins[1], 'status = 0') !== false);
check('no row is written with status 1',
    count(array_filter($ins, static function ($s) { return strpos($s, 'status = 1') !== false; })) === 0);
check('date_confirmed is never set by an import',
    count(array_filter($ins, static function ($s) { return stripos($s, 'date_confirmed') !== false; })) === 0);
check('each gets a confirmation request', count(sent()) === 2);
check('the confirmation carries a confirm link', strpos(sent()[0]['text'], 'scf_confirm=') !== false);
check('and an unsubscribe link, like every other message',
    strpos(sent()[0]['text'], 'scf_unsubscribe=') !== false);
check('no unexpanded placeholder survives', strpos(sent()[0]['text'], '%') === false);
check('the chosen format is stored',
    strpos($ins[0], "email_format = 'HTML'") !== false && strpos($ins[1], "email_format = 'TEXT'") !== false);
check('an HTML part goes only to the one who asked for HTML',
    isset(sent()[0]['block']['EMAIL_MESSAGE_HTML']) && !isset(sent()[1]['block']['EMAIL_MESSAGE_HTML']));
check('each row gets its own token',
    preg_match("~confirm_token = '([a-f0-9]+)'~", $ins[0], $t1)
    && preg_match("~confirm_token = '([a-f0-9]+)'~", $ins[1], $t2) && $t1[1] !== $t2[1]);
check('the admin activity log records the import', count($GLOBALS['SCF_ADMIN_LOG']) === 1);

section('headings');
check('column order does not matter', import("email_format,email\nHTML,ada@example.com\n")['added'] === 1);
$res = import("Email,Email Format\nada@example.com,html\n");
check('heading case does not matter', $res['added'] === 1);
check('a lower-case format value still lands as HTML',
    strpos($db->inserts()[0], "email_format = 'HTML'") !== false);
$res = import("E-Mail Address,E-Mail Preference\nada@example.com,TEXT-Only\n");
check('the spellings printed on the sign-up sheet are accepted', $res['added'] === 1);
check('"TEXT-Only" is stored as TEXT -- the column is varchar(4)',
    strpos($db->inserts()[0], "email_format = 'TEXT'") !== false);
check('a UTF-8 BOM does not make the first heading unmatchable (Excel writes one)',
    import("\xEF\xBB\xBFemail\nada@example.com\n")['added'] === 1);
check('blank lines before the headings are skipped',
    import("\n\nemail\nada@example.com\n")['added'] === 1);
$res = import("name,telephone\nAda,555\n");
check('a file with no email column imports nothing', $res['added'] === 0);
check('and says why', strpos($res['message'], 'email') !== false);
$res = import('');
check('an empty file is reported, not silently accepted', $res['added'] === 0 && $res['ok'] === false);

section('rows that should not be taken');
$res = import("email\nnot-an-address\nada@example.com\n");
check('an invalid address is skipped, the good row still imported',
    $res['added'] === 1 && $res['skipped'] === 1);
check('the skipped row is reported with its line number',
    !empty($res['problems']) && strpos($res['problems'][0], 'Row 2') === 0);
$res = import("email\n\nada@example.com\n");
check('a blank line mid-file is not counted as a bad row',
    $res['added'] === 1 && $res['skipped'] === 0);
$res = import("email\nada@example.com\nADA@example.com\n");
check('the same address twice in one file is taken once',
    $res['added'] === 1 && $res['skipped'] === 1);

reset_all();
$db->known = ['ada@example.com' => 1];
$f = csvFile("email\nada@example.com\n");
$res = scf_admin_import_rows($f['tmp_name']);
check('somebody already subscribed is not re-imported or re-mailed',
    $res['added'] === 0 && count(sent()) === 0);

reset_all();
$db->known = ['ada@example.com' => 2];
$f = csvFile("email\nada@example.com\n");
$res = scf_admin_import_rows($f['tmp_name']);
check('an import can never resurrect somebody who unsubscribed',
    $res['added'] === 0 && count(sent()) === 0);
check('and no INSERT is attempted for them', count($db->inserts()) === 0);

section('the sample file offered for download');
/* The sample exists to be copied. If it stopped being a file this importer
 * accepts, an owner would follow it exactly and still be rejected. */
$sample = scf_admin_import_sample_rows();
check('the sample has a heading row and some examples', count($sample) >= 3);
$csv = '';
foreach ($sample as $row) { $csv .= implode(',', $row) . "\n"; }
$res = import($csv);
check('every example row imports cleanly',
    $res['added'] === count($sample) - 1 && $res['skipped'] === 0);
check('no row of the sample is reported as a problem', empty($res['problems']));
check('it heads its address column with the name the reader matches on',
    in_array(SCF_IMPORT_HEADING_EMAIL, $sample[0], true));
check('and its format column likewise',
    in_array(SCF_IMPORT_HEADING_FORMAT, $sample[0], true));
$ins = $db->inserts();
check('the HTML example is stored as HTML', strpos($ins[0], "email_format = 'HTML'") !== false);
check('the TEXT example is stored as TEXT', strpos($ins[1], "email_format = 'TEXT'") !== false);
check('the example with no format falls back to TEXT, as documented',
    strpos($ins[2], "email_format = 'TEXT'") !== false);
check('the sample creates pending subscribers like any other import',
    strpos($ins[0], 'status = 0') !== false);
/* RFC 2606 reserves example.com so documentation cannot name a real mailbox. */
foreach (array_slice($sample, 1) as $row) {
    check("the sample address {$row[0]} is a reserved example domain",
        substr($row[0], -12) === '@example.com');
}

section('limits');
$many = "email\n";
for ($i = 0; $i < SCF_IMPORT_MAX_ROWS + 25; $i++) { $many .= "person$i@example.com\n"; }
$res = import($many);
check('no more than the row cap is imported from one file', $res['added'] === SCF_IMPORT_MAX_ROWS);
check('the remainder is reported as skipped, not silently dropped', $res['skipped'] === 25);
check('mail volume is bounded by the same cap', count(sent()) === SCF_IMPORT_MAX_ROWS);

reset_all();
$res = scf_admin_import_csv([
    'name' => 'big.csv', 'tmp_name' => __FILE__,
    'size' => SCF_IMPORT_MAX_BYTES + 1, 'error' => UPLOAD_ERR_OK,
]);
check('an oversized file is refused before it is read', $res['ok'] === false);
/* Size is checked before the upload guard so the owner gets the message that
 * tells them what to do, not one about the file arriving oddly. */
check('and refused for its size, not for arriving oddly',
    strpos($res['message'], 'KB') !== false || stripos($res['message'], 'larger') !== false);

section('upload failures are explained, not swallowed');
foreach ([
    UPLOAD_ERR_NO_FILE => 'no file chosen',
    UPLOAD_ERR_INI_SIZE => 'server size limit',
    UPLOAD_ERR_PARTIAL => 'incomplete upload',
    UPLOAD_ERR_CANT_WRITE => 'server-side failure',
] as $code => $label) {
    $res = scf_admin_import_csv(['error' => $code, 'tmp_name' => '', 'size' => 0, 'name' => '']);
    check("$label produces a message the owner can act on",
        $res['ok'] === false && trim($res['message']) !== '');
}

section('CSV parsing is pinned across versions');
/* PHP 8.4 deprecates leaving fgetcsv's $escape implicit and PHP 9 changes its
 * default; stating it keeps parsing identical on 7.4 through 8.5. */
check('fgetcsv states every argument including $escape',
    preg_match("~fgetcsv\(\\\$handle, 0, ',', '\"', ''\)~", $src) === 1);

foreach ($tmpFiles as $f) { @unlink($f); }

scf_done('an import creates pending subscribers only');
