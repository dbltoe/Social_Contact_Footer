<?php
/**
 * Social Contact Footer -- Plugin Manager installer.
 *
 * Deliberately limited to the API that exists in every supported Zen Cart
 * release (v1.5.8 -> v3.0.0):
 *
 *   - only `executeInstallerSql()` is used for database work. The convenience
 *     helpers (`addConfigurationKey()`, `getOrCreateConfigGroupId()`, ...) were
 *     added in ZC v2.0.1/v2.1.0 and do not exist on v1.5.8.
 *   - `executeUpgrade()` is declared with an optional argument, because ZC
 *     v1.5.8 calls it with none and ZC v2.x/v3.x calls it with $oldVersion.
 *
 * Every step is idempotent, so an upgrade is simply a re-run of the install.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    /**
     * Title of the configuration group created for this plugin.
     */
    const CONFIG_GROUP_TITLE = 'Social Contact Footer';

    /**
     * admin_pages.page_key values this plugin owns.
     */
    const ADMIN_PAGE_KEYS = ['configSocialContactFooter', 'toolsSocialContactFooterSubscribers'];

    protected function executeInstall()
    {
        $configGroupId = $this->scfGetOrCreateConfigGroup();
        if ($configGroupId === 0) {
            return false;
        }

        $this->scfCreateSubscribersTable();
        $this->scfAddMissingColumns();
        $this->scfAlignColumnDefaults();
        $this->scfRegisterAudienceQueries();
        $this->scfRemoveRetiredKeys();
        $this->scfAddConfigurationKeys($configGroupId);
        $this->scfRegisterAdminPages($configGroupId);
        // A fresh install leaves the master switch off, so the row should say
        // so straight away rather than on the next visit.
        $this->scfSyncPluginControlName(false);

        // Says how many subscribers survived, so an install that ran over an
        // existing table can be told apart from one that started empty.
        $this->scfLog(
            'Social Contact Footer: installed/upgraded. Subscriber table holds '
            . $this->scfCountSubscribers() . ' subscriber(s).',
            'warning'
        );

        return true;
    }

    /**
     * ZC v1.5.8 calls this with no argument; ZC v2.x/v3.x passes $oldVersion.
     */
    protected function executeUpgrade($oldVersion = null)
    {
        // The install routine is idempotent, so re-running it brings any older
        // installation up to the current schema and settings without touching
        // values the store owner has already customised.
        return $this->executeInstall();
    }

    protected function executeUninstall()
    {
        // Read this setting before the configuration keys are removed.
        $dropTable = $this->scfGetConfigValue('SCF_DROP_TABLE_ON_UNINSTALL', 'false');

        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);

        // Must happen before the plugin's files stop loading: the stored SQL
        // references a table constant this plugin defines, and Zen Cart's
        // audience parser calls constant() on it without checking.
        $this->scfRemoveAudienceQueries();

        $groupId = $this->scfGetConfigGroupId();
        if ($groupId > 0) {
            $this->executeInstallerSql(
                "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_group_id = " . $groupId
            );
            $this->executeInstallerSql(
                "DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . $groupId
            );
        }

        // Count before deciding, so the log can say what was actually lost
        // rather than that something was.
        $subscriberCount = $this->scfCountSubscribers();

        if ($dropTable === 'true') {
            $this->executeInstallerSql("DROP TABLE IF EXISTS " . $this->scfSubscribersTable());
            $this->scfLog(
                'Social Contact Footer: UNINSTALLED and the subscriber table was DROPPED, '
                . 'discarding ' . $subscriberCount . ' subscriber(s). '
                . 'Delete Subscribers When Uninstalling? was set to true.',
                'warning'
            );
        } else {
            // Said explicitly. "Where did my list go?" is a question somebody
            // will eventually ask, and this is the line that answers it.
            $this->scfLog(
                'Social Contact Footer: uninstalled. The subscriber table was KEPT, '
                . 'with ' . $subscriberCount . ' subscriber(s) still in it.',
                'warning'
            );
        }

        // Put the plugin's name back before the row stops being ours to fix.
        //
        // On v1.5.8/v2.0/v2.1 the Plugin Manager scan never rewrites
        // plugin_control.name for an existing row, and the admin function that
        // keeps it honest stops loading the moment the plugin is uninstalled.
        // Skipping this would leave "Social Contact Footer - Mod Not Turned On"
        // sitting in the Not Installed list permanently.
        $this->scfSyncPluginControlName(true);

        return true;
    }

    /**
     * Write a line to the admin activity log, if we can.
     *
     * Guarded because the installer runs in an unusual context -- Plugin
     * Manager, mid-transaction, sometimes before this plugin's own files are
     * loaded -- and a missing logger must never be the thing that fails an
     * install or, worse, an uninstall.
     *
     * @param string $message
     * @param string $severity
     * @return void
     */
    protected function scfLog($message, $severity = 'info')
    {
        if (function_exists('zen_record_admin_activity')) {
            zen_record_admin_activity($message, $severity);
        }
    }

    /**
     * How many subscribers are in the table right now.
     *
     * Returns 0 when the table is not there, which is also the honest answer.
     *
     * @return int
     */
    protected function scfCountSubscribers()
    {
        $table = $this->scfSubscribersTable();

        $exists = $this->dbConn->Execute(
            "SHOW TABLES LIKE '" . $this->dbConn->prepare_input($table) . "'"
        );
        if ($exists->EOF) {
            return 0;
        }

        $count = $this->dbConn->Execute("SELECT COUNT(*) AS total FROM " . $table);

        return $count->EOF ? 0 : (int)$count->fields['total'];
    }

    /**
     * plugin_control.unique_key for this plugin -- its zc_plugins directory.
     */
    const PLUGIN_KEY = 'SocialContactFooter';
    const PLUGIN_BASE_NAME = 'Social Contact Footer';
    const PLUGIN_OFF_SUFFIX = ' - Mod Not Turned On';

    /**
     * Write the name the current state calls for.
     *
     * @param bool $clean True to force the plain name, used on uninstall.
     * @return bool
     */
    protected function scfSyncPluginControlName($clean = false)
    {
        if (!defined('TABLE_PLUGIN_CONTROL')) {
            return true;
        }

        $name = self::PLUGIN_BASE_NAME;
        if (!$clean) {
            // Read straight from the table: on install the constant either does
            // not exist yet or still holds the value from before this run.
            $status = $this->scfGetConfigValue('SCF_STATUS', 'false');
            if ($status !== 'true') {
                $name .= self::PLUGIN_OFF_SUFFIX;
            }
        }

        return $this->executeInstallerSql(
            "UPDATE " . TABLE_PLUGIN_CONTROL . "
                SET name = '" . $this->dbConn->prepare_input($name) . "'
              WHERE unique_key = '" . $this->dbConn->prepare_input(self::PLUGIN_KEY) . "'
              LIMIT 1"
        );
    }

    /* ----------------------------------------------------------------- *
     * Helpers
     * ----------------------------------------------------------------- */

    /**
     * The subscribers table name.
     *
     * This is built from DB_PREFIX rather than a TABLE_* constant because the
     * plugin's extra_datafiles are not loaded while the plugin is being
     * installed.
     */
    protected function scfSubscribersTable()
    {
        return DB_PREFIX . 'social_contact_footer_subscribers';
    }

    protected function scfGetConfigGroupId()
    {
        $sql =
            "SELECT configuration_group_id
               FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "'
              LIMIT 1";
        $check = $this->dbConn->Execute($sql);

        return ($check->EOF) ? 0 : (int)$check->fields['configuration_group_id'];
    }

    protected function scfGetOrCreateConfigGroup()
    {
        $groupId = $this->scfGetConfigGroupId();
        if ($groupId > 0) {
            return $groupId;
        }

        $created = $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "',
                 'Social-media links and blog/newsletter subscription settings for the storefront footer.',
                 0, 1)"
        );
        if ($created === false) {
            return 0;
        }

        $groupId = $this->scfGetConfigGroupId();
        if ($groupId > 0) {
            // Zen Cart convention: a configuration group's sort_order matches its id.
            $this->executeInstallerSql(
                "UPDATE " . TABLE_CONFIGURATION_GROUP . "
                    SET sort_order = $groupId
                  WHERE configuration_group_id = $groupId
                  LIMIT 1"
            );
        }

        return $groupId;
    }

    protected function scfGetConfigValue($key, $default = '')
    {
        $sql =
            "SELECT configuration_value
               FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = '" . $this->dbConn->prepare_input($key) . "'
              LIMIT 1";
        $check = $this->dbConn->Execute($sql);

        return ($check->EOF) ? $default : $check->fields['configuration_value'];
    }

    /**
     * Drop configuration keys this version no longer uses.
     *
     * Runs on install as well as upgrade, so a re-install over an older copy
     * tidies up too. Harmless on a fresh install -- there is nothing to delete.
     */
    protected function scfRemoveRetiredKeys()
    {
        $db = $this->dbConn;
        $list = implode("','", array_map(static function ($key) use ($db) {
            return $db->prepare_input($key);
        }, self::RETIRED_KEYS));

        return $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . $list . "')"
        );
    }

    /**
     * Audience queries this plugin adds to Zen Cart's Newsletter Manager.
     *
     * `query_name` carries a UNIQUE index, and these names are what the
     * uninstaller removes, so they must match exactly in both places.
     *
     * Named by whether the person holds a customer account, NOT by what they
     * subscribed to. The footer offers exactly one subscription -- the
     * newsletter. The icons and the blog link are links; nobody signs up for
     * them. So "footer subscriber" and "newsletter subscriber" describe the
     * same person, and a name implying two kinds of subscription would be
     * describing something that does not exist.
     */
    const AUDIENCE_QUERIES = [
        'Newsletter Subscribers: Everyone (Social Contact Footer)',
        'Newsletter Subscribers: No Account Yet (Social Contact Footer)',
    ];

    /**
     * Names these queries used in an earlier build.
     *
     * Renaming a query_builder row is a delete plus an insert, so the old names
     * have to be deleted too -- both when re-registering and on uninstall. A
     * leftover row keeps a reference to this plugin's table constant, and
     * `parsed_query_string()` calls `constant()` on it without checking, so it
     * would make the Newsletter Manager's audience page fatal on PHP 8 once the
     * plugin is gone.
     */
    const RETIRED_AUDIENCE_QUERIES = [
        'Newsletter Subscribers plus Footer Subscribers',
        'Footer Subscribers only (Social Contact Footer)',
    ];

    /**
     * Make the plugin's subscribers reachable from Tools -> Newsletter Manager.
     *
     * Zen Cart builds the newsletter audience from SQL stored in the
     * `query_builder` table, and every query it ships reads only the
     * `customers` table. A footer subscriber who has no customer account is
     * therefore invisible to the Newsletter Manager -- the store owner collects
     * addresses it can never mail.
     *
     * These two entries fix that. `parsed_query_string()` splits the stored SQL
     * on spaces and replaces any token beginning with `TABLE_` by looking up a
     * PHP constant of that name, so:
     *
     *   - every TABLE_* name must be surrounded by single spaces, including
     *     next to brackets, or `constant()` is handed something like
     *     "TABLE_CUSTOMERS)" and throws;
     *   - the SQL must be one line. A newline would leave "TABLE_X\nwhere" as
     *     a single token and fail the same way.
     *
     * TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS is defined by this plugin's
     * admin extra_datafiles, which only load while the plugin is installed --
     * which is exactly why executeUninstall() must remove these rows again.
     */
    protected function scfRegisterAudienceQueries()
    {
        $db = $this->dbConn;
        $customers = 'TABLE_CUSTOMERS';
        $subscribers = 'TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS';

        // Confirmed subscribers (status 1) who have NO customer account.
        //
        // The exclusion is by address against the customers table, not against
        // this plugin's own customers_id column. That column records what was
        // true when they subscribed or were invited; somebody who registered
        // separately afterwards would still show 0 there, and this audience
        // would then mail an account holder while calling them account-less.
        // Checking the address matches what the Subscribers page shows in its
        // Account column, which is also a live lookup.
        $footerOnly =
            'select subscriber_name as customers_firstname , \'\' as customers_lastname ,'
            . ' subscriber_email as customers_email_address'
            . ' from ' . $subscribers . ' where status = 1'
            . ' and subscriber_email not in ( select customers_email_address'
            . ' from ' . $customers . ' )'
            . ' order by subscriber_email';

        // Both lists in one audience. The second half excludes anyone already
        // returned by the first, so nobody who is both a customer subscriber
        // and a footer subscriber receives two copies.
        $combined =
            'select customers_firstname , customers_lastname , customers_email_address'
            . ' from ' . $customers . ' where customers_newsletter = \'1\''
            . ' union'
            . ' select subscriber_name as customers_firstname , \'\' as customers_lastname ,'
            . ' subscriber_email as customers_email_address'
            . ' from ' . $subscribers . ' where status = 1'
            . ' and subscriber_email not in ( select customers_email_address'
            . ' from ' . $customers . ' where customers_newsletter = \'1\' )';

        $queries = [
            [
                'name' => self::AUDIENCE_QUERIES[0],
                'description' => 'Every confirmed newsletter subscriber: those who signed up with a customer account, and those who signed up in the footer without one. Addresses appearing in both lists are returned once. Use this for an ordinary newsletter.',
                'sql' => $combined,
            ],
            [
                'name' => self::AUDIENCE_QUERIES[1],
                'description' => 'Only confirmed newsletter subscribers who have no customer account -- the people the Tools page can invite to register. Anyone with an account is excluded.',
                'sql' => $footerOnly,
            ],
        ];

        // query_name is UNIQUE, so clear before inserting to stay idempotent.
        $this->scfRemoveAudienceQueries();

        foreach ($queries as $q) {
            $ok = $this->executeInstallerSql(
                "INSERT INTO " . TABLE_QUERY_BUILDER . "
                    (query_category, query_name, query_description, query_string, query_keys_list)
                 VALUES
                    ('email,newsletters',
                     '" . $db->prepare_input($q['name']) . "',
                     '" . $db->prepare_input($q['description']) . "',
                     '" . $db->prepare_input($q['sql']) . "',
                     '')"
            );
            if ($ok === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove this plugin's audience queries, under their current names and
     * every name they have ever had.
     *
     * Not optional on uninstall: the stored SQL names a constant that only
     * exists while the plugin is installed, and `parsed_query_string()` calls
     * `constant()` on it unguarded. Leaving these rows behind would make the
     * Newsletter Manager's audience page fatal on PHP 8.
     */
    protected function scfRemoveAudienceQueries()
    {
        $db = $this->dbConn;
        $all = array_merge(self::AUDIENCE_QUERIES, self::RETIRED_AUDIENCE_QUERIES);
        $names = implode("','", array_map(static function ($name) use ($db) {
            return $db->prepare_input($name);
        }, $all));

        return $this->executeInstallerSql(
            "DELETE FROM " . TABLE_QUERY_BUILDER . " WHERE query_name IN ('" . $names . "')"
        );
    }

    /**
     * Bring an existing subscribers table up to the current column defaults.
     *
     * CREATE TABLE IF NOT EXISTS leaves an existing table exactly as it is, so
     * a store installed before this change still has the old default. Only the
     * DEFAULT is altered -- no row's stored value is touched.
     *
     * v1.0.0 shipped this column defaulting to 'HTML'. Nothing ever relied on
     * it (every insert names the value), but a default that assumes HTML is the
     * wrong safety net for a plugin whose whole point is that the format is the
     * subscriber's choice. Plain text assumes least.
     */
    protected function scfAlignColumnDefaults()
    {
        return $this->executeInstallerSql(
            "ALTER TABLE " . $this->scfSubscribersTable() . "
                MODIFY email_format varchar(4) NOT NULL default 'TEXT'"
        );
    }

    /**
     * Does a column already exist on the subscribers table?
     *
     * MySQL 5.7 has no `ADD COLUMN IF NOT EXISTS` (MariaDB does), and the
     * supported floor is 5.7 -- so upgrades have to look before they leap.
     */
    protected function scfColumnExists($column)
    {
        $result = $this->dbConn->Execute(
            "SHOW COLUMNS FROM " . $this->scfSubscribersTable() . "
              LIKE '" . $this->dbConn->prepare_input($column) . "'"
        );

        return ($result !== false && !$result->EOF);
    }

    /**
     * Add columns introduced after a store's table was first created.
     *
     * CREATE TABLE IF NOT EXISTS leaves an existing table untouched, so every
     * column added after v1.0.0's first release has to be back-filled here.
     */
    protected function scfAddMissingColumns()
    {
        $table = $this->scfSubscribersTable();

        // Registration invitations. Deliberately a separate token from
        // confirm_token: that one doubles as the permanent unsubscribe link,
        // and an unsubscribe click must never activate an account.
        $columns = [
            'invite_token' => "ADD COLUMN invite_token varchar(64) NOT NULL default '' AFTER token_expires",
            'invite_sent' => "ADD COLUMN invite_sent datetime default NULL AFTER invite_token",
            'invite_accepted' => "ADD COLUMN invite_accepted datetime default NULL AFTER invite_sent",
        ];

        foreach ($columns as $column => $clause) {
            if ($this->scfColumnExists($column)) {
                continue;
            }
            if ($this->executeInstallerSql("ALTER TABLE $table $clause") === false) {
                return false;
            }
        }

        if (!$this->scfIndexExists('idx_scf_invite')) {
            $this->executeInstallerSql("ALTER TABLE $table ADD INDEX idx_scf_invite (invite_token)");
        }

        return true;
    }

    /**
     * @param string $indexName
     * @return bool
     */
    protected function scfIndexExists($indexName)
    {
        $result = $this->dbConn->Execute(
            "SHOW INDEX FROM " . $this->scfSubscribersTable() . "
              WHERE Key_name = '" . $this->dbConn->prepare_input($indexName) . "'"
        );

        return ($result !== false && !$result->EOF);
    }

    protected function scfCreateSubscribersTable()
    {
        $table = $this->scfSubscribersTable();

        // status: 0 = pending confirmation, 1 = subscribed, 2 = unsubscribed
        return $this->executeInstallerSql(
            "CREATE TABLE IF NOT EXISTS $table (
                subscriber_id int(11) NOT NULL AUTO_INCREMENT,
                customers_id int(11) NOT NULL default 0,
                subscriber_name varchar(96) NOT NULL default '',
                subscriber_email varchar(190) NOT NULL default '',
                email_format varchar(4) NOT NULL default 'TEXT',
                status tinyint(1) NOT NULL default 0,
                confirm_token varchar(64) NOT NULL default '',
                token_expires datetime default NULL,
                invite_token varchar(64) NOT NULL default '',
                invite_sent datetime default NULL,
                invite_accepted datetime default NULL,
                ip_address varchar(45) NOT NULL default '',
                language_id int(11) NOT NULL default 1,
                date_added datetime default NULL,
                date_confirmed datetime default NULL,
                date_unsubscribed datetime default NULL,
                last_modified datetime default NULL,
                PRIMARY KEY (subscriber_id),
                UNIQUE KEY idx_scf_email (subscriber_email),
                KEY idx_scf_status (status),
                KEY idx_scf_token (confirm_token),
                KEY idx_scf_invite (invite_token)
            ) ENGINE=MyISAM"
        );
    }

    protected function scfRegisterAdminPages($configGroupId)
    {
        // zen_register_admin_page() performs a plain INSERT, so clear first.
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);

        zen_register_admin_page(
            'configSocialContactFooter',
            'BOX_CONFIGURATION_SOCIAL_CONTACT_FOOTER',
            'FILENAME_CONFIGURATION',
            'gID=' . $configGroupId,
            'configuration',
            'Y',
            $configGroupId
        );

        zen_register_admin_page(
            'toolsSocialContactFooterSubscribers',
            'BOX_TOOLS_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS',
            'FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS',
            '',
            'tools',
            'Y',
            80
        );
    }

    /**
     * Insert every configuration key. INSERT IGNORE keeps this idempotent --
     * `configuration_key` carries a UNIQUE index in every supported release, so
     * settings the store owner has already changed are never overwritten.
     */
    protected function scfAddConfigurationKeys($configGroupId)
    {
        foreach ($this->scfConfigurationKeys($configGroupId) as $key) {
            $sql =
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description,
                     configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('" . $this->dbConn->prepare_input($key['title']) . "',
                     '" . $this->dbConn->prepare_input($key['key']) . "',
                     '" . $this->dbConn->prepare_input($key['value']) . "',
                     '" . $this->dbConn->prepare_input($key['description']) . "',
                     " . (int)$configGroupId . ",
                     " . (int)$key['sort_order'] . ",
                     now(),
                     NULL,
                     " . (empty($key['set_function']) ? 'NULL' : "'" . $this->dbConn->prepare_input($key['set_function']) . "'") . ")";

            if ($this->executeInstallerSql($sql) === false) {
                return false;
            }

            // INSERT IGNORE deliberately leaves an existing row alone, which is
            // right for the VALUE -- that belongs to the store owner and must
            // never be reset by an upgrade. It is wrong for everything else:
            // the label, the help text, the ordering and the input type all
            // belong to the plugin, and a store upgrading from an earlier
            // version should get the current wording rather than keep the old.
            //
            // So follow the insert with an update of the metadata only. On a
            // fresh install this is a no-op rewrite of what was just inserted.
            $update =
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_title = '" . $this->dbConn->prepare_input($key['title']) . "',
                        configuration_description = '" . $this->dbConn->prepare_input($key['description']) . "',
                        configuration_group_id = " . (int)$configGroupId . ",
                        sort_order = " . (int)$key['sort_order'] . ",
                        set_function = " . (empty($key['set_function']) ? 'NULL' : "'" . $this->dbConn->prepare_input($key['set_function']) . "'") . ",
                        last_modified = now()
                  WHERE configuration_key = '" . $this->dbConn->prepare_input($key['key']) . "'
                  LIMIT 1";

            if ($this->executeInstallerSql($update) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Configuration keys retired in later releases of the plugin.
     *
     * Removed on both install and upgrade so an older installation does not
     * leave orphaned settings sitting in the Configuration list.
     */
    const RETIRED_KEYS = [
        'SCF_ICON_SIZE',                 // split into desktop and mobile
        'SCF_LOAD_CSS',                  // stylesheet is required, not optional
        'SCF_SUBSCRIBE_DEFAULT_FORMAT',  // pre-selecting a mail format is not lawful in the US
        'SCF_BLOG_LINK_TEXT',            // link text now built from STORE_NAME
        'SCF_SUBSCRIBE_HEADING',         // heading now built from STORE_NAME
        'SCF_SUBSCRIBE_TYPE',            // blog and newsletter are separate now
        'SCF_SUBSCRIBE_CONFIRM',         // double opt-in is not optional
        'SCF_SUBSCRIBE_SYNC_CUSTOMER',   // always done, once confirmed
        'SCF_SUBSCRIBE_NOTIFY_ADMIN',    // always done, once confirmed
        'SCF_URL_EMAIL',                 // replaced by SCF_CONTACT_LINK
        'SCF_SUBSCRIBE_ASK_NAME',        // the form no longer asks for a name at all
    ];

    /**
     * The full list of configuration records this plugin owns.
     *
     * Titles are Title Case and end in a colon, or a question mark where the
     * setting is a yes/no question.
     */
    protected function scfConfigurationKeys($configGroupId)
    {
        $boolean = "zen_cfg_select_option(array('true', 'false'), ";

        $keys = [
            [
                'key' => 'SCF_STATUS',
                'title' => 'Enable Social Contact Footer?',
                // Off on a fresh install, deliberately. A store owner who
                // installs and then does not get back to the configuration for
                // a day or two would otherwise have a half-set-up block live on
                // the storefront in the meantime. Nothing appears until they
                // say so.
                'value' => 'false',
                'description' => 'Master switch, and <strong>off on a fresh install</strong> so nothing appears on your storefront until you have set the block up. Set to <strong>true</strong> when you are ready; set it back to <strong>false</strong> to hide the whole block again without uninstalling.',
                'sort_order' => 10,
                'set_function' => $boolean,
            ],
            [
                'key' => 'SCF_DISABLE_ON_PAGES',
                'title' => 'Hide On These Pages:',
                'value' => 'checkout_success,checkout_confirmation,checkout_payment,checkout_shipping,login,create_account',
                'description' => 'Comma-separated list of <code>main_page</code> values on which the block should <em>not</em> be shown. Leave empty to show it everywhere.',
                'sort_order' => 20,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_DROP_TABLE_ON_UNINSTALL',
                'title' => 'Delete Subscribers When Uninstalling?',
                'value' => 'false',
                'description' => 'When <strong>false</strong> (recommended) the subscriber table survives an uninstall so no addresses are lost. Set to <strong>true</strong> only if you want Plugin Manager to drop the table permanently.',
                'sort_order' => 40,
                'set_function' => $boolean,
            ],

            /* ---- icon appearance ---- */
            [
                'key' => 'SCF_WRAPPER_BACKGROUND',
                'title' => 'Block Background Color:',
                'value' => '',
                'description' => 'Optional, and off by default: the block simply sits on whatever your footer already uses. <strong>Set it to <code>#FFFFFF</code></strong> (or any CSS color) if you want the block to carry its own background. That is worth doing for accessibility -- the icon badges use fixed brand colors, and the text takes its color from your template, so on an unusual footer background the contrast of what sits inside this block cannot be predicted. Giving it a known surface makes it predictable. An unrecognised value is ignored rather than guessed at.',
                'sort_order' => 95,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICONS_HEADING',
                'title' => 'Icons Heading:',
                'value' => '',
                'description' => 'Heading shown above the icon row. Leave empty to use the translatable default (<em>Follow us</em>). Enter a single space to show no heading at all.',
                'sort_order' => 100,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICON_SIZE_DESKTOP',
                'title' => 'Icons Size On Desktop:',
                'value' => '32',
                'description' => 'Icon width and height in pixels on screens wider than 767px. Sensible range is 20-64. Anything outside 12-128 falls back to 32.',
                'sort_order' => 110,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICON_SIZE_MOBILE',
                'title' => 'Icons Size On Mobile:',
                'value' => '24',
                'description' => 'Icon width and height in pixels on screens 767px and narrower. Smaller than the desktop size usually works better, since footer icons compete with everything else for a short screen.',
                'sort_order' => 115,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICON_STYLE',
                'title' => 'Icons Color Style:',
                'value' => 'Brand colors',
                'description' => '<strong>Brand colors</strong> fills each badge with that network\'s color. <strong>Monochrome</strong> uses a single color for all of them (see the next setting). <strong>Inherit</strong> draws them in your template\'s own text color, which is usually right for a dark footer.',
                'sort_order' => 120,
                'set_function' => "zen_cfg_select_option(array('Brand colors', 'Monochrome', 'Inherit'), ",
            ],
            [
                'key' => 'SCF_ICON_MONO_COLOR',
                'title' => 'Icons Monochrome Color:',
                'value' => '#444444',
                'description' => 'Any CSS color value. Only used when <em>Icons Color Style</em> is set to <strong>Monochrome</strong>.',
                'sort_order' => 130,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICON_SHAPE',
                'title' => 'Icons Badge Shape:',
                'value' => 'Rounded',
                'description' => 'Shape of the colored badge behind each glyph. <strong>Bare</strong> draws the glyph on its own with no badge.',
                'sort_order' => 140,
                'set_function' => "zen_cfg_select_option(array('Circle', 'Rounded', 'Square', 'Bare'), ",
            ],
            [
                'key' => 'SCF_ICON_ALIGN',
                'title' => 'Icons Alignment:',
                'value' => 'Center',
                'description' => 'Horizontal alignment of the whole block within the footer.',
                'sort_order' => 150,
                'set_function' => "zen_cfg_select_option(array('Left', 'Center', 'Right'), ",
            ],
            [
                'key' => 'SCF_ICON_SOURCE',
                'title' => 'Icons Artwork Source:',
                'value' => 'Built-in SVG',
                'description' => 'The built-in glyphs are simplified original drawings, <em>not</em> the official brand logos. Choose <strong>Image files</strong> to use your own artwork instead -- see the next setting.',
                'sort_order' => 160,
                'set_function' => "zen_cfg_select_option(array('Built-in SVG', 'Image files'), ",
            ],
            [
                'key' => 'SCF_ICON_IMAGE_DIR',
                'title' => 'Icons Image Directory:',
                'value' => 'social_contact_footer/',
                'description' => 'Directory beneath your store\'s <code>images/</code> folder holding one file per network, named after the network key (for example <code>facebook.svg</code>, <code>youtube.png</code>). Only used when <em>Icons Artwork Source</em> is <strong>Image files</strong>.',
                'sort_order' => 170,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_ICON_ORDER',
                'title' => 'Icons Display Order:',
                'value' => '',
                'description' => 'Comma-separated list of network keys controlling the order icons appear in, for example <code>facebook,instagram,youtube</code>. Any network you leave out is appended afterwards in its default position. Leave empty for the default order.',
                'sort_order' => 180,
                'set_function' => '',
            ],
            [
                'key' => 'SCF_LINK_TARGET',
                'title' => 'Icons Open Links In:',
                'value' => '_blank',
                'description' => 'Where off-site links open. <code>_blank</code> opens a new tab, with <code>rel="noopener noreferrer"</code> applied automatically. Links that stay on your own store always open in the same tab, whichever you choose.',
                'sort_order' => 190,
                'set_function' => "zen_cfg_select_option(array('_blank', '_self'), ",
            ],
            [
                'key' => 'SCF_CONTACT_LINK',
                'title' => 'Contact Icon Links To:',
                'value' => 'Contact Us page',
                'description' => 'There is nothing to type here: your store already knows its own Contact Us page and owner address. <strong>Contact Us page</strong> keeps visitors on your site and hides your address from address-harvesting robots, so it is the better choice. Set to <strong>Off</strong> for no contact icon.',
                'sort_order' => 195,
                'set_function' => "zen_cfg_select_option(array('Contact Us page', 'Store owner E-Mail address', 'Off'), ",
            ],

            /* ---- blog link (no signup -- it is only a link) ---- */
            [
                'key' => 'SCF_BLOG_URL',
                'title' => 'Blog Address:',
                'value' => '',
                'description' => '<strong>Keep it on your own site wherever you can.</strong> If your blog lives on this store, enter just the folder, e.g. <code>blog/</code>, or a Zen Cart page name such as <code>page_2</code> for an EZ-Page. A relative address survives a domain change, an http-to-https move and a copy from staging to live. Only use a full <code>https://</code> address when the blog genuinely lives elsewhere. Leave empty and no blog line is shown at all. The wording writes itself from your store name -- there is nothing else to fill in.',
                'sort_order' => 200,
                'set_function' => '',
            ],

            /* ---- newsletter form ---- */
            [
                'key' => 'SCF_SUBSCRIBE_STATUS',
                'title' => 'Show Newsletter Signup Form?',
                'value' => 'false',
                'description' => 'Show the newsletter signup form beneath the blog line. The heading is built from your store name, so there is no wording to enter.',
                'sort_order' => 300,
                'set_function' => $boolean,
            ],
            [
                'key' => 'SCF_SUBSCRIBE_ASK_FORMAT',
                'title' => 'Ask HTML Or TEXT-Only?',
                'value' => 'true',
                'description' => 'Show the <em>E-Mail Preference: HTML / TEXT-Only</em> radio buttons so each subscriber chooses how they want to be written to. <strong>Neither is pre-selected</strong>, and the Subscribe button does not appear until one is picked -- pre-selecting a mail format is not lawful in the United States. The wording matches your customers\' own account page, because this writes to the same preference. Set to <strong>false</strong> and the question is not asked at all; everyone is then recorded as <code>TEXT</code>, which is also Zen Cart\'s own default for a new account.',
                'sort_order' => 340,
                'set_function' => $boolean,
            ],
            [
                'key' => 'SCF_SUBSCRIBE_HONEYPOT',
                'title' => 'Enable Spam Trap?',
                'value' => 'true',
                'description' => 'Adds a hidden field plus a minimum-time check that automated submitters trip over. Genuine visitors never see it.',
                'sort_order' => 390,
                'set_function' => $boolean,
            ],
            [
                'key' => 'SCF_SUBSCRIBE_PRIVACY_LINK',
                'title' => 'Show Privacy Link?',
                'value' => 'true',
                'description' => 'Shows <em>How We Handle Your Details</em> under the form.',
                'sort_order' => 400,
                'set_function' => $boolean,
            ],
            [
                'key' => 'SCF_SUBSCRIBE_PRIVACY_URL',
                'title' => 'Privacy Link Destination:',
                'value' => '',
                'description' => 'Leave empty to link to Zen Cart\'s built-in <strong>Privacy Notice</strong> page, whose wording you edit under <em>Tools &rarr; Define Pages Editor</em> (choose <code>define_privacy</code>). To point somewhere else, prefer an on-site target: a Zen Cart page name such as <code>page_3</code>, or a path such as <code>policies/privacy/</code>. A full <code>https://</code> address should be a last resort.',
                'sort_order' => 410,
                'set_function' => '',
            ],
        ];

        /* ---- one field per network, asking only for the distinguishing part ---- */
        $networks = require dirname(__DIR__) . '/shared/networks.php';
        foreach ($networks as $slug => $network) {
            // Networks the plugin works out from the store's own settings have
            // no field at all -- that is the whole point of them.
            if (!empty($network['derived'])) {
                continue;
            }

            $title = trim($network['label'] . ' ' . $network['entry_label']) . ':';

            if (!empty($network['url_template'])) {
                $built = sprintf($network['url_template'], '<strong>' . $network['example'] . '</strong>');
                $description =
                    'Enter <em>only</em> your ' . strtolower($network['entry_label']) . ' -- not the whole address. '
                    . 'For example <code>' . $network['example'] . '</code> becomes '
                    . '<code>' . $built . '</code>. '
                    . 'Leave empty to hide the ' . $network['label'] . ' icon. '
                    . 'A complete <code>https://</code> address still works if your profile does not fit that pattern.';
            } elseif ($slug === 'mastodon') {
                $description =
                    'Enter your full Mastodon handle including the instance, for example '
                    . '<code>' . $network['example'] . '</code> -- Mastodon is federated, so the instance is part of '
                    . 'the address. Leave empty to hide the Mastodon icon.';
            } else {
                $description =
                    '<strong>Keep it on your own site wherever you can.</strong> Your feed almost certainly lives on '
                    . 'this store, so a path such as <code>' . $network['example'] . '</code> is better than a full '
                    . 'address: it survives a domain change and an http-to-https move. Leave empty to hide the '
                    . $network['label'] . ' icon.';
            }

            $keys[] = [
                'key' => 'SCF_URL_' . strtoupper($slug),
                'title' => $title,
                'value' => '',
                'description' => $description,
                'sort_order' => 1000 + (int)$network['sort'],
                'set_function' => '',
            ];
        }

        return $keys;
    }
}
