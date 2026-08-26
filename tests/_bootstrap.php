<?php
/**
 * Shared scaffolding for the Social Contact Footer harnesses.
 *
 * The previous suite duplicated these stubs in every file, which is how two
 * harnesses ended up with check() taking its arguments in opposite orders --
 * and a reversed call passes vacuously, because a non-empty label is truthy.
 * One definition here, with the order enforced rather than remembered.
 *
 * Usage:
 *   $PLUGIN = scf_plugin_dir();
 *   scf_define_constants(['admin' => true]);
 *   require __DIR__ . '/_bootstrap.php';
 */

/**
 * The plugin's version directory.
 *
 * Found relative to this file, which lives in tests/ at the repository root --
 * so the harnesses work from any checkout, on any machine, with no path to
 * configure. The version directory is renamed at every release, so its name is
 * discovered rather than hard-coded.
 */
function scf_plugin_dir()
{
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $found = [];
    foreach (glob(dirname(__DIR__) . '/zc_plugins/SocialContactFooter/v*', GLOB_ONLYDIR) as $candidate) {
        if (is_file($candidate . '/manifest.php')) {
            $found[] = str_replace('\\', '/', $candidate);
        }
    }

    if ($found === []) {
        fwrite(STDERR, "cannot locate the plugin directory below " . dirname(__DIR__) . "\n");
        exit(2);
    }

    /* More than one means an old version directory was left behind next to the
     * new one -- worth saying, because the harnesses would silently test the
     * wrong one. */
    if (count($found) > 1) {
        usort($found, static function ($a, $b) {
            return version_compare(basename($a), basename($b));
        });
        fwrite(STDERR, "  note: " . count($found) . " version directories present; using "
            . basename(end($found)) . "\n");
    }

    return $dir = end($found);
}

/** The repository root that holds the plugin directory. */
function scf_repo_root()
{
    return dirname(scf_plugin_dir(), 3);
}

$GLOBALS['scf_failures'] = 0;
$GLOBALS['scf_quiet'] = false;

/**
 * Suppress passing output.
 *
 * For a harness that re-runs itself in a child process to reach a case a
 * constant or a static cache has locked out: the child repeats every earlier
 * assertion on its way there, and printing them twice buries the one line the
 * child exists to produce. Failures are still printed, always.
 */
function scf_quiet($on = true)
{
    $GLOBALS['scf_quiet'] = (bool)$on;
}

/**
 * One assertion.
 *
 * Argument order is (label, condition) and is checked at runtime: a reversed
 * call would otherwise report "ok" forever while testing nothing.
 */
function check($label, $cond)
{
    if (!is_string($label) || is_string($cond)) {
        fwrite(STDERR, "\n  ABORT check() takes (label, condition) -- arguments look reversed\n");
        exit(2);
    }

    if (!$cond) {
        echo "  FAIL  $label\n";
        $GLOBALS['scf_failures']++;
        return;
    }
    if (!$GLOBALS['scf_quiet']) {
        echo "  ok    $label\n";
    }
}

function section($title)
{
    if (!$GLOBALS['scf_quiet']) {
        echo "\n== $title ==\n";
    }
}

/** Final line and exit code. */
function scf_done($passMessage)
{
    $n = $GLOBALS['scf_failures'];
    echo "\n" . ($n === 0 ? "PASS: $passMessage\n" : "$n FAILURE(S)\n");
    exit($n === 0 ? 0 : 1);
}

/* ------------------------------------------------------------------ *
 * Zen Cart stubs. Only what the plugin actually calls.
 * ------------------------------------------------------------------ */

if (!function_exists('zen_db_input')) {
    function zen_db_input($s) { return addslashes((string)$s); }
}
if (!function_exists('zen_output_string_protected')) {
    function zen_output_string_protected($s)
    {
        return htmlspecialchars((string)$s, ENT_COMPAT, defined('CHARSET') ? CHARSET : 'utf-8', true);
    }
}
if (!function_exists('zen_output_string')) {
    function zen_output_string($s)
    {
        return htmlspecialchars((string)$s, ENT_COMPAT, defined('CHARSET') ? CHARSET : 'utf-8', false);
    }
}
if (!function_exists('zen_not_null')) {
    function zen_not_null($v) { return !($v === null || $v === ''); }
}
if (!function_exists('zen_validate_email')) {
    function zen_validate_email($e) { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); }
}
if (!function_exists('zen_href_link')) {
    function zen_href_link($page = '', $params = '', $c = 'NONSSL', $sess = true)
    {
        return 'https://shop.example.com/index.php?main_page=' . $page
            . ($params !== '' ? '&' . $params : '');
    }
}
if (!function_exists('zen_redirect')) {
    class ScfRedirect extends Exception {}
    function zen_redirect($url) { throw new ScfRedirect($url); }
}
if (!function_exists('zen_record_admin_activity')) {
    function zen_record_admin_activity($m, $s = 'info') { $GLOBALS['SCF_ADMIN_LOG'][] = $m; }
}
if (!function_exists('zen_encrypt_password')) {
    function zen_encrypt_password($p) { return 'hashed:' . sha1($p); }
}
if (!function_exists('zen_create_PADSS_password')) {
    function zen_create_PADSS_password($len = 8)
    {
        return substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, max(8, (int)$len));
    }
}
if (!function_exists('zen_db_perform')) {
    function zen_db_perform($table, $data, $action = 'insert', $where = '')
    {
        global $db;
        $GLOBALS['SCF_PERFORMED'][$table][] = $data;
        if (isset($db)) {
            $db->log[] = strtoupper($action) . ' INTO ' . $table;
            $db->nextInsertId = ($table === 'zen_customers') ? 4242 : 99;
        }
        return true;
    }
}
if (!function_exists('zen_mail')) {
    function zen_mail($toName, $toAddr, $subject, $text, $fromName, $fromAddr, $block = [], $module = 'default')
    {
        $GLOBALS['SCF_SENT_MAIL'][] = compact('toName', 'toAddr', 'subject', 'text', 'block', 'module');
        return true;
    }
}
/* The form-drawing helpers emit real markup rather than nothing. A stub that
 * returns '' would let a harness "pass" a check on an input that was never
 * drawn -- which is how the missing type="email" would have gone unnoticed. */
