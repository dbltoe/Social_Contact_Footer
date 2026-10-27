<?php
/**
 * Social Contact Footer -- storefront language strings (English).
 *
 * The `lang.` prefix plus an array return is the language format understood by
 * Zen Cart v1.5.8 through v3.0.0. To translate, copy this file to
 * `catalog/includes/languages/<your language>/extra_definitions/` inside this
 * plugin and translate the values.
 *
 * Headings marked "default" below are only used when the matching admin
 * setting is left empty, so a store owner can override them without editing
 * this file.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$define = [
    /* Headings ------------------------------------------------------------ */
    'SCF_DEFAULT_ICONS_HEADING' => 'Follow us',

    /* How the store's own name is made possessive. Kept separate so a
     * translator can change the rule, drop it, or special-case a name that
     * already ends in "s" -- English style guides disagree, and many languages
     * do not form possessives this way at all. %s is the store name. */
    'SCF_STORE_POSSESSIVE' => '%s\'s',

    /* Blog line. The blog is just a link -- there is nothing to sign up for,
     * so it is separate from the newsletter form below it.
     * %s in SCF_BLOG_LINK_PATTERN is the possessive store name from above, so
     * the link reads "Acme Widgets's Blog" with nothing for the owner to type.
     *
     * Deliberately NOT called SCF_BLOG_LINK_TEXT: that was a configuration key
     * in v1.0.0. Constants are defined once and the first definition wins, and
     * configuration loads long before language files -- so on a store upgrading
     * from that version, a leftover config value would silently override this
     * pattern until the installer removed it. */
    'SCF_BLOG_LABEL' => 'Visit Our Blog At:',
    'SCF_BLOG_LINK_PATTERN' => '%s Blog',

    /* Newsletter form. %s is again the possessive store name. */
    'SCF_NEWSLETTER_HEADING' => 'Would You Like to Receive %s Newsletter?',
    // Used only if the store has somehow not set a store name.
    'SCF_NEWSLETTER_HEADING_FALLBACK' => 'Would You Like to Receive Our Newsletter?',
    // There is deliberately no name field. A single free-text name does not
    // match Zen Cart's own first-name/last-name convention, and nothing here
    // uses one: the mail goes to an address, and an invited customer fills in
    // their own details when they register.
    'SCF_LABEL_EMAIL' => 'E-Mail Address:',
    'SCF_LABEL_FORMAT' => 'E-Mail Preference:',
    'SCF_LABEL_HONEYPOT' => 'Leave this field blank',
    // Deliberately the same wording Zen Cart uses on the customer's own
    // account page (its ENTRY_EMAIL_TEXT_DISPLAY is 'TEXT-Only'). This plugin
    // writes to the very same customers_email_format column, so a shopper who
    // chooses here and later looks at their account should see one label, not
    // two names for the same setting.
    //
    // Note the stored VALUE is 'TEXT', not 'TEXT-Only' -- the column is
    // varchar(4) in every supported Zen Cart release, so a longer token would
    // be silently truncated. This constant is the label only.
    'SCF_FORMAT_HTML' => 'HTML',
    'SCF_FORMAT_TEXT' => 'TEXT-Only',
    'SCF_BUTTON_SUBSCRIBE' => 'Subscribe',
    // Title case is deliberate: this link matters to the visitor and should
    // read as a heading rather than an afterthought.
    'SCF_PRIVACY_LINK_TEXT' => 'How We Handle Your Details',

    /* Results ------------------------------------------------------------- */
    'SCF_SUCCESS_PENDING' => 'Almost done. Please check your inbox and click the confirmation link we just sent you.',
    'SCF_SUCCESS_SUBSCRIBED' => 'Thank you, you are subscribed.',
    'SCF_SUCCESS_CONFIRMED' => 'Your subscription is confirmed. Thank you!',
    'SCF_SUCCESS_UNSUBSCRIBED' => 'You have been unsubscribed. Sorry to see you go.',
    'SCF_NOTICE_ALREADY_SUBSCRIBED' => 'That address is already subscribed. Your preferences have been updated.',
    'SCF_ERROR_EMAIL' => 'Please enter a valid E-Mail address.',
    'SCF_ERROR_FORMAT' => 'Please choose whether you would like HTML or TEXT-Only before subscribing.',
    'SCF_ERROR_SESSION' => 'Your session expired before the form was submitted. Please try again.',
    'SCF_ERROR_TOO_FAST' => 'That was submitted a little too quickly for us to be sure it was you. Please press Subscribe once more.',
    'SCF_ERROR_BAD_TOKEN' => 'That confirmation link is not valid. It may already have been used.',
    'SCF_ERROR_TOKEN_EXPIRED' => 'That confirmation link has expired. Please subscribe again.',

    /* Registration invitations ------------------------------------------- */
    'SCF_INVITE_ACCEPTED' => 'Your account is now active. Please sign in below using your E-Mail address and the password we sent you.',
    'SCF_INVITE_ALREADY_ACCEPTED' => 'That account is already active. Please sign in below.',

    /* Subscriber emails.
     * The placeholders are the same in every template:
     *   %1$s  store name
     *   %2$s  the message's primary link (confirm, or unsubscribe)
     *   %3$s  the permanent unsubscribe link
     *   %4$s  the address these emails are sent from
     *
     * Every message asks the reader to add %4$s to their address book. Getting
     * that in front of them early is the single most effective thing a small
     * sender can do to stay out of the junk folder.
     */
    'SCF_EMAIL_CONFIRM_SUBJECT' => 'Please confirm your subscription to %s',
    'SCF_EMAIL_CONFIRM_TEXT' =>
        "Thanks for subscribing to %1\$s.\n\n"
        . "To finish signing up, open this link in your browser:\n\n"
        . "%2\$s\n\n"
        . "PLEASE ADD US TO YOUR ADDRESS BOOK\n"
        . "So our messages reach your inbox rather than your junk or spam folder, "
        . "please add this address to your contacts, address book or safe-senders "
        . "list in your E-Mail app:\n\n"
        . "    %4\$s\n\n"
        . "If you did not request this, you can safely ignore this message -- "
        . "nothing will be sent to you unless the link above is used.\n\n"
        . "To unsubscribe from our Newsletters at any point, use this link:\n%3\$s\n",
    'SCF_EMAIL_CONFIRM_HTML' =>
        '<p>Thanks for subscribing to %1$s.</p>'
        . '<p><a href="%2$s">Click here to confirm your subscription</a>.</p>'
        . '<p>If the link does not work, copy and paste this address into your browser:<br>%2$s</p>'
        . '<p><strong>Please add us to your address book.</strong> So our messages reach your '
        . 'inbox rather than your junk or spam folder, please add <strong>%4$s</strong> to your '
        . 'contacts, address book or safe-senders list in your E-Mail app.</p>'
        . '<p>If you did not request this, you can safely ignore this message &mdash; '
        . 'nothing will be sent to you unless the link above is used.</p>'
        . '<p style="font-size:0.9em">You can <a href="%3$s">unsubscribe</a> at any time.</p>',

    'SCF_EMAIL_WELCOME_SUBJECT' => 'You are subscribed to %s',
    'SCF_EMAIL_WELCOME_TEXT' =>
        "You are now subscribed to %1\$s. Thank you!\n\n"
        . "PLEASE ADD US TO YOUR ADDRESS BOOK\n"
        . "So our messages reach your inbox rather than your junk or spam folder, "
        . "please add this address to your contacts, address book or safe-senders "
        . "list in your E-Mail app:\n\n"
        . "    %4\$s\n\n"
        . "Keep this message: to unsubscribe from our Newsletters, open this link "
        . "in your browser at any time:\n\n%3\$s\n",
    'SCF_EMAIL_WELCOME_HTML' =>
        '<p>You are now subscribed to %1$s. Thank you!</p>'
        . '<p><strong>Please add us to your address book.</strong> So our messages reach your '
        . 'inbox rather than your junk or spam folder, please add <strong>%4$s</strong> to your '
        . 'contacts, address book or safe-senders list in your E-Mail app.</p>'
        . '<p>Keep this message &mdash; you can <a href="%3$s">unsubscribe</a> at any time.</p>'
        . '<p style="font-size:0.9em">If the link does not work, copy and paste this address '
        . 'into your browser:<br>%3$s</p>',

    /* Store-owner notice -- %1$s is the address, %2$s the chosen format ---- */
    'SCF_EMAIL_ADMIN_SUBJECT' => 'New footer subscriber at %s',
    'SCF_EMAIL_ADMIN_TEXT' => "A new subscriber has confirmed:\n\nEmail: %1\$s\nPreferred format: %2\$s\n",
];

return $define;
