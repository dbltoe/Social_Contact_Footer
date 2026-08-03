<?php
/**
 * Social Contact Footer -- the optional header image for newsletter emails.
 *
 * Same shape as the Admin Add Customer plugin's welcome-email banner: an
 * optional file the store owner supplies, living in the plugin's own `email/`
 * directory as `header.<ext>`, uploaded from an admin page.
 *
 * WHAT IT DOES TO THE EMAIL
 * -------------------------
 * These three emails carry no image unless the owner supplies one here -- not
 * the store logo, nothing. Anything supplied appears at the top of those three
 * and nowhere else; the store's own header image file is never touched, and no
 * other email the store sends changes.
 *
 * How that is achieved, and why it takes any doing at all, is in
 * shared/email_header.php. This file only puts the file on disk and takes it
 * off again.
 * * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

/**
 * Bytes. A header image has no business being larger, and mail clients are
 * unforgiving about total message weight.
 */
define('SCF_HEADER_IMAGE_MAX_BYTES', 512000);

// Where the file lives, what it may be called, and how it reaches the email.
// Shared with the storefront, which sends two of the three messages.
require_once dirname(__DIR__, 4) . '/shared/email_header.php';
/**
 * Store an uploaded header image, replacing any that is already there.
 *
 * Every existing header.* is removed first, so exactly one can ever be present.
 * Without that, uploading a .png while a .jpg was already there would leave the
 * .jpg still winning the search order above -- the owner would see a successful
 * upload and no change in their email.
 *
 * @param array $file One entry from $_FILES.
 * @return array ['ok' => bool, 'message' => string]
 */
function scf_admin_store_header_image(array $file)
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_NO_FILE];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_UPLOAD];
    }

    if ((int)$file['size'] > SCF_HEADER_IMAGE_MAX_BYTES) {
        return [
            'ok' => false,
            'message' => sprintf(SCF_ADMIN_HEADER_ERROR_TOO_BIG, (int)(SCF_HEADER_IMAGE_MAX_BYTES / 1024)),
        ];
    }

    // Refuses any path that did not arrive with this request.
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_NOT_UPLOADED];
    }

    $allowed = scf_header_image_extensions();
    // The uploaded file's own name is never used on disk -- only its extension,
    // and only after it has been checked against the list.
    $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed, true)) {
        return [
            'ok' => false,
            'message' => sprintf(SCF_ADMIN_HEADER_ERROR_EXTENSION, implode(', ', $allowed)),
        ];
    }

    // The extension is a claim; this is a check. A .png that is not an image
    // would be served to every subscriber's mail client as one.
    $size = @getimagesize($file['tmp_name']);
    if ($size === false) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_NOT_AN_IMAGE];
    }

    $dir = SCF_HEADER_IMAGE_DIR;
    if (!is_dir($dir) || !is_writable($dir)) {
        return ['ok' => false, 'message' => sprintf(SCF_ADMIN_HEADER_ERROR_DIR, $dir)];
    }

    foreach ($allowed as $existing) {
        $old = $dir . 'header.' . $existing;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $target = $dir . 'header.' . $extension;
    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_SAVE];
    }

    zen_record_admin_activity(
        'Social Contact Footer: newsletter email header image set (' . basename($target) . ').',
        'warning'
    );

    return [
        'ok' => true,
        'message' => sprintf(SCF_ADMIN_HEADER_SUCCESS, $size[0], $size[1]),
    ];
}

/**
 * Remove the header image, returning the emails to carrying none.
 *
 * @return array ['ok' => bool, 'message' => string]
 */
function scf_admin_remove_header_image()
{
    $path = scf_header_image_path();

    if ($path === '') {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_NONE_SET];
    }

    if (!@unlink($path)) {
        return ['ok' => false, 'message' => SCF_ADMIN_HEADER_ERROR_REMOVE];
    }

    zen_record_admin_activity('Social Contact Footer: newsletter email header image removed.', 'warning');

    return ['ok' => true, 'message' => SCF_ADMIN_HEADER_REMOVED];
}
