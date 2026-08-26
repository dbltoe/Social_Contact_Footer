<?php
/**
 * The two Newsletter Manager audiences.
 *
 * The last section is the important one: it runs the generated SQL through a
 * faithful copy of Zen Cart's own parsed_query_string(), where a mistake
 * surfaces as a fatal on the audience page rather than as anything the
 * installer could catch.
 *
 * And "No Account Yet" shipped wrong once -- its SQL was `where status = 1` and
 * nothing more, so an audience whose name promises account-less subscribers
 * returned every confirmed one, account holders included. Found on a live store,
 * not here. The name is a promise; these assertions keep it.
 */

require __DIR__ . '/_bootstrap.php';

// The installer runs in the admin, and it requires shared/networks.php, which
// is guarded against direct access. Without this the guard fires and the
// harness exits silently with status 0 -- which is why scf_done() is the only
// thing allowed to decide the exit code below.
define('IS_ADMIN_FLAG', true);
define('DB_PREFIX', 'zen_');
define('TABLE_CONFIGURATION', 'zen_configuration');
define('TABLE_CONFIGURATION_GROUP', 'zen_configuration_group');
define('TABLE_QUERY_BUILDER', 'zen_query_builder');
define('TABLE_CUSTOMERS', 'zen_customers');
define('TABLE_PLUGIN_CONTROL', 'zen_plugin_control');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');
define('PLUGIN_INSTALL_SQL_FAILURE', 'x');

eval('namespace Zencart\PluginSupport; class ScriptedInstaller {
    protected $dbConn; protected $errorContainer;
    public function __construct($d, $e) { $this->dbConn = $d; $this->errorContainer = $e; }
    public function doInstall() { return $this->executeInstall(); }
    public function doUninstall() { return $this->executeUninstall(); }
    protected function executeInstall() { return true; }
    protected function executeUninstall() { return true; }
    protected function executeInstallerSql($sql) { return $this->dbConn->Execute($sql) !== false; }
}');
class ScfErrors { public function addError() {} }

$db = new ScfDb();
$db->answers = [
    'SELECT configuration_group_id' => [['configuration_group_id' => 55]],
    'SELECT configuration_value' => [['configuration_value' => 'false']],
    'SELECT COUNT(*)' => [['total' => 0]],
];

require scf_plugin_dir() . '/Installer/ScriptedInstaller.php';

/** Faithful copy of includes/functions/audience.php. */
function parsed_query_string($read_string)
{
    $good = '';
    foreach (explode(' ', $read_string) as $val) {
        if (substr($val, 0, 7) === '{TABLE_' && substr($val, -1) === '}') {
            $val = constant(substr($val, 2, strlen($val) - 2));
        } elseif (substr($val, 0, 6) === 'TABLE_') {
            // The line that throws if the token is not exactly a defined name.
            $val = constant($val);
        }
        $good .= $val . ' ';
    }
    return $good;
}

section('install registers both audiences');
(new ScriptedInstaller($db, new ScfErrors()))->doInstall();
$inserts = array_values(array_filter($db->log, static function ($s) {
    return stripos($s, 'INSERT INTO zen_query_builder') !== false;
}));
check('two audience queries registered', count($inserts) === 2);
check('existing rows cleared first, so re-install stays idempotent',
    $db->countMatching('DELETE FROM zen_query_builder') === 1);

$all = implode("\n", $db->log);
check('the everyone audience is offered',
    strpos($all, 'Newsletter Subscribers: Everyone (Social Contact Footer)') !== false);
check('the no-account audience is offered',
    strpos($all, 'Newsletter Subscribers: No Account Yet (Social Contact Footer)') !== false);
check('both are categorised so the Newsletter Manager lists them',
    substr_count($all, "'email,newsletters'") === 2);

/* The footer's only signup is the newsletter; the icons and blog link are links.
 * So a name must not imply subscribing to anything else -- what separates these
 * two is whether the person holds a customer account. */
