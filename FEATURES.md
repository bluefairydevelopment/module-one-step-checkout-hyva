# BlueFairy One Step Checkout for Hyvä: Features

- **Alpine.js checkout.** The one-page checkout as an Alpine component. No RequireJS, no Knockout.
- **Strict CSP ready.** No inline script; every Alpine attribute names a property or a method, as Hyvä's CSP build requires.
- **Same behaviour as the Luma checkout.** Both storefronts run the same checkout core, so validation, shipping refresh, coupons, payment methods (Stripe's Payment Element included) and order placement are identical.
- **No theme rebuild.** The checkout ships its own stylesheet; the theme's Tailwind build is not needed.
