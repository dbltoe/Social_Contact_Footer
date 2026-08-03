<?php
/**
 * Social Contact Footer -- admin "Footer Newsletter Subscribers" page strings (English).
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$define = [
    'HEADING_TITLE' => 'Social Contact Footer &mdash; Newsletter Subscribers',

    'SCF_ADMIN_INTRO' => 'Everyone who has signed up through the footer newsletter form. Confirmed subscribers whose address matches a customer account also have that account\'s newsletter flag set, so Zen Cart\'s own Newsletter Manager can reach them. The blog link in the footer involves no signup, so nothing about it appears here.',

    'SCF_ADMIN_TABLE_EMPTY' => 'No subscribers yet.',
    'SCF_ADMIN_TABLE_EMPTY_FILTERED' => 'No subscribers match that filter.',

    /* Column headings and buttons are Title Case throughout this page, matching
     * the configuration titles. */
    'SCF_ADMIN_HEADING_EMAIL' => 'E-Mail Address',
    'SCF_ADMIN_HEADING_FORMAT' => 'Format',
    'SCF_ADMIN_HEADING_STATUS' => 'Status',
    'SCF_ADMIN_HEADING_ADDED' => 'Signed Up',
    'SCF_ADMIN_HEADING_CONFIRMED' => 'Confirmed',
    'SCF_ADMIN_HEADING_ACTION' => 'Action',

    'SCF_ADMIN_STATUS_PENDING' => 'Awaiting confirmation',
    'SCF_ADMIN_STATUS_SUBSCRIBED' => 'Subscribed',
    'SCF_ADMIN_STATUS_UNSUBSCRIBED' => 'Unsubscribed',

    'SCF_ADMIN_FILTER_ALL' => 'All',
    'SCF_ADMIN_FILTER_LABEL' => 'Show',
    'SCF_ADMIN_SEARCH_LABEL' => 'Search E-Mail',
    'SCF_ADMIN_BUTTON_FILTER' => 'Apply',
    'SCF_ADMIN_BUTTON_RESET' => 'Reset',
    'SCF_ADMIN_BUTTON_EXPORT' => 'Export CSV',
    'SCF_ADMIN_BUTTON_CONFIRM' => 'Mark Subscribed',
    'SCF_ADMIN_BUTTON_UNSUBSCRIBE' => 'Unsubscribe',
    'SCF_ADMIN_BUTTON_DELETE' => 'Delete',

    'SCF_ADMIN_CONFIRM_DELETE' => 'Delete this subscriber permanently?',

    /* Announced but not shown.
     *
     * Every row carries the same four or five button labels, so out of table
     * context a screen reader reads "Delete, Delete, Delete…" with nothing to
     * tell them apart. %s is the address, which is the only thing that does. */
    'SCF_ADMIN_TABLE_CAPTION' => 'Footer newsletter subscribers, with the status and account state of each.',
    'SCF_ADMIN_ARIA_DELETE' => 'Delete %s',
    'SCF_ADMIN_ARIA_UNSUBSCRIBE' => 'Unsubscribe %s',
    'SCF_ADMIN_ARIA_CONFIRM' => 'Mark %s subscribed',
    'SCF_ADMIN_ARIA_INVITE' => 'Invite %s to create an account',
    'SCF_ADMIN_ARIA_REINVITE' => 'Send the invitation to %s again',
    'SCF_ADMIN_ARIA_FILTERS' => 'Filter and search subscribers',
    'SCF_ADMIN_ARIA_PAGER' => 'Subscriber list pages',

    'SCF_ADMIN_SUCCESS_UPDATED' => 'Subscriber updated.',
    'SCF_ADMIN_SUCCESS_DELETED' => 'Subscriber deleted.',
    'SCF_ADMIN_ERROR_NOT_FOUND' => 'That subscriber no longer exists.',
    'SCF_ADMIN_ERROR_NO_TABLE' => 'The subscribers table is missing. Re-install the plugin from Plugin Manager.',

    'SCF_ADMIN_COUNT_SUMMARY' => 'Showing %1$d&ndash;%2$d of %3$d.',
    'SCF_ADMIN_PREVIOUS' => '&laquo; Previous',
    'SCF_ADMIN_NEXT' => 'Next &raquo;',
    'SCF_ADMIN_NEVER' => '&mdash;',

    'SCF_ADMIN_EXPORT_FILENAME' => 'social_contact_footer_subscribers',

    /* CSV import ----------------------------------------------------------
     * For addresses collected on paper -- typically at an event, using the
     * printable sign-up sheet in the plugin's pdf/ directory. */
    'SCF_ADMIN_IMPORT_HEADING' => 'Import Subscribers From a CSV File',
    'SCF_ADMIN_IMPORT_INTRO' => 'For addresses collected on paper, such as at a trade show. The file needs one column headed <strong>email</strong>, and may have a second headed <strong>email_format</strong> containing <strong>HTML</strong> or <strong>TEXT</strong>. The headings go in the first row and may be in either order; anything else in the file is ignored.',
    'SCF_ADMIN_IMPORT_CONSENT' => '<strong>Everyone imported is added as awaiting confirmation and sent a confirmation request, exactly as if they had used the footer form.</strong> They are not subscribed until they click the link in it. Handwriting gets misread, and a mistyped address belongs to somebody who never asked to hear from you &mdash; one confirmation request to them is a mistake, a newsletter is spam. Keep the signed forms: they are what justifies sending the request.',
    'SCF_ADMIN_IMPORT_FILE_LABEL' => 'CSV File',
    'SCF_ADMIN_BUTTON_IMPORT' => 'Upload and Import',
    'SCF_ADMIN_IMPORT_LIMIT_NOTE' => 'Up to %1$d rows per file, %2$dKB maximum. Split a larger list and upload it in parts.',
    'SCF_ADMIN_IMPORT_PRINT_FORM' => 'Print the Sign-Up Sheet',
    'SCF_ADMIN_IMPORT_ERROR_NO_FORM' => 'The printable sign-up sheet is missing from the plugin directory (pdf/newsletter_signup_form.pdf).',
    'SCF_ADMIN_IMPORT_PRINT_HINT' => 'A blank sheet to take to an event: twenty-one lines, each with a space for an address and a box for HTML or TEXT-Only.',
    'SCF_ADMIN_BUTTON_SAMPLE' => 'Download a Sample CSV',
    'SCF_ADMIN_SAMPLE_HINT' => 'Three example rows with the headings already in place. Open it in your spreadsheet, replace the examples with your own addresses, and save as CSV. The third row leaves the format blank, which is allowed and means TEXT.',
    'SCF_ADMIN_SAMPLE_FILENAME' => 'social_contact_footer_sample_import',

    'SCF_ADMIN_IMPORT_SUCCESS' => '%1$d subscriber(s) imported and sent a confirmation request. %2$d row(s) skipped.',
    'SCF_ADMIN_IMPORT_PROBLEMS_HEADING' => 'Rows that were skipped:',

    'SCF_ADMIN_IMPORT_ERROR_NO_FILE' => 'No file was chosen.',
    'SCF_ADMIN_IMPORT_ERROR_NOT_UPLOADED' => 'That file did not arrive as an upload and was not read.',
    'SCF_ADMIN_IMPORT_ERROR_TOO_BIG' => 'That file is larger than %dKB. Split the list and upload it in parts.',
    'SCF_ADMIN_IMPORT_ERROR_PARTIAL' => 'The upload did not finish. Please try again.',
    'SCF_ADMIN_IMPORT_ERROR_SERVER' => 'The server could not accept the upload. Check the PHP upload settings, or ask your host.',
    'SCF_ADMIN_IMPORT_ERROR_UNREADABLE' => 'The uploaded file could not be opened.',
    'SCF_ADMIN_IMPORT_ERROR_EMPTY' => 'That file has no rows in it.',
    'SCF_ADMIN_IMPORT_ERROR_NO_EMAIL_COLUMN' => 'No <strong>%s</strong> column was found in the first row of that file. Nothing was imported.',

    'SCF_ADMIN_IMPORT_PROBLEM_NO_EMAIL' => 'Row %d: no address.',
    'SCF_ADMIN_IMPORT_PROBLEM_BAD_EMAIL' => 'Row %1$d: "%2$s" is not a valid address.',
    'SCF_ADMIN_IMPORT_PROBLEM_KNOWN' => 'Row %1$d: %2$s is already on this list.',
    'SCF_ADMIN_IMPORT_PROBLEM_FAILED' => 'Row %1$d: %2$s could not be added.',

    /* Newsletter email header image ---------------------------------------
     * Optional. These emails carry NO image unless one is supplied here. */
    'SCF_ADMIN_HEADER_HEADING' => 'Newsletter Email Header Image',
    'SCF_ADMIN_HEADER_INTRO' => 'Optional. <strong>These three emails &mdash; the confirmation request, the welcome message and the registration invitation &mdash; carry no image unless you add one here.</strong> Not your store logo, nothing. Anything you add appears at the top of those three and nowhere else: your store logo file is not touched, and every other email your store sends is unchanged.',
    'SCF_ADMIN_HEADER_SPEC' => 'Most email headers are <strong>550 &times; 110</strong> pixels, and that is the size to aim for. Accepted formats: <strong>%1$s</strong>. Nothing is resized or cropped, and the size is not enforced &mdash; but much wider than that gets scaled down or cropped by mail clients on a phone. Maximum %2$dKB.',
    'SCF_ADMIN_HEADER_FILE_LABEL' => 'Image File',
    'SCF_ADMIN_BUTTON_HEADER_UPLOAD' => 'Upload Header Image',
    'SCF_ADMIN_BUTTON_HEADER_REMOVE' => 'Remove It',
    'SCF_ADMIN_CONFIRM_HEADER_REMOVE' => 'Remove the header image? These emails go back to carrying no image at all.',
    'SCF_ADMIN_HEADER_CURRENT' => 'In use now: <strong>%1$s</strong> (%2$d &times; %3$d pixels).',
    'SCF_ADMIN_HEADER_NONE' => 'None set. <strong>These emails go out with no header image at all</strong> &mdash; not your store logo, nothing. Add one here if you want one.',
    'SCF_ADMIN_HEADER_PREVIEW_ALT' => 'The header image currently used in newsletter emails',

    'SCF_ADMIN_HEADER_SUCCESS' => 'Header image saved (%1$d &times; %2$d pixels). It appears in the next newsletter email this plugin sends.',
    'SCF_ADMIN_HEADER_REMOVED' => 'Header image removed. These emails go back to carrying no image at all.',
    'SCF_ADMIN_HEADER_ERROR_NO_FILE' => 'No image was chosen.',
    'SCF_ADMIN_HEADER_ERROR_UPLOAD' => 'The upload did not finish. Please try again.',
    'SCF_ADMIN_HEADER_ERROR_TOO_BIG' => 'That image is larger than %dKB. Mail clients are unforgiving about message weight, so please use a smaller one.',
    'SCF_ADMIN_HEADER_ERROR_NOT_UPLOADED' => 'That file did not arrive as an upload and was not saved.',
    'SCF_ADMIN_HEADER_ERROR_EXTENSION' => 'That file type cannot be used. Accepted formats: %s.',
    'SCF_ADMIN_HEADER_ERROR_NOT_AN_IMAGE' => 'That file is not a readable image, whatever its name says.',
    'SCF_ADMIN_HEADER_ERROR_DIR' => 'The plugin\'s email folder is missing or not writable: %s',
    'SCF_ADMIN_HEADER_ERROR_SAVE' => 'The image could not be saved.',
    'SCF_ADMIN_HEADER_ERROR_NONE_SET' => 'There is no header image to remove.',
    'SCF_ADMIN_HEADER_ERROR_REMOVE' => 'The image could not be removed. Check the file permissions on the plugin\'s email folder.',

    /* Invite Registration -------------------------------------------------
     * Turns a confirmed subscriber into a pending customer account and emails
     * them a link plus a password. */
    'SCF_ADMIN_HEADING_REGISTERED' => 'Account',
    // Both buttons name what the invitation is *to*. "Invite" on its own left
    // the store owner guessing, and the answer is not obvious from the page.
    'SCF_ADMIN_BUTTON_INVITE' => 'Invite to Create an Account',
    'SCF_ADMIN_BUTTON_INVITE_ALL' => 'Invite All Eligible Subscribers to Create an Account',
    'SCF_ADMIN_CONFIRM_INVITE' => 'Create a pending customer account for this subscriber and send them an invitation?',
    'SCF_ADMIN_CONFIRM_INVITE_ALL' => 'Create pending customer accounts for every eligible subscriber and send them all an invitation? This cannot be undone in bulk.',
    'SCF_ADMIN_INVITE_SUCCESS' => 'Invitation sent to %s.',
    'SCF_ADMIN_INVITE_BULK_SUCCESS' => '%1$d invitation(s) sent. %2$d subscriber(s) were skipped as not eligible.',
    'SCF_ADMIN_INVITE_NONE_ELIGIBLE' => 'No subscribers are eligible for an invitation right now.',
    'SCF_ADMIN_INVITE_ERROR_NOT_FOUND' => 'That subscriber no longer exists.',
    'SCF_ADMIN_INVITE_ERROR_NOT_ELIGIBLE' => 'That subscriber cannot be invited: only confirmed subscribers without an existing account are eligible.',
    'SCF_ADMIN_INVITE_ERROR_FAILED' => 'The customer account could not be created. Nothing was sent.',

    /* Re-sending. An invitation creates the account immediately, so the
     * subscriber stops being eligible for a first invitation the moment one
     * goes out -- this is what remains available until they accept. */
    'SCF_ADMIN_BUTTON_REINVITE' => 'Send the Invitation Again',
    // %s is how long ago the last one went out. Both versions state the
    // consequence, because it is the part that is easy to miss: the password
    // already sitting in the subscriber's inbox stops working.
    'SCF_ADMIN_CONFIRM_REINVITE' => 'The last invitation went out %s. Sending it again issues a NEW password and a NEW link, and the ones already sent stop working. Continue?',
    'SCF_ADMIN_CONFIRM_REINVITE_HASTY' => 'You sent this invitation only %s. Mail can take a few minutes to arrive, and sending again will invalidate the password already on its way. Send it again anyway?',
    'SCF_ADMIN_REINVITE_SUCCESS' => 'Invitation sent again to %s, with a new password.',
    'SCF_ADMIN_REINVITE_ERROR_NOT_ELIGIBLE' => 'That invitation cannot be sent again: it applies only to a subscriber who has been invited, still has a pending account, and has not accepted yet.',

    'SCF_ADMIN_ACCOUNT_NONE' => 'No',
    'SCF_ADMIN_ACCOUNT_INVITED' => 'Invited',
    'SCF_ADMIN_ACCOUNT_ACTIVE' => 'Registered',
    // %s is a phrase from the list below, so the column reads "Invited
    // 3 minutes ago" -- the one fact needed before deciding to send again.
    'SCF_ADMIN_ACCOUNT_INVITED_WHEN' => 'Invited %s',

    'SCF_ADMIN_TIME_JUST_NOW' => 'moments ago',
    'SCF_ADMIN_TIME_MINUTE' => '1 minute ago',
    'SCF_ADMIN_TIME_MINUTES' => '%d minutes ago',
    'SCF_ADMIN_TIME_HOUR' => '1 hour ago',
    'SCF_ADMIN_TIME_HOURS' => '%d hours ago',
    'SCF_ADMIN_TIME_DAY' => 'yesterday',
    'SCF_ADMIN_TIME_DAYS' => '%d days ago',

    /* Registration invitation.
     *   %1$s  store name
     *   %2$s  the link -- it activates the account and lands on the login page,
     *         which is why the message only mentions one link
     *   %3$s  their E-Mail address (their username)
     *   %4$s  the generated password
     *   %5$s  the unsubscribe link
     *   %6$s  the address this mail was sent from
     */
    'SCF_EMAIL_INVITE_SUBJECT' => 'An account is waiting for you at %s',
    'SCF_EMAIL_INVITE_TEXT' =>
        "We hope you've enjoyed our Newsletters so much that you'd like to join "
        . "our customer ranks.\n\n"
        . "Use the E-Mail address this E-Mail was sent to as the username and\n\n"
        . "    %4\$s\n\n"
        . "as the password when you log in at the following link:\n\n"
        . "%2\$s\n\n"
        . "Please change that password once you are signed in.\n\n"
        . "If you'd prefer to stay with Newsletter Subscription only, just ignore "
        . "this E-Mail.\n\n"
        . "If you would like to unsubscribe from our Newsletters, use this link:\n"
        . "%5\$s\n\n"
        . "PLEASE ADD US TO YOUR ADDRESS BOOK\n"
        . "So our messages reach your inbox rather than your junk or spam folder, "
        . "please add this address to your contacts:\n\n    %6\$s\n",
    'SCF_EMAIL_INVITE_HTML' =>
        '<p>We hope you&rsquo;ve enjoyed our Newsletters so much that you&rsquo;d like to '
        . 'join our customer ranks.</p>'
        . '<p>Use the E-Mail address this E-Mail was sent to as the username and '
        . '<strong>%4$s</strong> as the password when you '
        . '<a href="%2$s">log in at this link</a>.</p>'
        . '<p>If the link does not work, copy and paste this address into your browser:<br>%2$s</p>'
        . '<p><strong>Please change that password once you are signed in.</strong></p>'
        . '<p>If you&rsquo;d prefer to stay with Newsletter Subscription only, just ignore '
        . 'this E-Mail.</p>'
        . '<p>If you would like to unsubscribe from our Newsletters, '
        . '<a href="%5$s">click unsubscribe</a>.</p>'
        . '<p style="font-size:0.9em">Please add <strong>%6$s</strong> to your contacts so our '
        . 'messages reach your inbox rather than your junk folder.</p>',

    /* Re-sent invitation. Same placeholders as above.
     *
     * A separate message, not the one above sent twice. The password cannot be
     * repeated -- it was hashed on the way in and the plaintext is gone -- so a
     * re-send necessarily carries a different one. If the first message was
     * merely slow rather than lost, the recipient is now holding two of these,
     * and identical wording would leave them guessing which password is live.
     * They would most likely try the older one, fail, and give up.
     *
     * So this one says outright that it replaces the earlier message, and puts
     * that before the credentials rather than after them.
     */
    'SCF_EMAIL_REINVITE_SUBJECT' => 'Your new sign-in details for %s',
    'SCF_EMAIL_REINVITE_TEXT' =>
        "We are sending your invitation again, in case the first one did not "
        . "reach you.\n\n"
        . "IF YOU HAVE AN EARLIER E-MAIL FROM US ABOUT THIS, PLEASE DELETE IT.\n"
        . "The password it contains no longer works, and neither does its link. "
        . "Only the details below are current.\n\n"
        . "Use the E-Mail address this E-Mail was sent to as the username and\n\n"
        . "    %4\$s\n\n"
        . "as the password when you log in at the following link:\n\n"
        . "%2\$s\n\n"
        . "Please change that password once you are signed in.\n\n"
        . "If you'd prefer to stay with Newsletter Subscription only, just ignore "
        . "this E-Mail.\n\n"
        . "If you would like to unsubscribe from our Newsletters, use this link:\n"
        . "%5\$s\n\n"
        . "PLEASE ADD US TO YOUR ADDRESS BOOK\n"
        . "So our messages reach your inbox rather than your junk or spam folder, "
        . "please add this address to your contacts:\n\n    %6\$s\n",
    'SCF_EMAIL_REINVITE_HTML' =>
        '<p>We are sending your invitation again, in case the first one did not reach '
        . 'you.</p>'
        . '<p><strong>If you have an earlier E-Mail from us about this, please delete '
        . 'it.</strong> The password it contains no longer works, and neither does its '
        . 'link. Only the details below are current.</p>'
        . '<p>Use the E-Mail address this E-Mail was sent to as the username and '
        . '<strong>%4$s</strong> as the password when you '
        . '<a href="%2$s">log in at this link</a>.</p>'
        . '<p>If the link does not work, copy and paste this address into your browser:<br>%2$s</p>'
        . '<p><strong>Please change that password once you are signed in.</strong></p>'
        . '<p>If you&rsquo;d prefer to stay with Newsletter Subscription only, just ignore '
        . 'this E-Mail.</p>'
        . '<p>If you would like to unsubscribe from our Newsletters, '
        . '<a href="%5$s">click unsubscribe</a>.</p>'
        . '<p style="font-size:0.9em">Please add <strong>%6$s</strong> to your contacts so our '
        . 'messages reach your inbox rather than your junk folder.</p>',

    'SCF_ADMIN_INVITE_INTRO' => 'Subscribers with no customer account can be invited to create one. They receive a link and a password; the account stays pending, and unusable, until they click the link. Ignoring the invitation changes nothing about their subscription.',
];

return $define;
