<?php
/**
 * Social Contact Footer -- storefront output observer.
 *
 * Auto-loaded and instantiated by `includes/init_includes/init_observers.php`
 * at autoload point 175, on every supported Zen Cart release. The filename
 * (`auto.social_contact_footer.php`) and the class name below must stay in
 * step: Zen Cart derives the expected class as
 * 'zcObserver' . base::camelize('social_contact_footer', true).
 *
 * Two notifiers are observed so the block lands in a sensible place whatever
 * the shop is running:
 *
 *   NOTIFY_FOOTER_AFTER_NAVSUPP  Fires inside tpl_footer.php, added in ZC
 *                                v2.2.0. This is the preferred spot -- the
 *                                block sits with the rest of the footer.
 *   NOTIFY_FOOTER_END            Fires in tpl_main_page.php just before the
 *                                closing </body> tag. Present since long
 *                                before v1.5.8, and still present on v3.0.0,
 *                                so it is the dependable fallback for older
 *                                carts and for custom templates whose
 *                                tpl_footer.php predates the newer notifier.
 *
 * Whichever fires first wins; the second is ignored.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class zcObserverSocialContactFooter extends base
{
    /**
     * Guards against emitting the block twice when both notifiers fire.
     *
     * @var bool
     */
    protected $rendered = false;

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_HTML_HEAD_END',
            'NOTIFY_FOOTER_AFTER_NAVSUPP',
            'NOTIFY_FOOTER_END',
        ]);
    }

    /**
     * End of <head>: link the stylesheet.
     *
     * This notifier is present in v1.5.8 and v3.0.0-dev alike, so the plugin's
     * appearance can live in a real, cacheable, template-overridable stylesheet
     * on every supported release rather than being dumped inline in the body.
     */
    public function updateNotifyHtmlHeadEnd($class, $eventID, $param1 = null)
    {
        if (!function_exists('scf_block_is_visible') || !scf_block_is_visible()) {
            return;
        }

        echo scf_render_styles();
    }

    /**
     * Preferred hook: inside the footer (Zen Cart v2.2.0 and later).
     */
    public function updateNotifyFooterAfterNavsupp($class, $eventID, $param1 = null)
    {
        $this->emitBlock();
    }

    /**
     * Fallback hook: end of page (every supported release).
     */
    public function updateNotifyFooterEnd($class, $eventID, $param1 = null)
    {
        $this->emitBlock();
    }

    /**
     * @return void
     */
    protected function emitBlock()
    {
        if ($this->rendered) {
            return;
        }
        $this->rendered = true;

        if (!function_exists('scf_render_block')) {
            // The plugin's extra_functions file did not load -- fail quietly
            // rather than breaking the storefront.
            return;
        }

        echo scf_render_block();
    }
}
