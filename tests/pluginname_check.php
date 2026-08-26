<?php
/**
 * The "Mod Not Turned On" notice has to CLEAR, on every supported release.
 *
 * It cannot rely on the Plugin Manager scan to do that:
 *
 *   v2.2 / v2.3 / v3.0   updatePluginControl() -> upsertMany(), whose SQL ends
 *                        "ON DUPLICATE KEY UPDATE name = VALUES(name), ..."
 *                        -- the manifest value is re-read every scan.
 *
 *   v1.5.8 / v2.0 / v2.1 the same method calls Eloquent
 *                        upsert($values, ['id'], ['infs']). That third argument
 *                        is the list of columns to update on conflict and holds
 *                        only 'infs', so name and description are written by the
 *                        INSERT that created the row and never again.
 *
 * On three of six releases the notice would be permanent. This covers the
 * admin-side sync that fixes it, and the uninstall cleanup.
 *
 * Run with: off (default) | on | uninstall
 */

require __DIR__ . '/_bootstrap.php';

define('IS_ADMIN_FLAG', true);
define('DB_PREFIX', 'zen_');
define('TABLE_PLUGIN_CONTROL', 'zen_plugin_control');
define('TABLE_CONFIGURATION', 'zen_configuration');
define('TABLE_CONFIGURATION_GROUP', 'zen_configuration_group');
define('TABLE_QUERY_BUILDER', 'zen_query_builder');
define('TABLE_CUSTOMERS', 'zen_customers');
define('TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS', 'zen_social_contact_footer_subscribers');
define('PLUGIN_INSTALL_SQL_FAILURE', 'x');

$PLUGIN = scf_plugin_dir();
$state = $argv[1] ?? 'off';
$base = 'Social Contact Footer';
$suffix = ' - Mod Not Turned On';

class NameDb extends ScfDb
{
    public $storedName = 'Social Contact Footer';
    public $rowMissing = false;
    public $configStatus = 'false';

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));
        if (stripos($sql, 'SELECT name FROM zen_plugin_control') !== false) {
            return new ScfResult($this->rowMissing ? [] : [['name' => $this->storedName]]);
        }
        if (stripos($sql, 'SELECT configuration_value') !== false) {
            return new ScfResult([['configuration_value' => $this->configStatus]]);
        }
        if (stripos($sql, 'SELECT configuration_group_id') !== false) {
            return new ScfResult([['configuration_group_id' => 55]]);
        }
        if (stripos($sql, 'SELECT COUNT(*)') !== false) {
            return new ScfResult([['total' => 2]]);
        }
        if (stripos($sql, 'SHOW TABLES') !== false) {
            return new ScfResult([['x' => 'y']]);
        }
        return new ScfResult([]);
    }

    public function updates()
    {
        return array_values(array_filter($this->log, static function ($s) {
            return stripos($s, 'UPDATE zen_plugin_control') !== false;
        }));
    }
}

$db = new NameDb();

if ($state === 'uninstall') {
    section('uninstall puts the name back');
    eval('namespace Zencart\PluginSupport; class ScriptedInstaller {
        protected $dbConn; protected $errorContainer;
        public function __construct($d, $e) { $this->dbConn = $d; $this->errorContainer = $e; }
        public function doUninstall() { return $this->executeUninstall(); }
        protected function executeUninstall() { return true; }
        protected function executeInstallerSql($sql) { return $this->dbConn->Execute($sql) !== false; }
    }');
    class ScfErrors { public function addError() {} }
    require $PLUGIN . '/Installer/ScriptedInstaller.php';

    $db->storedName = $base . $suffix;
    (new ScriptedInstaller($db, new ScfErrors()))->doUninstall();
    $u = $db->updates();
    check('the name is rewritten on uninstall', count($u) === 1);
    check('and rewritten to the plain name', $u && strpos($u[0], "name = '" . $base . "'") !== false);
    check('the notice is not left behind', $u && strpos($u[0], 'Mod Not Turned On') === false);
    /* On v1.5.8-v2.1 nothing else ever rewrites this column, and the admin
     * function that keeps it honest stops loading once uninstalled -- so the
     * row would keep the notice forever in the Not Installed list. */
    check('it targets this plugin only',
        $u && strpos($u[0], "unique_key = 'SocialContactFooter'") !== false);
    scf_done('uninstall cleanup verified');
}

define('SCF_STATUS', $state === 'on' ? 'true' : 'false');
$_GET = ['cmd' => 'plugin_manager'];
/* Model the transition, not the destination: the stored name starts as whatever
 * the previous state left behind. "on" is the one that matters -- the owner has
 * just set the switch and the row still says the plugin is off. */
$db->storedName = ($state === 'on') ? $base . $suffix : $base;

require $PLUGIN . '/admin/includes/functions/extra_functions/social_contact_footer_admin.php';

$expected = ($state === 'on') ? $base : $base . $suffix;
section("admin sync, SCF_STATUS = " . SCF_STATUS);
$u = $db->updates();
check('the file corrected the row as it loaded', count($u) === 1);
check('it wrote the name the state calls for',
    $u && strpos($u[0], "name = '" . $expected . "'") !== false);
if ($state === 'on') {
    check('turning the plugin on removes the notice',
        $u && strpos($u[0], 'Mod Not Turned On') === false);
}

section('it does not write when there is nothing to correct');
$db->reset();
$db->storedName = $expected;
scf_admin_sync_plugin_name();
check('no UPDATE when the stored name is already right', count($db->updates()) === 0);

section('it stays off the rest of the admin');
$db->reset();
$db->storedName = 'something stale';
$_GET = ['cmd' => 'configuration'];
scf_admin_sync_plugin_name();
check('no query at all away from Plugin Manager, the only page showing this column',
    count($db->log) === 0);
$db->reset();
$_GET = [];
scf_admin_sync_plugin_name();
check('no query when cmd is absent', count($db->log) === 0);

section('a row that does not exist yet');
$db->reset();
$db->rowMissing = true;
$_GET = ['cmd' => 'plugin_manager'];
scf_admin_sync_plugin_name();
check('nothing is written before Plugin Manager has scanned', count($db->updates()) === 0);

section('the name is safe for the column');
check('the off-state name fits varchar(64): ' . strlen($base . $suffix) . ' chars',
    strlen($base . $suffix) <= 64);
check('it is plain text, so truncation could never break the markup',
    strpos($base . $suffix, '<') === false);
check('the constants match the manifest exactly',
    SCF_PLUGIN_BASE_NAME === $base && SCF_PLUGIN_OFF_SUFFIX === $suffix);
check('the manifest builds the same suffix (a mismatch would fight this sync forever)',
    strpos(file_get_contents($PLUGIN . '/manifest.php'), "' - Mod Not Turned On'") !== false);

if ($state === 'off') {
    foreach (['on', 'uninstall'] as $child) {
        $out = [];
        $status = 1;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $child . ' 2>&1', $out, $status);
        echo "\n" . implode("\n", $out) . "\n";
        if ($status !== 0) { $GLOBALS['scf_failures']++; }
    }
}

scf_done('the notice clears itself on every release');
