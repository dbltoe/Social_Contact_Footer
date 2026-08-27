<?php
/**
 * manifest.php: the Plugin Manager panel, the "Mod Not Turned On" name, and the
 * forum support link.
 *
 * SCF_STATUS is a configuration constant and a constant cannot be undefined, so
 * the three states run in three processes. The children run first and fold into
 * this one, keeping a single entry point.
 *
 * Run with: not-installed (default) | on | off
 */

require __DIR__ . '/_bootstrap.php';

define('DIR_WS_CATALOG', '/shop/');

$state = $argv[1] ?? 'not-installed';
if ($state === 'on') { define('SCF_STATUS', 'true'); }
if ($state === 'off') { define('SCF_STATUS', 'false'); }

$PLUGIN = scf_plugin_dir();
$version = basename($PLUGIN);
$manifest = require $PLUGIN . '/manifest.php';
$desc = $manifest['pluginDescription'];
$src = file_get_contents($PLUGIN . '/manifest.php');

section("manifest shape (state: $state)");
check('returns an array', is_array($manifest));
foreach (['pluginVersion', 'pluginName', 'pluginDescription', 'pluginAuthor',
          'pluginId', 'zcVersions', 'changelog', 'github_repo', 'pluginGroups'] as $key) {
    check("has $key", array_key_exists($key, $manifest));
}
check('declares every supported release',
    $manifest['zcVersions'] === ['v158', 'v200', 'v210', 'v220', 'v230', 'v300']);
check('changelog points at a file that exists', is_file($PLUGIN . '/' . $manifest['changelog']));

section('author attribution');
check('author is the agreed string', $manifest['pluginAuthor'] === 'My Zen Cart Host (dbltoe)');
/* plugin_control.author and plugin_control_versions.author are varchar(64). */
check('author fits the varchar(64) columns: ' . strlen($manifest['pluginAuthor']) . ' chars',
    strlen($manifest['pluginAuthor']) <= 64);

section('Read Me and GitHub buttons');
check('readme link honours DIR_WS_CATALOG',
    strpos($desc, 'href="/shop/zc_plugins/SocialContactFooter/' . $version . '/readme.html"') !== false);
/* pluginVersion is what Plugin Manager compares against the directory name to
 * decide an install is an upgrade; a mismatch makes the new version invisible. */
check('pluginVersion matches the directory: ' . $manifest['pluginVersion'],
    ltrim($manifest['pluginVersion'], 'v') === ltrim($version, 'v'));

section('the Plugins Library id');
/* Written to plugin_control.zc_contrib_id and passed to
 * isNewDownloadAvailable(). Zero means Plugin Manager never offers an update,
 * and on v1.5.8/v2.0/v2.1 the column is written only by the INSERT that
 * created the row -- so a zero that ships is frozen on every store that
 * installs it, and upgrading in place does not clear it. It shipped as 0 in
 * v1.0.0, which is exactly why this check exists. */
check('pluginId is set: ' . var_export($manifest['pluginId'], true),
    !empty($manifest['pluginId']));
check('pluginId is an integer, not a numeric string',
    is_int($manifest['pluginId']));
check('pluginId matches the plugin page: ' . $manifest['pluginId'],
    $manifest['pluginId'] === 2447);
/* This has already gone wrong once: the 1.0.0 uploaded to the Plugins Library
 * carried 2249, which is "Free Shipping Options Clone" -- so every store that
 * installed it has Plugin Manager asking the version server about an unrelated
 * shipping module, and on v1.5.8/v2.0/v2.1 that value is frozen in
 * zc_contrib_id. 860 is Scheduled Events, one paste away. */
foreach ([2249 => 'Free Shipping Options Clone', 860 => 'Scheduled Events'] as $id => $whose) {
    check("pluginId is not $id ($whose)", $manifest['pluginId'] !== $id);
}
check('GitHub link present',
    strpos($desc, 'href="https://github.com/dbltoe/Social_Contact_Footer"') !== false);
/* They must look like Plugin Manager's own Install / Uninstall / Disable. */
check('both are styled as core buttons',
    substr_count($desc, 'class="btn btn-primary" role="button"') === 2);
check('Read Me comes first',
    strpos($desc, '>Read Me<') !== false && strpos($desc, '>Read Me<') < strpos($desc, '>GitHub<'));
check('they share a single row (nothing block-level between them)',
    preg_match('~>Read Me</a>\s*<a~', $desc) === 1);
/* Spacing is inline so a theme's own .btn margin cannot change the result:
 * before = between = after. */
