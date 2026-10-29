<?php
/**
 * Social Contact Footer -- address gate for invited customers.
 *
 * An invitation creates the account with a placeholder address-book row (blank
 * street, country 0), because customers_default_address_id has to point at a
 * real row on every supported release. Core's init_header.php (v1.5.8 through
 * v3.0.0) checks a logged-in customer's addresses on every page and sends one
 * with an unknown country to edit it, saying: Sorry, but we no longer accept
 * billing or shipping addresses in "". That is the new customer's first page
 * after signing in.
 *
 * This observer gives them a clearer first step:
 *
 *   NOTIFY_LOGIN_SUCCESS     A customer with no usable address is sent to add
 *                            one, with a message that says so, before core's
 *                            check runs on the next page.
 *   NOTIFY_MODULE_ADDRESS_BOOK_ADDED_ADDRESS_BOOK_RECORD
 *                            Their first real address replaces the
 *                            placeholder and becomes the primary address, and
 *                            they go back to the page they were heading for
 *                            when they signed in, if there was one.
 *
 * A customer who already has a real address while the primary still points
 * at the placeholder is repaired at sign-in, with no redirect.
 *
 * Admin Add Customer ships the same gate (same session key, same placeholder
 * shape), so a store running both behaves the same; whichever observer runs
 * first does the work and the other finds nothing left to do.
 *
 * @package  SocialContactFooter
 * @license  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!class_exists('zcObserverScfAddressGate', false)) {
class zcObserverScfAddressGate extends base
{
    /** Where to send the customer once they have added an address; shared with Admin Add Customer. */
    public const RETURN_KEY = 'dbltoe_address_gate_return';

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_LOGIN_SUCCESS',
            'NOTIFY_MODULE_ADDRESS_BOOK_ADDED_ADDRESS_BOOK_RECORD',
        ]);
    }

    public function updateNotifyLoginSuccess($class, $eventID, $param1 = null)
    {
        // An account still awaiting approval goes where core sends it.
        if (!empty($_SESSION['customers_authorization'])) {
            return;
        }

        $return = null;
        if (isset($_SESSION['navigation']) && !empty($_SESSION['navigation']->snapshot['page'])) {
            $return = (string)$_SESSION['navigation']->snapshot['page'];
        }
        $this->gate($return);
    }

    public function updateNotifyModuleAddressBookAddedAddressBookRecord($class, $eventID, $param1 = null)
    {
        $customer_id = $this->customerId();
        $address_id = (int)($param1['address_id'] ?? 0);
        if ($customer_id === 0 || $address_id === 0) {
            return;
        }

        $state = $this->addressState($customer_id);
        if ($state['default_usable'] || !in_array($address_id, $state['usable'], true)) {
            return;
        }
        $this->makePrimary($customer_id, $address_id);

        if (!empty($_SESSION[self::RETURN_KEY])) {
            $page = (string)$_SESSION[self::RETURN_KEY];
            unset($_SESSION[self::RETURN_KEY]);
            zen_redirect(zen_href_link($page, '', 'SSL'));
        }
    }

    /**
     * Repair a primary that points at an unusable row, or send a customer with
     * no usable address at all to add one.
     *
     * @param string|null $return page to go on to after the address is added
     */
    protected function gate($return)
    {
        $customer_id = $this->customerId();
        if ($customer_id === 0) {
            return;
        }

        $state = $this->addressState($customer_id);
        if ($state['default_usable']) {
            return;
        }
        if (!empty($state['usable'])) {
            $this->makePrimary($customer_id, $state['usable'][0]);
            return;
        }

        if ($return !== null && $return !== '') {
            $_SESSION[self::RETURN_KEY] = $return;
        } else {
            unset($_SESSION[self::RETURN_KEY]);
        }
        $GLOBALS['messageStack']->add_session(
            'addressbook',
            defined('SCF_ADDRESS_GATE_NEEDED') ? SCF_ADDRESS_GATE_NEEDED : 'Your account has no address on file. Please add your address to continue.',
            'caution'
        );
        zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'));
    }

    /**
     * The logged-in, non-guest customer's id, or 0.
     */
    protected function customerId()
    {
        if (empty($_SESSION['customer_id'])) {
            return 0;
        }
        if (function_exists('zen_in_guest_checkout') && zen_in_guest_checkout()) {
            return 0;
        }
        return (int)$_SESSION['customer_id'];
    }

    /**
     * The customer's usable address ids (those core can read: the country
     * exists) and whether the stored primary is one of them. Read past the
     * storefront query cache, since this request may just have written them.
     *
     * @return array{usable: int[], default_usable: bool}
     */
    protected function addressState($customer_id)
    {
        global $db;

        $usable = [];
        $result = $db->Execute(
            "SELECT ab.address_book_id
               FROM " . TABLE_ADDRESS_BOOK . " ab
                    INNER JOIN " . TABLE_COUNTRIES . " c ON c.countries_id = ab.entry_country_id
              WHERE ab.customers_id = " . (int)$customer_id . "
              ORDER BY ab.address_book_id",
            null, false, 0, true
        );
        while (!$result->EOF) {
            $usable[] = (int)$result->fields['address_book_id'];
            $result->MoveNext();
        }

        $result = $db->Execute(
            "SELECT customers_default_address_id
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_id = " . (int)$customer_id,
            1, false, 0, true
        );
        $default_id = $result->EOF ? 0 : (int)$result->fields['customers_default_address_id'];

        return ['usable' => $usable, 'default_usable' => in_array($default_id, $usable, true)];
    }

    /**
     * Make $address_id the customer's primary address, in the database and in
     * the session values login() set, and remove the placeholder row(s).
     */
    protected function makePrimary($customer_id, $address_id)
    {
        global $db;

        $result = $db->Execute(
            "SELECT entry_country_id, entry_zone_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id = " . (int)$address_id . "
                AND customers_id = " . (int)$customer_id,
            1, false, 0, true
        );
        if ($result->EOF) {
            return;
        }

        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = " . (int)$address_id . "
              WHERE customers_id = " . (int)$customer_id
        );

        // The placeholder this plugin (or Admin Add Customer) wrote: no street, no country.
        $placeholders = [];
        $rows = $db->Execute(
            "SELECT address_book_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = " . (int)$customer_id . "
                AND entry_country_id = 0
                AND entry_street_address = ''",
            null, false, 0, true
        );
        while (!$rows->EOF) {
            $placeholders[] = (int)$rows->fields['address_book_id'];
            $rows->MoveNext();
        }
        if (!empty($placeholders)) {
            $db->Execute(
                "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE customers_id = " . (int)$customer_id . "
                    AND address_book_id IN (" . implode(',', $placeholders) . ")"
            );
        }

        $old_default = (int)($_SESSION['customer_default_address_id'] ?? 0);
        $_SESSION['customer_default_address_id'] = (int)$address_id;
        $_SESSION['customer_country_id'] = (int)$result->fields['entry_country_id'];
        $_SESSION['customer_zone_id'] = (int)$result->fields['entry_zone_id'];
        foreach (['sendto', 'billto'] as $key) {
            if (isset($_SESSION[$key]) && ((int)$_SESSION[$key] === $old_default || in_array((int)$_SESSION[$key], $placeholders, true))) {
                $_SESSION[$key] = (int)$address_id;
            }
        }
    }
}
}
