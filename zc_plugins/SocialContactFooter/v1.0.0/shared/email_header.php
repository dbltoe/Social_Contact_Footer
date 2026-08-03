<?php
/**
 * Social Contact Footer -- locating the optional newsletter email header image.
 *
 * Shared, because both sides send these emails: the storefront sends the
 * confirmation request and the welcome message, the admin sends registration
 * invitations. One definition of where the file lives and what it may be
 * called, rather than two that drift apart.
 *
 * Defines functions and returns the configuration, so a caller can have either.
 * The guards let both sides require it in the same request without colliding.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('SCF_HEADER_IMAGE_DIR')) {
    /**
     * The plugin's own email/ directory, with a trailing separator.
     */
    define('SCF_HEADER_IMAGE_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR);
}

if (!defined('SCF_HEADER_IMAGE_EXTENSIONS')) {
    /**
     * Accepted formats, in the order they are searched.
     *
     * These five and no others: Zen Cart's shipped `zc_plugins/.htaccess`
     * denies everything and re-allows exactly `jpe?g|gif|webp|png` among image
     * types, so anything else would upload happily and then fail to load in
     * every subscriber's mail client.
     */
    define('SCF_HEADER_IMAGE_EXTENSIONS', 'jpg,jpeg,png,gif,webp');
}

if (!defined('SCF_HEADER_IMAGE_TYPICAL')) {
    /**
     * The size Zen Cart's own email headers use. Advisory -- nothing is
     * resized, cropped or rejected on these numbers.
     */
    define('SCF_HEADER_IMAGE_TYPICAL', '550 x 110');
}

if (!function_exists('scf_header_image_extensions')) {
    /**
     * @return array
     */
    function scf_header_image_extensions()
    {
        return explode(',', SCF_HEADER_IMAGE_EXTENSIONS);
    }
}

if (!function_exists('scf_header_image_path')) {
    /**
     * The header image's on-disk path, or '' when none has been supplied.
     *
     * @return string
     */
    function scf_header_image_path()
    {
        foreach (scf_header_image_extensions() as $extension) {
            $path = SCF_HEADER_IMAGE_DIR . 'header.' . $extension;
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }
}

if (!function_exists('scf_email_dir_url')) {
    /**
     * The public URL of this plugin's email/ directory, trailing slash included.
     *
     * Both directory names are read from disk rather than written out, so a
     * version bump cannot leave this pointing somewhere that no longer exists
     * -- a break that would surface as a missing image in somebody else's inbox
     * rather than as an error anyone here would see.
     *
     * @return string
     */
    function scf_email_dir_url()
    {
        // Every constant is guarded. An undefined one is a fatal Error on PHP 8,
        // and this runs while a storefront visitor is subscribing -- exactly the
        // moment a fatal is least acceptable and hardest to attribute.
        //
        // The catalog server, always: the link has to resolve for a subscriber
        // reading their mail, even when the message was sent from the admin.
        $server = '';
        if (defined('HTTP_CATALOG_SERVER')) {
            $server = HTTP_CATALOG_SERVER;
        } elseif (defined('HTTP_SERVER')) {
            $server = HTTP_SERVER;
        }

        if ($server === '') {
            return '';
        }

        $catalog = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';
        $versionDir = basename(dirname(__DIR__));
        $pluginDir = basename(dirname(dirname(__DIR__)));

        return $server . $catalog . 'zc_plugins/' . $pluginDir . '/' . $versionDir . '/email/';
    }
}

if (!function_exists('scf_header_image_url')) {
    /**
     * The public URL of the header image, or '' when none has been supplied.
     *
     * @return string
     */
    function scf_header_image_url()
    {
        $path = scf_header_image_path();

        return ($path === '') ? '' : scf_email_dir_url() . basename($path);
    }
}

if (!function_exists('scf_header_image_block')) {
    /**
     * Email template variables for this plugin's messages.
     *
     * THE RULE: no image at the top of these emails unless the store owner has
     * supplied one. Not the store logo -- nothing.
     *
     * That takes some doing, because the `<img>` tag is written into the store's
     * email template, not into anything this plugin controls:
     *
     *     <img src="$EMAIL_LOGO_FILE" alt="$EMAIL_LOGO_ALT_TEXT" ... />
     *
     * and `Email::setTemplateVarsFromDefines()` fills that variable in from the
     * store's own settings whenever the caller has not:
     *
     *     if (empty($block['EMAIL_LOGO_FILE'])) { ...store logo... }
     *
     * -- identical in v1.5.8, v2.0, v2.1, v2.2, v2.3 and v3.0. So passing an
     * empty value does NOT remove the image; it asks for the store logo. There
     * is no way to delete the tag itself: a plugin can only supply its own
     * template through NOTIFY_EMAIL_REGISTER_ADDITIONAL_TEMPLATE_DIRS, which
     * exists in v3.0 alone.
     *
     * So the tag is pointed at a 1x1 fully transparent PNG that ships with this
     * plugin. The reader sees nothing, and with the alt text empty there is
     * nothing to read out either -- including in the very common case of a mail
     * client that blocks images, where a real logo would leave its alt text
     * sitting there instead.
     *
     * Width and height are left empty on purpose. The store's numbers describe
     * the store's logo, and forcing them onto somebody else's image distorts it;
     * unset, a mail client uses the file's own dimensions.
     *
     * Whatever this returns applies to this plugin's three emails and nothing
     * else. The store's own header image file is never touched, and every other
     * email the store sends is exactly as it was.
     *
     * @return array
     */
    function scf_header_image_block()
    {
        $url = scf_header_image_url();

        if ($url === '') {
            $url = scf_header_spacer_url();

            // No absolute URL could be built -- the store's own constants are
            // not available. Return nothing rather than an image tag pointing
            // nowhere: Zen Cart then inserts the store logo, which is not what
            // was asked for but is a great deal better than a broken image in
            // every subscriber's inbox.
            if ($url === '') {
                return [];
            }

            return [
                'EMAIL_LOGO_FILE' => $url,
                'EMAIL_LOGO_ALT_TEXT' => '',
                'EMAIL_LOGO_ALT_TITLE_TEXT' => '',
                'EMAIL_LOGO_WIDTH' => '',
                'EMAIL_LOGO_HEIGHT' => '',
            ];
        }

        return [
            'EMAIL_LOGO_FILE' => $url,
            'EMAIL_LOGO_ALT_TEXT' => defined('STORE_NAME') ? STORE_NAME : '',
            'EMAIL_LOGO_ALT_TITLE_TEXT' => defined('STORE_NAME') ? STORE_NAME : '',
            'EMAIL_LOGO_WIDTH' => '',
            'EMAIL_LOGO_HEIGHT' => '',
        ];
    }
}

if (!function_exists('scf_header_spacer_url')) {
    /**
     * The 1x1 transparent PNG used when no header image has been supplied.
     *
     * @return string
     */
    function scf_header_spacer_url()
    {
        return scf_email_dir_url() . 'spacer.png';
    }
}

return [
    'extensions' => scf_header_image_extensions(),
    'dir' => SCF_HEADER_IMAGE_DIR,
    'typical' => SCF_HEADER_IMAGE_TYPICAL,
];