if (!function_exists('zen_draw_form')) {
    function zen_draw_form($name, $action, $method = 'post', $params = '')
    {
        return '<form name="' . $name . '" action="' . htmlspecialchars($action, ENT_COMPAT)
            . '" method="' . $method . '" ' . $params . '>';
    }
}
if (!function_exists('zen_draw_input_field')) {
    function zen_draw_input_field($name, $value = '', $params = '', $type = 'text', $reinsert = true)
    {
        return '<input type="' . $type . '" name="' . $name . '"'
            . ' value="' . htmlspecialchars((string)$value, ENT_COMPAT) . '" ' . $params . '>';
    }
}
if (!function_exists('zen_draw_hidden_field')) {
    function zen_draw_hidden_field($name, $value = '', $params = '')
    {
        return '<input type="hidden" name="' . $name . '"'
            . ' value="' . htmlspecialchars((string)$value, ENT_COMPAT) . '" ' . $params . '>';
    }
}
if (!function_exists('zen_draw_radio_field')) {
    function zen_draw_radio_field($name, $value = '', $checked = false, $params = '')
    {
        return '<input type="radio" name="' . $name . '"'
            . ' value="' . htmlspecialchars((string)$value, ENT_COMPAT) . '"'
            . ($checked ? ' checked="checked"' : '') . ' ' . $params . '>';
    }
}
if (!function_exists('zen_draw_pull_down_menu')) {
    function zen_draw_pull_down_menu($name, $values = [], $default = '', $params = '')
    {
        $html = '<select name="' . $name . '" ' . $params . '>';
        foreach ((array)$values as $row) {
            $id = isset($row['id']) ? $row['id'] : '';
            $html .= '<option value="' . htmlspecialchars((string)$id, ENT_COMPAT) . '"'
                . ((string)$id === (string)$default ? ' selected="selected"' : '') . '>'
                . htmlspecialchars(isset($row['text']) ? (string)$row['text'] : '', ENT_COMPAT)
                . '</option>';
        }
        return $html . '</select>';
    }
}
if (!function_exists('zen_register_admin_page')) {
    function zen_register_admin_page() { return true; }
}
/* Zen Cart's observer base. attach() is recorded so a harness can see which
 * notifiers a plugin actually asked for. */
if (!class_exists('base')) {
    class base
    {
        public function attach($observer, $events)
        {
            foreach ((array)$events as $e) { $GLOBALS['SCF_ATTACHED'][] = $e; }
        }
        public function notify($eventID) {}
    }
}
if (!function_exists('zen_deregister_admin_pages')) {
    function zen_deregister_admin_pages($p) {}
}

$GLOBALS['SCF_SENT_MAIL'] = [];
$GLOBALS['SCF_ADMIN_LOG'] = [];
$GLOBALS['SCF_PERFORMED'] = [];
$GLOBALS['SCF_ATTACHED'] = [];

/* ------------------------------------------------------------------ *
 * A recording fake database.
 * ------------------------------------------------------------------ */

class ScfResult
{
    public $EOF = true;
    public $fields = [];
    public function __construct($rows) { if (!empty($rows)) { $this->EOF = false; $this->fields = $rows[0]; } }
    public function MoveNext() { $this->EOF = true; }
}

class ScfDb
{
    public $log = [];
    /** Rows returned for a query matching a substring: ['needle' => [rows]] */
    public $answers = [];
    public $nextInsertId = 0;

    public function prepare_input($v) { return addslashes((string)$v); }
    public function Insert_ID() { return $this->nextInsertId; }
    public function affectedRows() { return 1; }

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));
        foreach ($this->answers as $needle => $rows) {
            if (stripos($sql, $needle) !== false) {
                return new ScfResult($rows);
            }
        }
        return new ScfResult([]);
    }

    public function matching($needle)
    {
        foreach ($this->log as $s) {
            if (stripos($s, $needle) !== false) { return $s; }
        }
        return '';
    }

    public function countMatching($needle)
    {
        $n = 0;
        foreach ($this->log as $s) { if (stripos($s, $needle) !== false) { $n++; } }
        return $n;
    }

    public function reset()
    {
        $this->log = [];
        $GLOBALS['SCF_SENT_MAIL'] = [];
        $GLOBALS['SCF_ADMIN_LOG'] = [];
        $GLOBALS['SCF_PERFORMED'] = [];
    }
}
