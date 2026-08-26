<?php
/**
 * readme.html, the docs, and the packaged copy under dist/.
 *
 * readme.html is opened from the Plugin Manager panel, so it is served by the
 * web server out of zc_plugins/ and has to survive that .htaccess. It is also
 * the only documentation most store owners will ever read.
 */

require __DIR__ . '/_bootstrap.php';

$PLUGIN = scf_plugin_dir();              // .../zc_plugins/SocialContactFooter/v<version>
$ROOT = scf_repo_root();
$readme = file_get_contents($PLUGIN . '/readme.html');
/* Taken from the directory the plugin is actually sitting in, so a release
 * bump does not need this file edited -- and so a bump that renamed the
 * directory but forgot the manifest is caught rather than assumed away. */
$version = basename($PLUGIN);

section('readme.html stands alone');
/* zc_plugins/.htaccess denies everything and re-allows a fixed extension list.
 * html is on it -- css and js are too, but a stylesheet fetched from a plugin
 * directory still depends on the store not having tightened that file, and the
 * readme has to render on a store where it has. */
check('no external stylesheet', stripos($readme, '<link') === false || stripos($readme, 'rel="stylesheet"') === false);
check('no external script', preg_match('~<script[^>]+src=~i', $readme) === 0);
check('nothing is loaded from another host',
    preg_match('~(?:src|href)\s*=\s*["\']https?://~i', $readme, $m, PREG_OFFSET_CAPTURE) === 0
    || preg_match_all('~<a [^>]*href\s*=\s*["\']https?://~i', $readme) === preg_match_all('~(?:src|href)\s*=\s*["\']https?://~i', $readme));
check('no <img> at all, so nothing can 404 in the panel',
    preg_match('~<img~i', $readme) === 0);
check('it declares its charset', stripos($readme, 'charset="utf-8"') !== false || stripos($readme, 'charset=utf-8') !== false);
check('it declares a viewport, since admins read it on tablets',
    stripos($readme, 'name="viewport"') !== false);
check('it declares a language', preg_match('~<html[^>]+lang=~i', $readme) === 1);
check('it has a title', preg_match('~<title>[^<]+</title>~i', $readme) === 1);

section('the readme is navigable');
preg_match_all('~<h2 id="([^"]+)"~', $readme, $h2);
check('every h2 carries an id', count($h2[1]) >= 15);
check('the ids are unique', count($h2[1]) === count(array_unique($h2[1])));
preg_match_all('~href="#([^"]+)"~', $readme, $links);
$ids = [];
preg_match_all('~ id="([^"]+)"~', $readme, $allIds);
$ids = $allIds[1];
$broken = array_values(array_diff(array_unique($links[1]), $ids));
check('no in-page link points at a missing anchor' . ($broken ? ': ' . implode(', ', $broken) : ''),
    $broken === []);
check('there is a contents list', stripos($readme, 'href="#what"') !== false);

section('the subjects the store owner will look for');
$must = [
    'the double opt-in' => 'Double opt-in',
    'unsubscribing' => 'Unsubscribe',
    'the CSV import' => 'CSV',
    'the printable sign-up sheet' => 'sign-up',
    'the Newsletter Manager audiences' => 'Newsletter Manager',
    'inviting a subscriber to register' => 'Invite Registration',
    'uninstalling' => 'Uninstalling',
    'upgrading' => 'Upgrading',
    'troubleshooting' => 'Troubleshooting',
    'translating' => 'Translating',
];
foreach ($must as $label => $needle) {
    check("it covers $label", stripos($readme, $needle) !== false);
}

section('the email header image is explained as opt-in');
/* This was the one the store owner most needed spelled out: the plugin ships
 * NO image, and it must never be confused with the store's own email/header.jpg,
 * which every Zen Cart release installs and which other mail does use. */
check('it says plainly that there is no image unless one is added',
    preg_match('~no (?:header )?image[^.]*unless~i', $readme) === 1
    || preg_match('~unless you (?:add|upload)~i', $readme) === 1);
check('it gives the usual dimensions', strpos($readme, '550') !== false && strpos($readme, '110') !== false);
foreach (['jpg', 'png', 'gif'] as $ext) {
    check("it names .$ext as an accepted extension", stripos($readme, $ext) !== false);
}
check("it mentions the store's own email/header.jpg, which other mail may still use",
    stripos($readme, 'header.jpg') !== false);
