<?php
/**
 * Social Contact Footer -- admin "Footer Newsletter Subscribers" page.
 *
 * Reached as `admin/index.php?cmd=social_contact_footer_subscribers`, the
 * routing Zen Cart has used for plugin admin pages since v1.5.7. Access is
 * governed by the `toolsSocialContactFooterSubscribers` record the installer
 * writes into `admin_pages`, checked centrally during admin bootstrap.
 *
 * All state-changing operations are POSTed with an `action` parameter, so
 * Zen Cart's global admin CSRF check in `init_sessions.php` applies to them;
 * the token is re-checked here as well.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

require 'includes/application_top.php';

$scfTable = TABLE_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS;
$scfPerPage = 50;

$scfStatusLabels = [
    0 => SCF_ADMIN_STATUS_PENDING,
    1 => SCF_ADMIN_STATUS_SUBSCRIBED,
    2 => SCF_ADMIN_STATUS_UNSUBSCRIBED,
];

/**
 * Build the query-string that preserves the current filter/page across links.
 */
function scfAdminQueryString(array $overrides = [])
{
    $params = [
        'status' => isset($_GET['status']) ? (string)$_GET['status'] : '',
        'search' => isset($_GET['search']) ? (string)$_GET['search'] : '',
        'page' => isset($_GET['page']) ? (string)(int)$_GET['page'] : '',
    ];
    $params = array_merge($params, $overrides);

    $pairs = [];
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $pairs[] = urlencode($key) . '=' . urlencode((string)$value);
        }
    }

    return implode('&', $pairs);
}

/**
 * The blank sign-up sheet, served through this page.
 *
 * NOT linked directly. Zen Cart's shipped `zc_plugins/.htaccess` denies
 * everything and then re-allows a specific list -- js, css, html, images,
 * fonts, xml -- which does not include `pdf`. A direct link would therefore
 * 403 on any normal Apache host, so the file is streamed from here instead,
 * where the admin session has already been authenticated.
 *
 * A plain GET: it reads a file that ships with the plugin and changes nothing,
 * so there is no state to protect with a token.
 */
if (isset($_GET['scf_form'])) {
    $scfFormPath = DIR_FS_CATALOG . 'zc_plugins/SocialContactFooter/v1.0.0/pdf/newsletter_signup_form.pdf';

    if (is_file($scfFormPath) && is_readable($scfFormPath)) {
        header('Content-Type: application/pdf');
        // inline: the store owner wants to look at it and press print, not
        // find it in their downloads folder afterwards.
        header('Content-Disposition: inline; filename="newsletter_signup_form.pdf"');
        header('Content-Length: ' . filesize($scfFormPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($scfFormPath);
        exit;
    }

    $messageStack->add_session(SCF_ADMIN_IMPORT_ERROR_NO_FORM, 'error');
    zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'SSL'));
}

/**
 * An example import file, generated rather than stored.
 *
 * Same reasoning as the sign-up sheet: a plain GET that reads nothing and
 * changes nothing, behind the admin login. Generating it means the headings can
 * never disagree with the ones the importer matches on.
 */
