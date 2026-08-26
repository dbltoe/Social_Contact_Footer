<#
    Run every harness against every PHP version that can be found.

    The plugin claims PHP 7.4 through 8.5 from a single codebase, and that claim
    is only worth making if it is checked -- the syntax that breaks is rarely the
    syntax you are looking at. Nothing here needs a web server or a database.

    PHP builds are looked for, in order:

      -PhpDir <path>        a directory holding php74\, php80\, ... php85\
      $env:SCF_PHP_DIR      the same, set once in your environment
      tests\php\            beside this script (gitignored)
      php on PATH           always tried, last, and labelled with its version

    Windows builds come from https://windows.php.net/downloads/releases/archives/
    -- the NTS zips need no installation, just unzipping into php74\ and so on.

    A harness is green only when it prints its own "PASS:" line. Exit code alone
    is not enough: a die() -- the direct-access guard firing, say -- exits 0 and
    would otherwise read as a pass while testing nothing.

    Exits non-zero if anything failed, so it can gate a release.
#>

[CmdletBinding()]
param(
    [string] $PhpDir = $env:SCF_PHP_DIR,
    # Run one harness rather than all of them, e.g. -Only storefront
    [string] $Only = ''
)

$here = $PSScriptRoot

$searchRoots = @($PhpDir, (Join-Path $here 'php')) | Where-Object { $_ -and (Test-Path $_) }

$versions = [ordered]@{}
foreach ($v in '7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5') {
    foreach ($root in $searchRoots) {
        $exe = Join-Path $root ("php" + $v.Replace('.', '') + "\php.exe")
        if (Test-Path $exe) { $versions[$v] = $exe; break }
    }
}

# Whatever is on PATH, under its own version number, so a machine with no
# collection of builds still runs the suite against something.
$onPath = (Get-Command php -ErrorAction SilentlyContinue).Source
if ($onPath) {
    # Read from `php -v` rather than `php -r`: passing PHP source through
    # PowerShell to a native exe loses the inner quoting.
    $banner = (& $onPath -v 2>&1 | Select-Object -First 1)
    if ($banner -match 'PHP (\d+\.\d+)') {
        $pathVersion = $Matches[1]
        if (-not $versions.Contains($pathVersion)) { $versions[$pathVersion] = $onPath }
    }
}

if ($versions.Count -eq 0) {
    Write-Error "no PHP found. Put builds in tests\php\php74\ (and so on), set SCF_PHP_DIR, or put php on PATH."
    exit 2
}

$harnesses = @(Get-ChildItem $here -Filter '*_check.php' | Select-Object -ExpandProperty BaseName)
$harnesses += 'security_scan'
$harnesses = @($harnesses | Sort-Object -Unique)
if ($Only) { $harnesses = @($harnesses | Where-Object { $_ -like "*$Only*" }) }
if ($harnesses.Count -eq 0) { Write-Error "no harness matched '$Only'"; exit 2 }

# A harness that emits a warning has found something even when its assertions
# pass -- a deprecation on one PHP version is exactly what this suite is for.
$noise = '(?m)^(PHP )?(Warning|Deprecated|Notice|Fatal error|Parse error|Strict Standards):'
$problems = @()

foreach ($v in $versions.Keys) {
    $exe = $versions[$v]
    $line = "{0,-5} " -f $v
    foreach ($h in $harnesses) {
        $out = (& $exe -d error_reporting=E_ALL "$here\$h.php" 2>&1) -join "`n"
        $passed = ($LASTEXITCODE -eq 0) -and ($out -match '(?m)^PASS:') -and ($out -notmatch $noise)
        $line += if ($passed) { '.' } else { 'X' }
        if (-not $passed) {
            $detail = ($out -split "`n" |
                       Select-String -Pattern 'FAIL|ABORT|Warning|Deprecated|Fatal|Parse error' |
                       Select-Object -First 3) -join "`n"
            if (-not $detail) { $detail = "no PASS: line -- did it exit early?`n" + ($out -split "`n" | Select-Object -Last 3 | Out-String) }
            $problems += "=== PHP $v / $h ===`n$detail"
        }
    }
    $line
}

''
"order: $($harnesses -join ', ')"

$missing = @('7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5') | Where-Object { -not $versions.Contains($_) }
if ($missing) { "not checked: PHP $($missing -join ', ') -- no build found" }

if ($problems) {
    ''
    '--- PROBLEMS ---'
    $problems | Select-Object -First 6
    exit 1
}

''
"ALL GREEN ($($versions.Count) x $($harnesses.Count) runs)"
exit 0