foreach (ScriptedInstaller::AUDIENCE_QUERIES as $name) {
    check("\"$name\" leads with what they subscribed to", stripos($name, 'newsletter') === 0);
    check("\"$name\" does not imply subscribing to the footer itself",
        preg_match('~footer subscriber~i', $name) !== 1);
}

section("the SQL survives Zen Cart's parser");
$queries = [];
foreach ($inserts as $sql) {
    if (preg_match("~'(select .*?)',\s*''\)~is", $sql, $m)) { $queries[] = stripslashes($m[1]); }
}
check('both query strings extracted', count($queries) === 2);

foreach ($queries as $i => $q) {
    $n = $i + 1;
    check("query $n is a single line (a newline would break the token split)",
        strpos($q, "\n") === false);
    $parsed = null;
    try {
        $parsed = parsed_query_string($q);
        check("query $n parses without throwing", true);
    } catch (\Throwable $e) {
        check("query $n parses without throwing -- " . $e->getMessage(), false);
    }
    if ($parsed !== null) {
        check("query $n has every TABLE_ token substituted", strpos($parsed, 'TABLE_') === false);
        check("query $n reaches the plugin's subscriber table",
            strpos($parsed, 'zen_social_contact_footer_subscribers') !== false);
        foreach (['customers_firstname', 'customers_lastname', 'customers_email_address'] as $col) {
            check("query $n returns $col, which the mailer requires", strpos($parsed, $col) !== false);
        }
    }
}

section("'No Account Yet' really means no account");
$noAccount = $queries[1] ?? '';
check('it excludes anyone already in the customers table',
    stripos($noAccount, 'not in') !== false && stripos($noAccount, 'customers_email_address') !== false);
/* By address, not by this plugin's own customers_id column: that column records
 * what was true when they subscribed, so somebody who registered separately
 * afterwards would still read 0 and be mailed as account-less. */
check('it excludes by address, not by the stored customers_id',
    stripos($noAccount, 'customers_id') === false);
check('it still restricts to confirmed subscribers', stripos($noAccount, 'status = 1') !== false);
check('the two audiences are genuinely different queries', ($queries[0] ?? '') !== $noAccount);

section('the everyone audience does not double-send');
$combined = $queries[0] ?? '';
check('both lists are combined', stripos($combined, 'union') !== false);
check('subscribers already present as customer subscribers are excluded',
    stripos($combined, 'not in') !== false);
check('only confirmed subscribers are included -- pending ones are never mailed',
    stripos($combined, 'status = 1') !== false);

section('uninstall removes them');
$db2 = new ScfDb();
$db2->answers = $db->answers;
(new ScriptedInstaller($db2, new ScfErrors()))->doUninstall();
$all2 = implode("\n", $db2->log);
check('audience queries are deleted on uninstall',
    stripos($all2, 'DELETE FROM zen_query_builder') !== false);
foreach (ScriptedInstaller::AUDIENCE_QUERIES as $name) {
    check("current name is in the delete: \"$name\"", strpos($all2, $name) !== false);
}
/* A store that installed an earlier build has rows under the old names. They
 * reference this plugin's table constant too, and parsed_query_string() calls
 * constant() unguarded -- so a leftover row makes the audience page fatal on
 * PHP 8 once the plugin's files stop loading. */
foreach (ScriptedInstaller::RETIRED_AUDIENCE_QUERIES as $name) {
    check("retired name is also deleted: \"$name\"", strpos($all2, $name) !== false);
}
check('no name appears in both lists',
    count(array_intersect(ScriptedInstaller::AUDIENCE_QUERIES, ScriptedInstaller::RETIRED_AUDIENCE_QUERIES)) === 0);

section('the installer leaves an audit trail');
/* The installer logged nothing at all until a subscriber went missing and there
 * was no way to answer "where did my list go?". */
check('uninstall records whether the table was kept or dropped',
    count(array_filter($GLOBALS['SCF_ADMIN_LOG'], static function ($m) {
        return stripos($m, 'KEPT') !== false || stripos($m, 'DROPPED') !== false;
    })) === 1);

scf_done('newsletter audiences verified');