if (isset($_GET['scf_sample'])) {
    header('Content-Type: text/csv; charset=' . (defined('CHARSET') ? CHARSET : 'utf-8'));
    header('Content-Disposition: attachment; filename="' . SCF_ADMIN_SAMPLE_FILENAME . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    foreach (scf_admin_import_sample_rows() as $sampleRow) {
        // $escape stated for the same reason as everywhere else: PHP 8.4
        // deprecates leaving it implicit and PHP 9 changes its default.
        fputcsv($out, $sampleRow, ',', '"', '');
    }
    fclose($out);
    exit;
}

// -----
// Guard against the table having been dropped out from under us.
//
$scfTableExists = !$db->Execute("SHOW TABLES LIKE '" . $db->prepare_input($scfTable) . "'")->EOF;

// -----
// Action processing. Everything here arrives by POST.
//
$action = (isset($_POST['action'])) ? (string)$_POST['action'] : '';

if ($action !== '' && $scfTableExists) {
    // Belt and braces: init_sessions.php already rejected a bad token.
    $postedToken = isset($_POST['securityToken']) ? (string)$_POST['securityToken'] : '';
    if (empty($_SESSION['securityToken']) || !hash_equals((string)$_SESSION['securityToken'], $postedToken)) {
        zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'SSL'));
    }

    $subscriberId = isset($_POST['sid']) ? (int)$_POST['sid'] : 0;

    switch ($action) {
        case 'export':
            // subscriber_name is not selected or exported: nothing collects a
            // name any more, so the column would be empty for every row a
            // current version created.
            $result = $db->Execute(
                "SELECT subscriber_email, email_format, status,
                        confirm_token, date_added, date_confirmed, date_unsubscribed
                   FROM $scfTable
                  ORDER BY subscriber_email"
            );

            // Each subscriber's personal opt-out address, ready to be merged
            // into a mailing. See docs/CONFIGURATION.md.
            $catalogBase = (ENABLE_SSL_CATALOG === 'true')
                ? HTTPS_CATALOG_SERVER . DIR_WS_HTTPS_CATALOG
                : HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
            $unsubscribeBase = $catalogBase . 'index.php?main_page=index&scf_unsubscribe=';

            $filename = SCF_ADMIN_EXPORT_FILENAME . '-' . date('Ymd-His') . '.csv';
            header('Content-Type: text/csv; charset=' . (defined('CHARSET') ? CHARSET : 'utf-8'));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');

            $out = fopen('php://output', 'w');
            // $escape is stated explicitly on both calls: PHP 8.4 deprecates
            // leaving it implicit, PHP 9 changes its default, and '' is the
            // correct value for real CSV -- a backslash carries no meaning in
            // the format. Stating it keeps output identical on 7.4 to 8.5.
            fputcsv($out, ['email', 'format', 'status', 'signed_up', 'confirmed', 'unsubscribed', 'unsubscribe_url'], ',', '"', '');
            while (!$result->EOF) {
                $row = $result->fields;
                $statusIndex = (int)$row['status'];
                $statusWords = ['pending', 'subscribed', 'unsubscribed'];
                $statusWord = isset($statusWords[$statusIndex]) ? $statusWords[$statusIndex] : 'unknown';
                fputcsv($out, [
                    $row['subscriber_email'],
                    $row['email_format'],
                    $statusWord,
                    $row['date_added'],
                    $row['date_confirmed'],
                    $row['date_unsubscribed'],
                    $unsubscribeBase . $row['confirm_token'],
                ], ',', '"', '');
                $result->MoveNext();
            }
            fclose($out);

            zen_record_admin_activity('Exported Social Contact Footer subscribers.', 'info');
            exit;

        case 'set_subscribed':
        case 'set_unsubscribed':
            if ($subscriberId > 0) {
                $newStatus = ($action === 'set_subscribed') ? 1 : 2;
                $dateColumn = ($newStatus === 1) ? 'date_confirmed' : 'date_unsubscribed';

                $db->Execute(
                    "UPDATE $scfTable
                        SET status = $newStatus,
                            $dateColumn = now(),
                            last_modified = now()
                      WHERE subscriber_id = $subscriberId
                      LIMIT 1"
                );

                if ($db->affectedRows() > 0) {
                    $messageStack->add_session(SCF_ADMIN_SUCCESS_UPDATED, 'success');
                    zen_record_admin_activity(
                        'Social Contact Footer: subscriber ' . $subscriberId . ' set to status ' . $newStatus . '.',
                        'warning'
                    );
                } else {
                    $messageStack->add_session(SCF_ADMIN_ERROR_NOT_FOUND, 'error');
                }
            }
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;

        case 'invite':
            if ($subscriberId > 0) {
                $outcome = scf_admin_send_invite($subscriberId);
                $messageStack->add_session($outcome['message'], $outcome['ok'] ? 'success' : 'error');
            }
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;

        case 'import':
            // The upload arrives in $_FILES, not $_POST, but the CSRF token
            // above still guards it: the form posts `action` like every other
            // one on this page.
            $outcome = scf_admin_import_csv(isset($_FILES['scf_csv']) ? $_FILES['scf_csv'] : []);
            $messageStack->add_session($outcome['message'], $outcome['ok'] ? 'success' : 'error');
            if (!empty($outcome['problems'])) {
                // Keep them for one redirect so the owner can see which rows
                // were passed over and why.
                $_SESSION['scf_import_problems'] = array_slice($outcome['problems'], 0, 50);
            }
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;

        case 'reinvite':
            if ($subscriberId > 0) {
                $outcome = scf_admin_resend_invite($subscriberId);
                $messageStack->add_session($outcome['message'], $outcome['ok'] ? 'success' : 'error');
            }
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;

        case 'invite_all':
            // Deliberately bounded. Each invitation creates an account and
            // sends mail, so an unbounded loop over a large list would stall
            // the request and hammer the mail queue. The button can simply be
            // pressed again for the next batch.
            $batchLimit = 50;
            $sent = 0;
            $skipped = 0;

            $candidates = $db->Execute(
                "SELECT * FROM $scfTable
                  WHERE status = 1
                    AND invite_accepted IS NULL
                  ORDER BY date_confirmed, subscriber_id"
            );
            while (!$candidates->EOF && $sent < $batchLimit) {
                if (scf_admin_invite_eligible($candidates->fields)) {
                    $outcome = scf_admin_send_invite((int)$candidates->fields['subscriber_id']);
                    if ($outcome['ok']) {
                        $sent++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
                $candidates->MoveNext();
            }

            $messageStack->add_session(
                ($sent === 0 && $skipped === 0)
                    ? SCF_ADMIN_INVITE_NONE_ELIGIBLE
                    : sprintf(SCF_ADMIN_INVITE_BULK_SUCCESS, $sent, $skipped),
                ($sent > 0) ? 'success' : 'warning'
            );
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;

        case 'delete':
            if ($subscriberId > 0) {
                $db->Execute("DELETE FROM $scfTable WHERE subscriber_id = $subscriberId LIMIT 1");
                $messageStack->add_session(SCF_ADMIN_SUCCESS_DELETED, 'success');
                zen_record_admin_activity(
                    'Social Contact Footer: deleted subscriber ' . $subscriberId . '.',
                    'warning'
                );
            }
            zen_redirect(zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(), 'SSL'));
            break;
    }
}

