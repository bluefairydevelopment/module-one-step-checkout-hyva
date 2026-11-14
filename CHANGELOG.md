# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.3 — 2026-11-14

### Fixed

- A guest who types an email that already has an account is offered a sign-in on the page (password, **Sign In**, **Forgot Your Password?**), following Magento's **Enable Guest Checkout Login** setting; guest checkout stays open (M2MAP-906, base 1.0.3). Works under Hyvä's strict Content Security Policy.

- Stripe: card entry is always on screen for a shopper whom Stripe Link already knows; Link is offered only when the base module's new **Offer Stripe Link in the Card Form** is Yes (M2MAP-906, base 1.0.3).

- The email is looked up when the browser fills it in (change), as well as when the shopper leaves the field.
- The checkout page carries the states and provinces of the store's default country only; another country's list is fetched when the shopper chooses it, and the Hyvä view draws it when it arrives (base 1.0.3, M2MAP-880).
- Which address fields ask for new shipping rates follows the base module's new **Shipping Rates > Ask for New Rates When These Change** (base 1.0.3, M2MAP-880).

### Changed

- Requires `bluefairy/module-one-step-checkout` ^1.0.3.

## 1.0.2 — 2026-11-13

### Fixed

- What a shopper types before Alpine starts the checkout is no longer wiped when the cart first loads (M2MAP-904), seen on a Hyvä CSP child theme whose Alpine starts seconds after the page shows. A field emptied before then stays empty. A signed-in customer's saved address is kept as it is, rather than merged into a new one (base 1.0.2).

### Changed

- Requires `bluefairy/module-one-step-checkout` ^1.0.2.

## 1.0.1 — 2026-11-13

### Added

- Stripe's Payment Element in the Hyvä checkout, from `bluefairy/module-one-step-checkout` 1.0.1: the card form sits in its own element that Alpine never redraws, so the card typed stays put. Works under Hyvä's strict Content Security Policy.

### Changed

- Requires `bluefairy/module-one-step-checkout` ^1.0.1.

## 1.0.0 — 2026-11-10

### Added

- One Step Checkout for Hyvä themes as an Alpine.js component: the same single page, validation, coupon, payment methods and order summary as the Luma checkout, without RequireJS or Knockout.
- Works under Hyvä's strict Content Security Policy: no inline script, and every Alpine attribute names a property or a method.
