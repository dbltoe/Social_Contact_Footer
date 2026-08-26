<?php
/**
 * The pre-release security scan, as a test rather than a habit.
 *
 * The Zen Cart Plugin Library runs its own scan on submission. MultiShip v3.0.0
 * was tagged, released and submitted before anyone looked, came back with three
 * flagged items, and cost a deleted release, a re-cut tag and a rebuilt package
 * -- after the reviewer had already downloaded the flagged copy. Every one was
 * findable with grep in seconds.
 *
 * So it runs with the rest of the suite, every time.
 *
 * A hit is a question, not a verdict. Where a pattern is legitimately present,
 * the exemption is written down here with its reason, so the next person reads
 * the reasoning instead of re-deriving it -- or deletes the exemption when the
 * reason stops being true.
 */

require __DIR__ . '/_bootstrap.php';

$PLUGIN = scf_plugin_dir();

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($PLUGIN, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isFile()) {
        $files[] = $f->getPathname();
    }
}
sort($files);

function rel($path)
{
    global $PLUGIN;
    return str_replace('\\', '/', substr($path, strlen($PLUGIN) + 1));
}

/** Every line matching $pattern, minus anything an exemption explains away. */
function scan(array $files, $pattern, array $exempt = [], $onlyExt = null)
{
    $hits = [];
    foreach ($files as $path) {
        if ($onlyExt !== null && !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $onlyExt, true)) {
            continue;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $i => $line) {
            if (preg_match($pattern, $line) !== 1) {
                continue;
            }
            foreach ($exempt as $ex) {
                if (preg_match($ex, $line) === 1) {
                    continue 2;
                }
            }
            $hits[] = rel($path) . ':' . ($i + 1) . '  ' . trim($line);
        }
    }
    return $hits;
}

function report($label, array $hits)
{
    check($label, empty($hits));
    foreach (array_slice($hits, 0, 8) as $h) {
        echo "          $h\n";
    }
    if (count($hits) > 8) {
        echo '          ... and ' . (count($hits) - 8) . " more\n";
    }
}

echo 'scanning ' . count($files) . " shipped files\n";

section('dynamic code and process execution');
/* Matched as calls, not substrings: "-apple-system" and "must-revalidate"
 * contain "system" and "eval" and are not what anyone means. */
report('no eval/exec/system/passthru/shell_exec/proc_open/create_function/assert',
    scan($files, '~(?<![a-z_$>])(eval|exec|system|passthru|shell_exec|proc_open|create_function|assert|popen|pcntl_fork)\s*\(~i'));

section('encoding and obfuscation');
report('no base64/gzinflate/str_rot13/hex2bin',
    scan($files, '~\b(base64_(en|de)code|gzinflate|gzuncompress|str_rot13|hex2bin)\s*\(~i'));

section('remote calls and deserialisation');
report('no unserialize/file_get_contents/curl/fsockopen in shipped code',
    scan($files, '~\b(unserialize|file_get_contents|curl_init|fsockopen|stream_socket_client)\s*\(~i'));

section('DOM injection');
report('no innerHTML/outerHTML/document.write/insertAdjacentHTML',
    scan($files, '~\b(innerHTML|outerHTML|document\.write|insertAdjacentHTML)\b~i'));

section('packed or minified third-party code');
$packed = [];
foreach ($files as $path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['js', 'css'], true)) {
        continue;
    }
    if (preg_match('~\.min\.|packed|vendor|jquery|bootstrap~i', basename($path)) === 1) {
        $packed[] = rel($path) . '  (name suggests third-party or minified)';
        continue;
    }
    // The other tell: one enormous line.
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $i => $line) {
        if (strlen($line) > 500) {
            $packed[] = rel($path) . ':' . ($i + 1) . '  line is ' . strlen($line) . ' chars';
            break;
        }
    }
}
report('every shipped script and stylesheet is readable source', $packed);

section('off-site assets');
/* Link destinations are the plugin's whole purpose; what must not appear is an
 * asset LOADED from another host. */
report('no script, stylesheet, image or iframe loads from off-site',
    scan($files, '~<(script|link|img|iframe|source|object|embed)\b[^>]*\b(src|href|data)\s*=\s*["\']?https?://~i'));

section('superglobals reaching SQL');
report('no $_GET/$_POST/$_REQUEST inside a query string',
    scan($files, '~(SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM|WHERE|VALUES)\b[^;]*\$_(GET|POST|REQUEST|COOKIE)~i', [], ['php']));