// -----
// Listing.
//
$filterStatus = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$filterSearch = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$where = [];
if ($filterStatus !== null && in_array($filterStatus, [0, 1, 2], true)) {
    $where[] = 'status = ' . $filterStatus;
}
if ($filterSearch !== '') {
    $where[] = "subscriber_email LIKE '%" . $db->prepare_input($filterSearch) . "%'";
}
$whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);

$totalRows = 0;
$rows = [];

if ($scfTableExists) {
    $countResult = $db->Execute("SELECT COUNT(*) AS total FROM $scfTable$whereClause");
    $totalRows = (int)$countResult->fields['total'];

    $totalPages = max(1, (int)ceil($totalRows / $scfPerPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $scfPerPage;

    $result = $db->Execute(
        "SELECT * FROM $scfTable$whereClause
          ORDER BY date_added DESC, subscriber_id DESC
          LIMIT $offset, $scfPerPage"
    );
    while (!$result->EOF) {
        $rows[] = $result->fields;
        $result->MoveNext();
    }
} else {
    $totalPages = 1;
    $messageStack->add(SCF_ADMIN_ERROR_NO_TABLE, 'error');
}

$firstShown = ($totalRows === 0) ? 0 : (($currentPage - 1) * $scfPerPage) + 1;
$lastShown = ($totalRows === 0) ? 0 : $firstShown + count($rows) - 1;
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
<style>
/* Nothing on this page is smaller than 1.2rem.
 *
 * Admin themes commonly set 12-13px, which is a squint for anyone reading a
 * long list of addresses. Everything below is scoped to .scf-admin-page so the
 * rest of the admin is untouched.
 *
 * Form controls need it stated explicitly: browsers do NOT inherit font-size
 * into <input>, <select>, <textarea> or <button>, so a rule on the container
 * alone would leave the filter box and every button at the browser default. */
.scf-admin-page{font-size:1.2rem;line-height:1.5}
.scf-admin-page h1{font-size:2rem}
.scf-admin-page p,
.scf-admin-page label,
.scf-admin-page td,
.scf-admin-page th,
.scf-admin-page a{font-size:1.2rem}
.scf-admin-page input,
.scf-admin-page select,
.scf-admin-page textarea,
.scf-admin-page button,
.scf-admin-page .btn{font-size:1.2rem;line-height:1.4}
/* The row action buttons are the one place a smaller size is conventional --
 * btn-xs -- so they are pulled back up to the same floor rather than left at
 * whatever the theme's extra-small happens to be. */
.scf-admin-page .btn-xs{font-size:1.2rem;padding:.25em .6em}
/* Bootstrap sets `code` to 90%, which inside a 1.2rem container lands under
   the floor -- and on exactly the strings that get read character by
   character. Size only; the familiar styling is left alone. */
.scf-admin-page code,.scf-admin-page kbd,
.scf-admin-page pre,.scf-admin-page samp{font-size:1.2rem}
.scf-admin-table{width:100%;border-collapse:collapse}
.scf-admin-table th,.scf-admin-table td{padding:.5em .7em;border-bottom:1px solid #ddd;text-align:left;vertical-align:top}
.scf-admin-table th{white-space:nowrap}
.scf-admin-filters{margin:1em 0;display:flex;flex-wrap:wrap;gap:.6em;align-items:flex-end}
.scf-admin-filters form{display:flex;flex-wrap:wrap;gap:.6em;align-items:flex-end;margin:0}
.scf-admin-rowform{display:inline;margin:0}
.scf-admin-pager{margin:1em 0;display:flex;gap:1em;align-items:center}
/* Status colours, all measured rather than eyeballed -- against white, and
 * against the light zebra stripe some admin themes paint behind table rows,
 * because the theme decides which one is actually behind the text:
 *
 *                            white     #f5f5f5
 *   #856404  awaiting        5.49:1    5.04:1
 *   #2e7d32  subscribed      5.13:1    4.70:1
 *   #b03a37  unsubscribed    5.99:1    5.49:1
 *
 * Unsubscribed is red, matching the Delete button, because it is the status
 * that must not be misread: it means do not mail this person.
 *
 * It is the button's border-and-hover shade rather than its face colour, and
 * that is not a compromise -- it is the only way to actually match. #CE4844
 * works on the button because the WHITE text does the contrasting, at 4.53:1.
 * Turned around as text on a near-white row it has almost nothing to contrast
 * against: 4.53:1 on pure white, and 4.16:1 on a striped one, which fails.
 * #b03a37 is the same red to the eye, sits on the same button, and holds
 * 5.49:1 even on a stripe.
 *
 * The other two were measured the same way. Unsubscribed began at #999 --
 * 2.85:1, unreadable for anyone with reduced contrast sensitivity. Awaiting
 * confirmation had the same fault less obviously: #8a6d3b passed on white at
 * 4.85:1 and failed on a stripe at 4.45:1. */
.scf-admin-page .scf-admin-status-0{color:#856404}
.scf-admin-page .scf-admin-status-1{color:#2e7d32}
.scf-admin-page .scf-admin-status-2{color:#b03a37}
/* Bootstrap's .btn-danger is white on #d9534f -- 3.96:1, short of the 4.5:1
 * needed for normal text. #CE4844 reaches 4.53:1 with the same white text.
 * Hover, focus and active are pinned too: leaving them to Bootstrap would let
 * the button fail again the moment a pointer touched it. */
.scf-admin-page .btn-danger{background-color:#CE4844;border-color:#b03a37;color:#fff}
.scf-admin-page .btn-danger:hover,
.scf-admin-page .btn-danger:focus,
.scf-admin-page .btn-danger:active,
.scf-admin-page .btn-danger.active{background-color:#b03a37;border-color:#a33a37;color:#fff}
.scf-admin-busy{opacity:.6;cursor:progress}
/* Announced, not shown: the table already carries a visible summary above it,
 * so a second visible caption would be noise -- but a table with no caption is
 * one a screen-reader user meets with no idea what it holds. Clipped rather
 * than display:none, which would remove it from the accessibility tree too. */
.scf-visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;
  overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);white-space:nowrap;border:0}
.scf-admin-note{margin:.5em 0 1em}
/* CSV import, kept visually apart from the filters so it cannot be pressed by
 * accident while filtering. */
.scf-admin-import{margin:1.5em 0;padding:1em 1.2em;border:1px solid #ddd;border-radius:4px}
.scf-admin-import h2{font-size:1.5rem;margin:0 0 .4em}
.scf-admin-import form{display:flex;flex-wrap:wrap;gap:.8em;align-items:flex-end;margin:0}
.scf-admin-import .scf-admin-hint{font-size:1.2rem;color:#555;margin:.6em 0 0}
</style>
</head>
<body>
<!-- header //-->
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<!-- header_eof //-->

<!-- body //-->
<?php
/* The main landmark, because nothing else on the page provides one.
 *
 * Zen Cart's admin header emits a nav (v2.2+) and its footer a footer element,
 * but no release wraps the page body in a main landmark -- so everything
 * between them sits outside any landmark at all, which is what a landmark audit
 * reports. This page owns that markup, so it can supply the missing one.
 *
 * Labelled by the <h1> rather than with a duplicate aria-label: one name, and
 * it stays correct when the heading is translated. */
?>
<main class="container-fluid scf-admin-page" aria-labelledby="scfPageHeading">
    <h1 id="scfPageHeading"><?php echo HEADING_TITLE; ?></h1>
    <p><?php echo SCF_ADMIN_INTRO; ?></p>

<?php
if ($messageStack->size > 0) {
    echo $messageStack->output();
}
?>

    <div class="scf-admin-filters">
<?php
// role=search is the landmark a filter/search form belongs in. The label
// matters because a page may hold more than one landmark of a kind.
echo zen_draw_form('scfFilter', FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'get',
    'role="search" aria-label="' . zen_output_string_protected(SCF_ADMIN_ARIA_FILTERS) . '"');

$statusOptions = [
    ['id' => '', 'text' => SCF_ADMIN_FILTER_ALL],
    ['id' => '0', 'text' => SCF_ADMIN_STATUS_PENDING],
    ['id' => '1', 'text' => SCF_ADMIN_STATUS_SUBSCRIBED],
    ['id' => '2', 'text' => SCF_ADMIN_STATUS_UNSUBSCRIBED],
];
?>
        <label>
            <?php echo SCF_ADMIN_FILTER_LABEL; ?><br>
            <?php echo zen_draw_pull_down_menu('status', $statusOptions, ($filterStatus === null ? '' : (string)$filterStatus)); ?>
        </label>
        <label>
            <?php echo SCF_ADMIN_SEARCH_LABEL; ?><br>
            <?php echo zen_draw_input_field('search', $filterSearch, 'size="28"'); ?>
        </label>
        <button type="submit" class="btn btn-primary"><?php echo SCF_ADMIN_BUTTON_FILTER; ?></button>
        <a class="btn btn-default" href="<?php echo zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'SSL'); ?>"><?php echo SCF_ADMIN_BUTTON_RESET; ?></a>
        <?php echo '</form>'; ?>

<?php if ($scfTableExists && $totalRows > 0) { ?>
        <?php echo zen_draw_form('scfExport', FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'post'); ?>
        <?php echo zen_draw_hidden_field('action', 'export'); ?>
        <button type="submit" class="btn btn-default"><?php echo SCF_ADMIN_BUTTON_EXPORT; ?></button>
        <?php echo '</form>'; ?>
        <?php echo zen_draw_form('scfInviteAll', FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, '', 'post', 'onsubmit="return confirm(\'' . zen_output_string_protected(SCF_ADMIN_CONFIRM_INVITE_ALL) . '\');"'); ?>
        <?php echo zen_draw_hidden_field('action', 'invite_all'); ?>
        <button type="submit" class="btn btn-default"><?php echo SCF_ADMIN_BUTTON_INVITE_ALL; ?></button>
        <?php echo '</form>'; ?>
<?php } ?>
    </div>

    <p class="scf-admin-note"><?php echo SCF_ADMIN_INVITE_INTRO; ?></p>

<?php if ($scfTableExists) { ?>
    <section class="scf-admin-import" aria-labelledby="scfImportHeading">
        <h2 id="scfImportHeading"><?php echo SCF_ADMIN_IMPORT_HEADING; ?></h2>
        <p><?php echo SCF_ADMIN_IMPORT_INTRO; ?></p>
        <p><?php echo SCF_ADMIN_IMPORT_CONSENT; ?></p>
        <?php
        // enctype has to be set explicitly -- zen_draw_form() defaults to
        // application/x-www-form-urlencoded, which silently discards the file
        // and leaves $_FILES empty.
        echo zen_draw_form(
            'scfImport',
            FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS,
            scfAdminQueryString(),
            'post',
            'enctype="multipart/form-data"'
        );
        echo zen_draw_hidden_field('action', 'import');
        // Advisory only: the browser uses it to filter the file picker. The
        // server decides what it will actually read.
        ?>
        <label>
            <?php echo SCF_ADMIN_IMPORT_FILE_LABEL; ?><br>
            <input type="file" name="scf_csv" accept=".csv,text/csv" required>
        </label>
        <button type="submit" class="btn btn-primary"><?php echo SCF_ADMIN_BUTTON_IMPORT; ?></button>
        <?php echo '</form>'; ?>
        <p class="scf-admin-hint"><?php
            echo sprintf(SCF_ADMIN_IMPORT_LIMIT_NOTE, SCF_IMPORT_MAX_ROWS, (int)(SCF_IMPORT_MAX_BYTES / 1024));
        ?></p>
<?php
        /* The blank sheet that starts this workflow: print it, collect
         * addresses on it, type them into a spreadsheet, upload that above.
         * Served by this page rather than linked directly -- see the note at
         * the top of the file about the zc_plugins allowlist. */
        if (is_file(DIR_FS_CATALOG . 'zc_plugins/SocialContactFooter/v1.0.0/pdf/newsletter_signup_form.pdf')) {
?>
        <p class="scf-admin-hint">
            <a class="btn btn-default" target="_blank" rel="noopener noreferrer"
               href="<?php echo zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, 'scf_form=1', 'SSL'); ?>"><?php
                echo SCF_ADMIN_IMPORT_PRINT_FORM;
            ?></a>
            <?php echo SCF_ADMIN_IMPORT_PRINT_HINT; ?>
        </p>
        <p class="scf-admin-hint">
            <a class="btn btn-default"
               href="<?php echo zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, 'scf_sample=1', 'SSL'); ?>"><?php
                echo SCF_ADMIN_BUTTON_SAMPLE;
            ?></a>
            <?php echo SCF_ADMIN_SAMPLE_HINT; ?>
        </p>
<?php
        }
?>
<?php
        // Reported once, after the redirect that followed the upload.
        if (!empty($_SESSION['scf_import_problems'])) {
            echo '<p class="scf-admin-hint"><strong>'
                . SCF_ADMIN_IMPORT_PROBLEMS_HEADING . '</strong></p><ul class="scf-admin-hint">';
            foreach ($_SESSION['scf_import_problems'] as $problem) {
                echo '<li>' . zen_output_string_protected($problem) . '</li>';
            }
            echo '</ul>';
            unset($_SESSION['scf_import_problems']);
        }
?>
    </section>
<?php } ?>

<?php if ($scfTableExists) { ?>
<?php   if (empty($rows)) { ?>
    <p><?php echo ($filterStatus === null && $filterSearch === '') ? SCF_ADMIN_TABLE_EMPTY : SCF_ADMIN_TABLE_EMPTY_FILTERED; ?></p>
<?php   } else { ?>
    <p><?php echo sprintf(SCF_ADMIN_COUNT_SUMMARY, $firstShown, $lastShown, $totalRows); ?></p>

    <table class="scf-admin-table">
        <caption class="scf-visually-hidden"><?php echo SCF_ADMIN_TABLE_CAPTION; ?></caption>
        <thead>
            <tr>
                <?php /* scope="col" is what ties each cell to its heading when
                         a screen reader reads across a row. */ ?>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_EMAIL; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_FORMAT; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_STATUS; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_REGISTERED; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_ADDED; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_CONFIRMED; ?></th>
                <th scope="col"><?php echo SCF_ADMIN_HEADING_ACTION; ?></th>
            </tr>
        </thead>
        <tbody>
<?php
        foreach ($rows as $row) {
            $status = (int)$row['status'];
            $queryString = scfAdminQueryString();
?>
            <tr>
                <?php /* The address is what identifies the row, so it is the
                         row's header cell -- that is what makes a screen
                         reader announce "ada@example.com, Status, Subscribed"
                         instead of just "Subscribed". */ ?>
                <th scope="row"><?php echo zen_output_string_protected($row['subscriber_email']); ?></th>
                <td><?php echo zen_output_string_protected($row['email_format']); ?></td>
                <td class="scf-admin-status-<?php echo $status; ?>"><?php echo $scfStatusLabels[$status] ?? ''; ?></td>
                <td><?php
                    if (!empty($row['invite_accepted']) || scf_admin_customer_id_for_email($row['subscriber_email']) > 0) {
                        echo SCF_ADMIN_ACCOUNT_ACTIVE;
                    } elseif (!empty($row['invite_sent'])) {
                        // When, not just whether. Deciding whether to send
                        // again depends entirely on how long ago the last one
                        // went out, and the exact timestamp is on hover.
                        $scfAgo = scf_admin_time_ago($row['invite_sent']);
                        echo '<span title="' . zen_output_string_protected((string)$row['invite_sent']) . '">'
                            . ($scfAgo === ''
                                ? SCF_ADMIN_ACCOUNT_INVITED
                                : zen_output_string_protected(sprintf(SCF_ADMIN_ACCOUNT_INVITED_WHEN, $scfAgo)))
                            . '</span>';
                    } else {
                        echo SCF_ADMIN_ACCOUNT_NONE;
                    }
                ?></td>
                <td><?php echo zen_output_string_protected((string)$row['date_added']); ?></td>
                <td><?php echo empty($row['date_confirmed']) ? SCF_ADMIN_NEVER : zen_output_string_protected((string)$row['date_confirmed']); ?></td>
                <td>
<?php           if (scf_admin_invite_eligible($row)) { ?>
                    <?php echo zen_draw_form('scfInv' . (int)$row['subscriber_id'], FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, $queryString, 'post', 'class="scf-admin-rowform" onsubmit="return confirm(\'' . zen_output_string_protected(SCF_ADMIN_CONFIRM_INVITE) . '\');"'); ?>
                    <?php echo zen_draw_hidden_field('action', 'invite') . zen_draw_hidden_field('sid', (int)$row['subscriber_id']); ?>
                    <button type="submit" class="btn btn-xs btn-primary" aria-label="<?php echo zen_output_string_protected(sprintf(SCF_ADMIN_ARIA_INVITE, $row['subscriber_email'])); ?>"><?php echo SCF_ADMIN_BUTTON_INVITE; ?></button>
                    <?php echo '</form>'; ?>
<?php           } elseif (scf_admin_invite_resendable($row)) { ?>
                    <?php
                    /* Invited, account still pending, not accepted: the
                       first-invitation button is correctly gone, but the owner
                       still needs a way to reach them.

                       The confirmation carries how long ago the last one went
                       out, and says something firmer when that was minutes
                       rather than days -- mail takes time to arrive, and the
                       cost of an impatient second click lands on the
                       subscriber, whose password quietly stops working. */
                    $scfAgo = scf_admin_time_ago($row['invite_sent']);
                    $scfConfirm = sprintf(
                        scf_admin_invite_sent_recently($row)
                            ? SCF_ADMIN_CONFIRM_REINVITE_HASTY
                            : SCF_ADMIN_CONFIRM_REINVITE,
                        $scfAgo
                    );
                    ?>
                    <?php echo zen_draw_form('scfReInv' . (int)$row['subscriber_id'], FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, $queryString, 'post', 'class="scf-admin-rowform" onsubmit="return confirm(\'' . zen_output_string_protected(str_replace(["'", "\n"], ["\\'", ' '], $scfConfirm)) . '\');"'); ?>
                    <?php echo zen_draw_hidden_field('action', 'reinvite') . zen_draw_hidden_field('sid', (int)$row['subscriber_id']); ?>
                    <button type="submit" class="btn btn-xs btn-primary" aria-label="<?php echo zen_output_string_protected(sprintf(SCF_ADMIN_ARIA_REINVITE, $row['subscriber_email'])); ?>"><?php echo SCF_ADMIN_BUTTON_REINVITE; ?></button>
                    <?php echo '</form>'; ?>
<?php           } ?>
<?php           if ($status !== 1) { ?>
                    <?php echo zen_draw_form('scfSub' . (int)$row['subscriber_id'], FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, $queryString, 'post', 'class="scf-admin-rowform"'); ?>
                    <?php echo zen_draw_hidden_field('action', 'set_subscribed') . zen_draw_hidden_field('sid', (int)$row['subscriber_id']); ?>
                    <button type="submit" class="btn btn-xs btn-default" aria-label="<?php echo zen_output_string_protected(sprintf(SCF_ADMIN_ARIA_CONFIRM, $row['subscriber_email'])); ?>"><?php echo SCF_ADMIN_BUTTON_CONFIRM; ?></button>
                    <?php echo '</form>'; ?>
<?php           } ?>
<?php           if ($status !== 2) { ?>
                    <?php echo zen_draw_form('scfUnsub' . (int)$row['subscriber_id'], FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, $queryString, 'post', 'class="scf-admin-rowform"'); ?>
                    <?php echo zen_draw_hidden_field('action', 'set_unsubscribed') . zen_draw_hidden_field('sid', (int)$row['subscriber_id']); ?>
                    <button type="submit" class="btn btn-xs btn-default" aria-label="<?php echo zen_output_string_protected(sprintf(SCF_ADMIN_ARIA_UNSUBSCRIBE, $row['subscriber_email'])); ?>"><?php echo SCF_ADMIN_BUTTON_UNSUBSCRIBE; ?></button>
                    <?php echo '</form>'; ?>
<?php           } ?>
                    <?php echo zen_draw_form('scfDel' . (int)$row['subscriber_id'], FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, $queryString, 'post', 'class="scf-admin-rowform" onsubmit="return confirm(\'' . zen_output_string_protected(SCF_ADMIN_CONFIRM_DELETE) . '\');"'); ?>
                    <?php echo zen_draw_hidden_field('action', 'delete') . zen_draw_hidden_field('sid', (int)$row['subscriber_id']); ?>
                    <button type="submit" class="btn btn-xs btn-danger" aria-label="<?php echo zen_output_string_protected(sprintf(SCF_ADMIN_ARIA_DELETE, $row['subscriber_email'])); ?>"><?php echo SCF_ADMIN_BUTTON_DELETE; ?></button>
                    <?php echo '</form>'; ?>
                </td>
            </tr>
<?php   } ?>
        </tbody>
    </table>

<?php       if ($totalPages > 1) { ?>
    <?php /* Paging is navigation, and a labelled <nav> is how a screen-reader
             user finds it without reading the whole table first. */ ?>
    <nav class="scf-admin-pager" aria-label="<?php echo zen_output_string_protected(SCF_ADMIN_ARIA_PAGER); ?>">
<?php           if ($currentPage > 1) { ?>
        <a href="<?php echo zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(['page' => $currentPage - 1]), 'SSL'); ?>"><?php echo SCF_ADMIN_PREVIOUS; ?></a>
<?php           } ?>
        <span><?php echo (int)$currentPage . ' / ' . (int)$totalPages; ?></span>
<?php           if ($currentPage < $totalPages) { ?>
        <a href="<?php echo zen_href_link(FILENAME_SOCIAL_CONTACT_FOOTER_SUBSCRIBERS, scfAdminQueryString(['page' => $currentPage + 1]), 'SSL'); ?>"><?php echo SCF_ADMIN_NEXT; ?></a>
<?php           } ?>
    </nav>
<?php       } ?>
<?php   } ?>
<?php } ?>
</main>
<!-- body_eof //-->

<script>
/* One submission per click.
 *
 * Every action guarded here writes something -- an invitation creates an
 * account and sends mail, a re-send invalidates a password already in
 * somebody's inbox -- so a double-click, or a second press because nothing
 * appeared to happen yet, must not become two requests.
 *
 * Export is deliberately NOT guarded: it downloads a file without navigating
 * away, so a disabled button would never come back, and running it twice costs
 * nothing anyway.
 *
 * The button is disabled after the browser has begun submitting, never before:
 * disabling it first would cancel the very submission it is meant to protect.
 * Plain ES5 with no library, because this has to work on whatever admin theme
 * the store is running.
 */
(function () {
    var forms = document.querySelectorAll('form.scf-admin-rowform, form[name="scfInviteAll"]');

    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-scf-sent') === '1') {
                // Already on its way. Swallow the second press.
                event.preventDefault();
                return false;
            }

            // A confirm() that was declined never reaches here, because the
            // onsubmit handler returns false and cancels the event first.
            if (event.defaultPrevented) {
                return true;
            }

            form.setAttribute('data-scf-sent', '1');

            var button = form.querySelector('button[type="submit"]');
            if (button) {
                window.setTimeout(function () {
                    button.disabled = true;
                    button.className += ' scf-admin-busy';
                }, 0);
            }

            return true;
        });
    });
}());
</script>

<!-- footer //-->
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
<!-- footer_eof //-->
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
