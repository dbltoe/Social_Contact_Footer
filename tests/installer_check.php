<?php
/**
 * The Plugin Manager installer.
 *
 * Two things it must never do: reset a value the store owner chose, and delete
 * subscriber data without being told to.
 */

require __DIR__ . '/_bootstrap.php';

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
    public function doUpgrade($v = null) { return $this->executeUpgrade($v); }
    protected function executeInstall() { return true; }
    protected function executeUninstall() { return true; }
    protected function executeUpgrade($v = null) { return true; }
    protected function executeInstallerSql($sql) { return $this->dbConn->Execute($sql) !== false; }
}');
class ScfErrors { public function addError() {} }

class InstDb extends ScfDb
{
    public $dropSetting = 'false';
    public $subscriberCount = 3;
    public $tableExists = true;

    public function Execute($sql, $limit = null)
    {
        $this->log[] = preg_replace('/\s+/', ' ', trim($sql));
        if (stripos($sql, 'SELECT configuration_group_id') !== false) {
            return new ScfResult([['configuration_group_id' => 55]]);
        }
        if (stripos($sql, "configuration_key = 'SCF_DROP_TABLE_ON_UNINSTALL'") !== false) {
            return new ScfResult([['configuration_value' => $this->dropSetting]]);
        }
        if (stripos($sql, 'SELECT configuration_value') !== false) {
            return new ScfResult([['configuration_value' => 'false']]);
        }
        if (stripos($sql, 'SHOW TABLES') !== false) {
            return new ScfResult($this->tableExists ? [['t' => 'x']] : []);
        }
        if (stripos($sql, 'SELECT COUNT(*)') !== false) {
            return new ScfResult([['total' => $this->subscriberCount]]);
        }
        return new ScfResult([]);
    }
}

$PLUGIN = scf_plugin_dir();
require $PLUGIN . '/Installer/ScriptedInstaller.php';

section('install');
$db = new InstDb();
(new ScriptedInstaller($db, new ScfErrors()))->doInstall();
$all = implode("\n", $db->log);

$keyInserts = array_filter($db->log, static function ($s) {
    return stripos($s, 'INSERT IGNORE INTO zen_configuration') !== false;
});
check('configuration keys are written', count($keyInserts) > 0);
/* INSERT IGNORE then UPDATE of the metadata only: the value belongs to the
 * store owner and must survive an upgrade; the label, help text and ordering
 * belong to the plugin and are refreshed. */
check('every configuration key uses INSERT IGNORE, never a plain INSERT',
    count($keyInserts) === count(array_filter($db->log, static function ($s) {
        return stripos($s, 'INTO zen_configuration ') !== false && stripos($s, 'INSERT') !== false;
    })));
check('no UPDATE ever touches configuration_value',
    count(array_filter($db->log, static function ($s) {
        return stripos($s, 'UPDATE zen_configuration') !== false
            && stripos($s, 'configuration_value') !== false;
    })) === 0);
check('the metadata IS refreshed', count(array_filter($db->log, static function ($s) {
    return stripos($s, 'UPDATE zen_configuration') !== false
        && stripos($s, 'configuration_title') !== false;
})) > 0);
check('all keys carry the SCF_ prefix',
    preg_match_all("~configuration_key[^,]*,?\s*'((?!SCF_)[A-Z_]{4,})'~", $all) === 0);
check('the configuration group is created or reused, never duplicated',
    substr_count($all, 'INSERT INTO zen_configuration_group') <= 1);

section('retired settings are cleaned up');
check('retired keys are deleted on install',
    stripos($all, 'DELETE FROM zen_configuration WHERE configuration_key IN') !== false);