check('and it makes clear this plugin does not touch that file',
    preg_match('~header\.jpg~i', $readme) === 1
    || preg_match('~(?:does not|never|without) (?:touch|replace|overwrit|affect)~i', $readme) === 1);

section('American spelling throughout');
/* Corrected once already on the Configuration page. These are the spellings a
 * British keyboard reaches for; the check covers the shipped text, not the
 * British-spelled words that are legitimately part of a URL or a name. */
$textFiles = [
    'readme.html' => $readme,
    'catalog language file' => file_get_contents($PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php'),
    'admin language file' => file_get_contents($PLUGIN . '/admin/includes/languages/english/lang.social_contact_footer_subscribers.php'),
    'admin names file' => file_get_contents($PLUGIN . '/admin/includes/languages/english/extra_definitions/lang.social_contact_footer_names.php'),
    'installer' => file_get_contents($PLUGIN . '/Installer/ScriptedInstaller.php'),
    'manifest' => file_get_contents($PLUGIN . '/manifest.php'),
];
$british = ['colour', 'centre', 'organis', 'customis', 'personalis', 'recognis',
    'unrecognis', 'behaviour', 'licence', 'catalogue', 'grey'];
/* The docs are read as often as the readme, and the same hand wrote them. */
foreach (['README.md', 'CHANGELOG.md', 'docs/INSTALL.md', 'docs/CONFIGURATION.md',
          'docs/CUSTOMIZING.md', 'docs/COMPATIBILITY.md'] as $doc) {
    $textFiles[$doc] = file_get_contents($ROOT . '/' . $doc);
}
foreach ($textFiles as $label => $body) {
    $hits = [];
    foreach ($british as $word) {
        if (preg_match('~\b' . $word . '~i', $body)) { $hits[] = $word; }
    }
    check("$label uses American spelling" . ($hits ? ' -- found ' . implode(', ', $hits) : ''), $hits === []);
}

section('E-Mail, the way Zen Cart spells it');
/* Core writes "E-Mail" in its own admin labels, and matching it keeps the
 * plugin's pages from reading like a bolt-on. This is about LABELS: a
 * capitalized "Email" is one, whereas lowercase "email" as a common noun
 * ("most email headers are 550 wide") is ordinary prose and core writes that
 * too. Only the capitalized form is a finding.
 *
 * `Email` is also Zen Cart's own mail class, so code comments naming it are
 * exempt -- and the readme is stripped of its code samples first, since the
 * column heading the CSV import wants really is spelled `email`. */
$labelFiles = [
    'readme.html' => $readme,
    'admin language file' => $textFiles['admin language file'],
    'catalog language file' => $textFiles['catalog language file'],
    'admin names file' => $textFiles['admin names file'],
    'networks list' => file_get_contents($PLUGIN . '/shared/networks.php'),
    'email README' => file_get_contents($PLUGIN . '/email/README.txt'),
];
foreach ($labelFiles as $label => $body) {
    $prose = preg_replace('~<code>.*?</code>|<pre>.*?</pre>~s', '', $body);
    $prose = strip_tags($prose);
    $prose = preg_replace('~SCF_[A-Z_]+~', '', $prose);
    $prose = preg_replace('~[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+~', '', $prose);
    /* `Email::` and "the Email class" -- Zen Cart's own name for it. */
    $prose = preg_replace('~\bEmail(?:::|\s+(?:class|template variables))~', '', $prose);
    $hits = [];
    if (preg_match_all('~.{0,40}\bEmail\b.{0,20}~', $prose, $m)) {
        $hits = $m[0];
    }
    check("$label writes E-Mail, not Email" . ($hits ? ' -- ' . trim($hits[0]) : ''), $hits === []);
}

section('the documentation set');
foreach (['README.md', 'CHANGELOG.md', 'LICENSE', 'docs/INSTALL.md', 'docs/CONFIGURATION.md',
          'docs/CUSTOMIZING.md', 'docs/COMPATIBILITY.md'] as $doc) {
    check("$doc is present and not empty", is_file($ROOT . '/' . $doc) && filesize($ROOT . '/' . $doc) > 500);
}
check('changelog.txt ships inside the plugin, where the manifest points',
    is_file($PLUGIN . '/changelog.txt'));
$compat = file_get_contents($ROOT . '/docs/COMPATIBILITY.md');
check('COMPATIBILITY.md records the upsert divergence that shapes the notice',
    stripos($compat, 'upsert') !== false);
check('and the PR 7953 finding', strpos($compat, '7953') !== false);
$changelog = file_get_contents($ROOT . '/CHANGELOG.md');
$bare = ltrim($version, 'v');
check("CHANGELOG.md has an entry for $bare", strpos($changelog, "## [$bare]") !== false);
check("and a link target for it", strpos($changelog, "[$bare]: https://") !== false);
/* The two changelogs are written separately -- one for GitHub, one shipped
 * inside the plugin -- so it is easy to update one and forget the other. */
check("the shipped changelog.txt also names $version",
    preg_match('~^' . preg_quote($version, '~') . '\s*$~m',
        file_get_contents($PLUGIN . '/changelog.txt')) === 1);
check("$version is the newest entry in CHANGELOG.md",
    preg_match('~## \[([0-9.]+)\]~', $changelog, $first) === 1 && $first[1] === $bare);

section('one version number, everywhere');
$manifestSrc = file_get_contents($PLUGIN . '/manifest.php');
preg_match("~'pluginVersion' => '([^']+)'~", $manifestSrc, $pv);
/* Plugin Manager accepts the version with or without the leading v, and both
 * are in use in core plugins -- what matters is that it names the directory it
 * is sitting in, since that is what upgrade detection compares. */
check('the manifest version matches the directory it sits in: ' . ($pv[1] ?? '?'),
    isset($pv[1]) && ltrim($pv[1], 'v') === ltrim($version, 'v'));
/* The readme href is assembled from DIR_WS_CATALOG at run time, so it is the
 * rendered description that has to carry the right path, not the source. */
define('DIR_WS_CATALOG', '/shop/');
$rendered = (require $PLUGIN . '/manifest.php')['pluginDescription'];
check('the readme link in the manifest points at this version directory',
    strpos($rendered, 'zc_plugins/SocialContactFooter/' . $version . '/readme.html') !== false);
/* Three of these are hard-coded and every one has to be found and changed at
 * the next release, or the links point into a directory that no longer exists. */
$hard = [];
foreach ([$PLUGIN . '/manifest.php', $PLUGIN . '/admin/social_contact_footer_subscribers.php'] as $file) {
    $n = preg_match_all('~SocialContactFooter/v[0-9.]+~', file_get_contents($file));
    if ($n) { $hard[] = basename($file) . " x$n"; }
}
check('the hard-coded version paths are still only where they are known to be: '
    . implode(', ', $hard),
    $hard === ['manifest.php x1', 'social_contact_footer_subscribers.php x2']);
/* Every one of them has to name the CURRENT directory. A bump that renames the
 * directory and misses one of these leaves a link into a directory that no
 * longer exists -- a 404 readme, or a missing sign-up sheet. */
$stalePaths = [];
foreach ([$PLUGIN . '/manifest.php', $PLUGIN . '/admin/social_contact_footer_subscribers.php'] as $file) {
    if (preg_match_all('~SocialContactFooter/(v[0-9.]+)~', file_get_contents($file), $m)) {
        foreach (array_unique($m[1]) as $seen) {
            if ($seen !== $version) { $stalePaths[] = basename($file) . " -> $seen"; }
        }
    }
}
check("all of them name $version" . ($stalePaths ? ': ' . implode(', ', $stalePaths) : ''),
    $stalePaths === []);
/* And the readme the store owner reads has to agree about what they installed. */
check("readme.html states version " . $bare,
    strpos($readme, 'Version ' . $bare . ' ') !== false);
/* No stray directory from the previous release left beside the new one -- it
 * would be uploaded alongside, and Plugin Manager would list both. */
check('only one version directory exists under zc_plugins/SocialContactFooter',
    count(glob(dirname($PLUGIN) . '/v*', GLOB_ONLYDIR)) === 1);

section('the packaged copy under dist/ matches the source');
/* dist/ is what becomes the release zip. It is a copy, so it goes stale the
 * moment a source file is edited and the package is not rebuilt -- and a stale
 * package means the release people download is not the code that was reviewed. */
$dist = $ROOT . '/dist/Social_Contact_Footer_' . $version;
check('dist/ exists', is_dir($dist));
if (is_dir($dist)) {
    $stale = [];
    $missing = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dist, FilesystemIterator::SKIP_DOTS));
    $count = 0;
    foreach ($it as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dist) + 1));
        /* readme.html is deliberately lifted to the package root as well, so it
         * can be opened straight out of the download without hunting through
         * four levels of zc_plugins. */
        $source = ($rel === 'readme.html') ? $PLUGIN . '/readme.html' : $ROOT . '/' . $rel;
        $count++;
        if (!is_file($source)) { $missing[] = $rel; continue; }
        if (md5_file($source) !== md5_file($file->getPathname())) { $stale[] = $rel; }
    }
    check("dist/ holds files: $count", $count > 20);
    check('every packaged file still exists in the source' . ($missing ? ': ' . implode(', ', $missing) : ''),
        $missing === []);
    check('no packaged file is stale' . ($stale ? ': ' . implode(', ', $stale) : ''), $stale === []);

    /* And the other direction: a file added to the source after the package was
     * built is simply absent from the download, which is harder to notice. */
    $shipped = [];
    $srcIt = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ROOT . '/zc_plugins', FilesystemIterator::SKIP_DOTS));
    foreach ($srcIt as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($ROOT) + 1));
        if (!is_file($dist . '/' . $rel)) { $shipped[] = $rel; }
    }
    check('no plugin file was left out of the package' . ($shipped ? ': ' . implode(', ', $shipped) : ''),
        $shipped === []);

    /* The harnesses and the build script are for whoever maintains this, not
     * for the store owner: they name PHP builds that will not exist on their
     * machine and would only raise questions in a support thread. */
    foreach (['tests', 'tools', '.git', '.github', '.gitignore', '.gitattributes',
              '.editorconfig', 'dist'] as $keepOut) {
        check("$keepOut is not in the package", !file_exists($dist . '/' . $keepOut));
    }
}