section('superglobals reaching the filesystem');
report('no $_GET/$_POST/$_FILES reaching include/require/readfile/unlink',
    scan($files, '~\b(include|include_once|require|require_once|readfile|file_get_contents|fopen|unlink|rename|copy)\s*\([^)]*\$_(GET|POST|REQUEST|FILES)~i', [], ['php']));

section('output escaping');
/* Anything echoed into the admin page or the storefront block must be escaped.
 * The exemptions below are each a deliberate decision. */
report('no unescaped variable echoed',
    scan($files, '~\becho\s+\$[a-z_]~i', [
        // Escaped, cast, or built entirely from our own markup.
        '~zen_output_string_protected|zen_output_string|htmlspecialchars~',
        '~echo\s+\$(html|vars|out|scfLinks|scfForumLink|scfNotice)\b~',
        // $messageStack->output() is core's own renderer and every admin page
        // calls it. What we PUT in it is the thing to police -- see the
        // "admin messages" section below.
        '~echo\s+\$messageStack->output\(\)~',
        // Integer, and used as a CSS class suffix.
        '~echo\s+\$status;~',
    ], ['php']));

section('admin messages');
/* $messageStack->output() is echoed as raw HTML by every Zen Cart admin page,
 * so anything user-derived placed into a message has to be escaped first.
 *
 * This is the check that found the real defect in the first scan: a stored
 * subscriber address went into sprintf(SCF_ADMIN_INVITE_SUCCESS, $email)
 * unescaped. It had passed zen_validate_email(), but RFC 5321 permits quoted
 * local parts, and "valid as an address" is not "safe as markup". */
$messageHits = scan($files, '~sprintf\(\s*SCF_ADMIN_[A-Z_]+\s*,[^)]*\$~', [
    // Values that cannot carry markup.
    '~\$(lineNumber|added|skipped|sent|count|size|minutes|hours|days|scfHeaderW|scfHeaderH|firstShown|lastShown|totalRows|subscriberCount)\b~',
    '~\(int\)~',
    '~SCF_IMPORT_MAX_ROWS|SCF_IMPORT_HEADING_EMAIL|implode\(~',
    // Already escaped at the point of use.
    '~zen_output_string_protected~',
    // The import problem list is escaped where it is printed, not where it is
    // built -- see the subscribers page, which wraps each entry.
    '~\$problems\[\]~',
    // A relative-time phrase built from our own language constants.
    '~SCF_ADMIN_ACCOUNT_INVITED_WHEN|SCF_ADMIN_TIME_~',
], ['php']);
report('every user-derived value in an admin message is escaped', $messageHits);

section('direct-access guards');
/* Core's own bundled plugins (DisplayLogs, POSM, PayPalRestful,
 * ScanAdditionalImages, SystemInspection) ship manifest.php, language files,
 * extra_datafiles and ScriptedInstaller.php with no guard, so those match the
 * convention and are exempt. Everything else that is only ever included must
 * carry one. */
$noGuard = [];
foreach ($files as $path) {
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'php') {
        continue;
    }
    $r = rel($path);
    $head = implode("\n", array_slice(file($path, FILE_IGNORE_NEW_LINES), 0, 60));
    $guarded = strpos($head, "defined('IS_ADMIN_FLAG')") !== false;

    // A top-level admin page requires application_top.php, which is what
    // DEFINES the constant -- so it must NOT carry the guard.
    if (strpos($head, 'application_top') !== false) {
        check("$r is a top-level page and correctly has no guard", !$guarded);
        continue;
    }
    if (preg_match('~^(manifest\.php|Installer/|.*languages/|.*extra_datafiles/)~', $r) === 1) {
        continue; // matches core convention
    }
    if (!$guarded) {
        $noGuard[] = $r;
    }
}
report('every included file that should be guarded is', $noGuard);

section('CSRF');
$tokens = scan($files, '~hash_equals~', [], ['php']);
check('both POST entry points compare a security token', count($tokens) >= 2);
foreach ($tokens as $t) { echo "          $t\n"; }

section('file uploads');
$upload = implode("\n", array_map('file_get_contents',
    array_filter($files, static function ($p) { return strpos($p, 'header_image') !== false || strpos($p, 'import') !== false; })));
check('every upload is checked with is_uploaded_file()', substr_count($upload, 'is_uploaded_file(') >= 2);
check('an image upload is verified by getimagesize(), not by its name',
    strpos($upload, 'getimagesize(') !== false);
check('the stored filename is built from a whitelisted extension',
    preg_match('~in_array\(\$extension, \$allowed, true\)~', $upload) === 1);
check('the submitted filename is never used as the stored name',
    preg_match('~\$target\s*=\s*\$dir\s*\.\s*.header\.~', $upload) === 1);

scf_done('security scan clean');