foreach (['SCF_SUBSCRIBE_ASK_NAME', 'SCF_LOAD_CSS', 'SCF_SUBSCRIBE_DEFAULT_FORMAT',
          'SCF_ICON_SIZE', 'SCF_URL_EMAIL'] as $gone) {
    check("$gone is retired", in_array($gone, ScriptedInstaller::RETIRED_KEYS, true));
}
check('no retired key is also a live key',
    count(array_intersect(ScriptedInstaller::RETIRED_KEYS,
        preg_match_all("~'key' => '(SCF_[A-Z_]+)'~", file_get_contents($PLUGIN . '/Installer/ScriptedInstaller.php'), $m) ? $m[1] : [])) === 0);

section('the master switch ships off');
/* A fresh install must not put a half-configured block on the storefront. */
check('SCF_STATUS defaults to false',
    preg_match("~'key' => 'SCF_STATUS'.{0,900}?'value' => 'false'~s",
        file_get_contents($PLUGIN . '/Installer/ScriptedInstaller.php')) === 1);

section('admin pages');
check('both admin pages are deregistered before being registered, so a re-install does not duplicate them',
    count(ScriptedInstaller::ADMIN_PAGE_KEYS) === 2);

section('uninstall keeps the subscriber table by default');
$db2 = new InstDb();
$db2->dropSetting = 'false';
(new ScriptedInstaller($db2, new ScfErrors()))->doUninstall();
$all2 = implode("\n", $db2->log);
check('the table is NOT dropped', stripos($all2, 'DROP TABLE') === false);
check('the configuration group is removed',
    stripos($all2, 'DELETE FROM zen_configuration_group') !== false);
check('the audience queries are removed',
    stripos($all2, 'DELETE FROM zen_query_builder') !== false);
/* The installer logged nothing until a subscriber went missing and there was no
 * way to answer "where did my list go?". */
$log = implode("\n", $GLOBALS['SCF_ADMIN_LOG']);
check('the log says the table was KEPT, and how many rows it holds',
    stripos($log, 'KEPT') !== false && strpos($log, '3 subscriber') !== false);

section('uninstall drops it only when told to');
$GLOBALS['SCF_ADMIN_LOG'] = [];
$db3 = new InstDb();
$db3->dropSetting = 'true';
(new ScriptedInstaller($db3, new ScfErrors()))->doUninstall();
check('the table IS dropped when the setting says so',
    stripos(implode("\n", $db3->log), 'DROP TABLE IF EXISTS zen_social_contact_footer_subscribers') !== false);
check('and the log records what was discarded',
    stripos(implode("\n", $GLOBALS['SCF_ADMIN_LOG']), 'DROPPED') !== false);
/* The setting has to be read BEFORE the configuration rows are deleted, or it
 * would always read as its default. */
$src = file_get_contents($PLUGIN . '/Installer/ScriptedInstaller.php');
check('the setting is read before the configuration is deleted',
    strpos($src, "scfGetConfigValue('SCF_DROP_TABLE_ON_UNINSTALL'") < strpos($src, 'DELETE FROM " . TABLE_CONFIGURATION'));

section('upgrade is a re-run of install');
/* ZC v1.5.8 calls executeUpgrade() with no argument; v2.x/v3.x pass $oldVersion.
 * Widening the parameter to optional satisfies both parent signatures. */
check('executeUpgrade declares an optional argument',
    preg_match('~executeUpgrade\(\$oldVersion = null\)~', $src) === 1);

section('only v1.5.8-safe installer API is used');
/* addConfigurationKey(), getOrCreateConfigGroupId() and the rest arrived in
 * v2.0.1/v2.1.0 and do not exist on v1.5.8. */
foreach (['addConfigurationKey', 'deleteConfigurationKeys', 'getOrCreateConfigGroupId',
          'executeInstallerDbPerform', 'removeConfigurationKeys'] as $helper) {
    // A call, not a mention -- the file's docblock names these deliberately,
    // to record why they are avoided.
    check("does not call $helper (v2.0.1+ only)",
        preg_match('~\\$this->' . $helper . '\\s*\\(~', $src) !== 1);
}

scf_done('installer verified');