section('the release zip matches dist/');
/* Read the central directory by hand rather than through ZipArchive: the
 * extension is not built into every PHP in the matrix, and this only needs each
 * entry's name and stored CRC-32, both of which sit in fixed positions. */
function scf_zip_entries($path)
{
    $raw = file_get_contents($path);
    $eocd = strrpos($raw, "PK\x05\x06");
    if ($eocd === false) { return null; }
    $end = unpack('vdisk/vcddisk/vhere/vtotal/Vsize/Voffset', substr($raw, $eocd + 4, 18));
    $at = $end['offset'];
    $entries = [];
    for ($i = 0; $i < $end['total']; $i++) {
        if (substr($raw, $at, 4) !== "PK\x01\x02") { return null; }
        $namelen = unpack('v', substr($raw, $at + 28, 2))[1];
        $extralen = unpack('v', substr($raw, $at + 30, 2))[1];
        $commentlen = unpack('v', substr($raw, $at + 32, 2))[1];
        $crc = unpack('V', substr($raw, $at + 16, 4))[1];
        $name = substr($raw, $at + 46, $namelen);
        $entries[] = ['name' => str_replace('\\', '/', $name), 'crc' => sprintf('%u', $crc)];
        $at += 46 + $namelen + $extralen + $commentlen;
    }
    return $entries;
}

