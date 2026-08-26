<?php
/**
 * Every subscriber-facing email carries an unsubscribe route.
 *
 * Held as a rule rather than a habit: a template added later without one fails
 * here. The store-owner notice is excluded deliberately -- it goes to the shop,
 * not to a subscriber, and a working unsubscribe link in it would let the owner
 * opt somebody out by accident.
 */

require __DIR__ . '/_bootstrap.php';

define('IS_ADMIN_FLAG', true);

$PLUGIN = scf_plugin_dir();

$sources = [
    'storefront' => $PLUGIN . '/catalog/includes/languages/english/extra_definitions/lang.social_contact_footer.php',
    'admin' => $PLUGIN . '/admin/includes/languages/english/lang.social_contact_footer_subscribers.php',
];

/** Templates that go to the store owner, not to a subscriber. */
$internal = ['SCF_EMAIL_ADMIN_SUBJECT', 'SCF_EMAIL_ADMIN_TEXT', 'SCF_EMAIL_ADMIN_HTML'];

$bodies = [];
foreach ($sources as $where => $file) {
    foreach (require $file as $key => $value) {
        if (strpos($key, 'SCF_EMAIL_') !== 0) { continue; }
        if (substr($key, -5) === '_TEXT' || substr($key, -5) === '_HTML') {
            $bodies[$key] = ['where' => $where, 'value' => $value];
        }
    }
}

section('subscriber-facing bodies');
check(sprintf('found %d body templates', count($bodies)), count($bodies) >= 8);

foreach ($bodies as $key => $info) {
    if (in_array($key, $internal, true)) {
        echo "  --    $key skipped (goes to the store owner)\n";
        continue;
    }
    check("$key offers an unsubscribe route ({$info['where']})",
        stripos($info['value'], 'unsubscribe') !== false);
}

section('placeholder safety');
/* A bare % that is not a positional placeholder is fatal in sprintf, and would
 * only ever surface in production. This bit once: font-size:90% in an HTML
 * template. */
foreach ($bodies as $key => $info) {
    $stripped = str_replace('%%', '', preg_replace('~%\d+\$s~', '', $info['value']));
    check("$key contains no stray percent sign", strpos($stripped, '%') === false);
}

section('every template is reachable');
$code = '';
foreach ([
    '/catalog/includes/functions/extra_functions/social_contact_footer_functions.php',
    '/admin/includes/functions/extra_functions/social_contact_footer_invite.php',
    '/admin/includes/functions/extra_functions/social_contact_footer_import.php',
] as $f) {
    $code .= file_get_contents($PLUGIN . $f);
}
foreach (array_keys($bodies) as $key) {
    $subject = str_replace(['_TEXT', '_HTML'], '_SUBJECT', $key);
    check("$key is used by the code",
        strpos($code, $key) !== false || strpos($code, $subject) !== false);
}

section('the whitelist request');
/* Asking the reader to add the sending address to their contacts is the single
 * most effective thing a small sender can do about junk folders. */
foreach ($bodies as $key => $info) {
    if (in_array($key, $internal, true)) { continue; }
    check("$key asks the reader to whitelist the sending address",
        stripos($info['value'], 'address book') !== false
        || stripos($info['value'], 'contacts') !== false);
}

section('the re-sent invitation is its own message');
/* A re-send necessarily carries a different password, because the first one was
 * hashed and never kept. If the original was merely slow rather than lost, the
 * subscriber holds both -- so the second has to say which is live. */
check('a separate re-invitation template exists', isset($bodies['SCF_EMAIL_REINVITE_TEXT']));
if (isset($bodies['SCF_EMAIL_REINVITE_TEXT'], $bodies['SCF_EMAIL_INVITE_TEXT'])) {
    check('it is not the first invitation reused',
        $bodies['SCF_EMAIL_REINVITE_TEXT']['value'] !== $bodies['SCF_EMAIL_INVITE_TEXT']['value']);
    check('it says the earlier password no longer works',
        stripos($bodies['SCF_EMAIL_REINVITE_TEXT']['value'], 'no longer works') !== false);
    check('the HTML part says so too',
        stripos($bodies['SCF_EMAIL_REINVITE_HTML']['value'], 'no longer works') !== false);
}

scf_done('all subscriber emails carry an unsubscribe route');
