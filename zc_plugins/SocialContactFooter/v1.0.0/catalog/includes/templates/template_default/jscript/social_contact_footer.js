/**
 * Social Contact Footer -- Subscribe button gate.
 *
 * The Subscribe button stays out of the way until the visitor has entered an
 * email address AND chosen a mail format. No format is pre-selected, so
 * "chosen" genuinely means the person picked one.
 *
 * PROGRESSIVE ENHANCEMENT -- please keep this property if you edit the file.
 * The button is rendered visible by the server. This script is the only thing
 * that ever hides it, so a visitor with JavaScript disabled, or one for whom
 * this file fails to load, still sees a working form. The server refuses a
 * signup with no format either way, so the rule is enforced regardless.
 *
 * TO OVERRIDE: copy this file to
 *   includes/templates/YOUR_TEMPLATE/jscript/social_contact_footer.js
 * and the plugin will load yours instead.
 *
 * Written in ES5 with no dependencies -- no jQuery, no build step -- because
 * it has to run on whatever a Zen Cart storefront happens to be serving.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
(function () {
    'use strict';

    function init() {
        var wrapper = document.getElementById('scfWrapper');
        if (!wrapper) {
            return;
        }

        var form = wrapper.querySelector('form.scf-form');
        if (!form) {
            return;
        }

        var email = form.querySelector('#scf_email');
        var action = form.querySelector('.scf-field-action');
        if (!email || !action) {
            return;
        }

        var radios = form.querySelectorAll('input[name="scf_format"]');

        function formatChosen() {
            // When the store owner has switched the format question off there
            // are no radios to answer, so this condition is simply met.
            if (!radios.length) {
                return true;
            }
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    return true;
                }
            }
            return false;
        }

        function emailLooksValid() {
            var value = (email.value || '').replace(/^\s+|\s+$/g, '');
            if (value === '') {
                return false;
            }
            // Prefer the browser's own judgement on type="email".
            if (typeof email.checkValidity === 'function') {
                return email.checkValidity();
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        function update() {
            var ready = emailLooksValid() && formatChosen();

            // `hidden` also removes it from the accessibility tree, so it is
            // not announced or focusable while it does not apply.
            action.hidden = !ready;
            action.setAttribute('aria-hidden', ready ? 'false' : 'true');
        }

        function listen(el, events) {
            for (var i = 0; i < events.length; i++) {
                if (el.addEventListener) {
                    el.addEventListener(events[i], update, false);
                } else if (el.attachEvent) {
                    el.attachEvent('on' + events[i], update);
                }
            }
        }

        listen(email, ['input', 'keyup', 'change', 'blur']);
        for (var i = 0; i < radios.length; i++) {
            listen(radios[i], ['change', 'click']);
        }

        // Mark the form so the stylesheet knows scripting is active. Nothing
        // is hidden until this point, which is what keeps the no-JavaScript
        // case working.
        form.setAttribute('data-scf-gated', 'true');
        update();
    }

    if (document.readyState === 'loading') {
        if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', init, false);
        } else {
            document.attachEvent('onreadystatechange', function () {
                if (document.readyState !== 'loading') {
                    init();
                }
            });
        }
    } else {
        init();
    }
}());