$zip = dirname($ROOT) . '/Social_Contact_Footer_' . $version . '.zip';
check('the release zip is where it is expected', is_file($zip));
if (is_file($zip)) {
    $entries = scf_zip_entries($zip);
    check('the zip central directory is readable', is_array($entries));
    if (is_array($entries)) {
        $bad = [];
        $seen = 0;
        foreach ($entries as $e) {
            if (substr($e['name'], -1) === '/') { continue; }
            $seen++;
            $rel = preg_replace('~^Social_Contact_Footer_' . preg_quote($version, '~') . '/~', '', $e['name']);
            $onDisk = $dist . '/' . $rel;
            if (!is_file($onDisk)) { $bad[] = "$rel (not in dist)"; continue; }
            if (sprintf('%u', crc32(file_get_contents($onDisk))) !== $e['crc']) { $bad[] = $rel; }
        }
        check("the zip holds every packaged file: $seen", $seen > 20);
        check('every entry matches the file on disk'
            . ($bad ? ': ' . implode(', ', array_slice($bad, 0, 6)) : ''), $bad === []);
        /* The PDF is the one entry a text-mode packer would silently corrupt --
         * it has an uncompressed stream and no NUL bytes to give it away. */
        $pdf = null;
        foreach ($entries as $e) {
            if (substr($e['name'], -4) === '.pdf') { $pdf = $e; }
        }
        check('the sign-up sheet is in the zip', $pdf !== null);
        check('and survived packing byte for byte',
            $pdf !== null
            && sprintf('%u', crc32(file_get_contents($PLUGIN . '/pdf/newsletter_signup_form.pdf'))) === $pdf['crc']);
    }
}

scf_done('documentation and packaging verified');
