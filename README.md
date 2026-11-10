# BlueFairy One Step Checkout for Hyvä

The Alpine.js storefront of [BlueFairy One Step Checkout](https://bluefairydevelopment.com) for
Hyvä themes.

| | |
|---|---|
| Package | `bluefairy/module-one-step-checkout-hyva` |
| Module | `BlueFairy_OneStepCheckoutHyva` |
| Requires | `bluefairy/module-one-step-checkout`, `hyva-themes/magento2-theme-module` 1.5 or later |
| Magento | Open Source and Adobe Commerce 2.4.8 and 2.4.9 |
| PHP | 8.3, 8.4, 8.5 |
| Licence | MIT |

## Install

```bash
composer require bluefairy/module-one-step-checkout-hyva
bin/magento module:enable BlueFairy_OneStepCheckoutHyva
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

There is nothing to configure here. Every setting is the base module's:
**Stores > Configuration > Sales > One Step Checkout**. With One Step Checkout enabled, a store
view on a Hyvä theme gets this checkout and a Luma store view gets the base module's.

## What it needs from the theme

- The checkout page must be served by the Hyvä theme. A store that sends `/checkout` to a Luma
  fallback theme gets the base module's Luma checkout there instead.
- No Tailwind rebuild: the checkout uses the base module's own stylesheet.
- Hyvä Checkout is not required and is not used. Do not enable One Step Checkout on a store view
  where Hyvä Checkout serves the checkout page.

## Strict Content Security Policy

The template has no inline script, and every Alpine attribute names a property or a method of the
component, so it runs under the Alpine CSP build Hyvä uses in strict mode.

## Payment methods

As in the base module: Check / Money Order, Bank Transfer Payment, Cash On Delivery, Purchase Order
and Zero Subtotal Checkout are built in; any other method needs a payment renderer registered in
`window.bluefairyOscPayments` before the checkout starts.

## Support

support@bluefairydevelopment.com
