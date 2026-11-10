# One Step Checkout for Hyvä: acceptance walkthrough

The Hyvä view shares its markup hooks (`data-osc-*`) with the base module's Luma view, so the
walkthrough is the base package's Playwright suite, run against a Hyvä store view:

```bash
cd vendor/bluefairy/module-one-step-checkout/Test/Playwright   # or a checkout of the base package
OSC_THEME=hyva BASE_URL=https://hyva-store.example \
OSC_ADMIN_PATH=admin OSC_ADMIN_USER=... OSC_ADMIN_PASS=... \
OSC_MAGENTO_CLI='ssh host cd /var/www/magento && php bin/magento' \
    npx playwright test
```

Run it on a store view whose theme uses Hyvä's strict Content Security Policy (the Alpine CSP
build): that is the configuration this package is written for, and the one where a template
expression Alpine CSP cannot evaluate shows up as a dead control. The steps are L01 to L26 of the
base runbook (`Test/Playwright/uat-runbook.md` in the base package); on Hyvä they additionally prove:

| ID | Intention |
|---|---|
| H01 | The Hyvä checkout renders with no Content Security Policy violation and no console error from this package |
| H02 | Every control works under the Alpine CSP build: typing, radio choices, checkboxes, coupon, place order |