check('equal spacing before, between and after',
    strpos($desc, 'padding:0 0 0 6px') !== false
    && substr_count($desc, 'style="margin:0 6px 0 0"') === 2);
check('no <p> wrapper, whose 1em default would offset them from the core buttons',
    strpos($desc, '<p><a') === false);

section('the forum support link');
preg_match("~\\\$scfForumUrl = '([^']*)';~", $src, $forum);
check('the manifest has a single place for the thread URL', isset($forum[1]));
$forumUrl = $forum[1] ?? '';
if ($forumUrl === '') {
    check('nothing is rendered while no thread exists',
        strpos($desc, 'Forum Support Thread') === false);
} else {
    check('the link is rendered', strpos($desc, '>Forum Support Thread</a>') !== false);
    /* Deliberately not a third button: asking for help is a different kind of
     * act from Install or Uninstall and should not carry the same weight. */
    check('it is a plain link, not a third button',
        preg_match('~class="btn[^"]*"[^>]*>Forum Support Thread~', $desc) === 0);
    check('the URL is absolute and https', strpos($forumUrl, 'https://') === 0);
    check('the URL carries no quote that would break the attribute',
        strpos($forumUrl, '"') === false && strpos($forumUrl, "'") === false);
}
/* v1.5.8/v2.0/v2.1 never refresh plugin_control.description for an existing
 * row, so a URL added after release never reaches those stores. */
check('the manifest records why the URL must be set before the first release',
    stripos($src, 'BEFORE THE FIRST RELEASE') !== false);

section('outbound links');
$outbound = 2 + ($forumUrl !== '' ? 1 : 0);
check("all $outbound outbound links open in a new tab",
    substr_count($desc, 'target="_blank"') === $outbound);
check("all $outbound carry rel=noopener",
    substr_count($desc, 'rel="noopener noreferrer"') === $outbound);

section('the "Mod Not Turned On" name');
$base = 'Social Contact Footer';
$suffix = ' - Mod Not Turned On';
if ($state === 'off') {
    check('the plugin list name carries the notice', $manifest['pluginName'] === $base . $suffix);
} else {
    /* Not installed, or installed and live: the list already says so. */
    check('the name is clean when there is nothing to warn about',
        $manifest['pluginName'] === $base);
}
/* plugin_control.name is varchar(64) and both the v1.5.8 table_view and the
 * v2.2+ template echo it UNESCAPED. An overflow is truncated -- silently on a
 * normal server, fatally under STRICT mode -- and truncation mid-tag would put
 * broken markup into the plugin list. */
check('the name fits varchar(64): ' . strlen($manifest['pluginName']) . ' of 64',
    strlen($manifest['pluginName']) <= 64);
check('the name is plain text, so truncation could never break the markup',
    strpos($manifest['pluginName'], '<') === false && strpos($manifest['pluginName'], '&') === false);
check('at least 15 characters of headroom remain', strlen($manifest['pluginName']) <= 49);

section('nothing state-dependent in the description');
/* On v1.5.8/v2.0/v2.1 the description is written only by the INSERT that first
 * creates the row, so anything conditional here would freeze at whatever the
 * state happened to be on the very first scan and never clear. */
check('the description carries no state-dependent notice',
    strpos($desc, 'Mod Not Turned On') === false);
check('nor mentions the enable setting', strpos($desc, 'Enable Social Contact Footer?') === false);

section('the description survives the DB round trip');
check('fits a TEXT column: ' . strlen($desc) . ' bytes', strlen($desc) < 60000);
check('no stray backslash', strpos($desc, '\\') === false);
/* Echoed raw into the info box, so every tag must be balanced. Void elements are
 * self-closed and named entities resolved first -- both are valid HTML that an
 * XML parser rejects. */
$xml = preg_replace('~<(br|hr|img)([^>]*?)/?>~i', '<$1$2/>', $desc);
$xml = preg_replace_callback('~&(?!amp;|lt;|gt;|quot;|apos;|#)([a-z][a-z0-9]*);~i', static function ($m) {
    $d = html_entity_decode('&' . $m[1] . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return ($d === '&' . $m[1] . ';') ? $m[0] : $d;
}, $xml);
libxml_use_internal_errors(true);
$ok = simplexml_load_string('<div>' . $xml . '</div>') !== false;
libxml_clear_errors();
check('description tags are balanced', $ok);

if ($state === 'not-installed') {
    foreach (['on', 'off'] as $child) {
        $out = [];
        $status = 1;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $child . ' 2>&1', $out, $status);
        echo "\n" . implode("\n", $out) . "\n";
        if ($status !== 0) { $GLOBALS['scf_failures']++; }
    }
}

scf_done("manifest verified (state: $state)");
