/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 *
 * One Step Checkout, Hyvä view: an Alpine.js component over the base module's osc-core.js.
 *
 * Written for Hyvä's strict CSP (the Alpine CSP build): the component is registered with
 * Alpine.data(), and every Alpine attribute in the template names a property or a method of
 * this object, never an expression. Methods that depend on a loop item read it from `this`
 * (this.method, this.row, ...). No RequireJS, no Knockout, no data-mage-init.
 *
 * The core keeps plain state; `version` is bumped on each change, and every getter reads it,
 * so Alpine re-evaluates whatever depends on the checkout.
 */
(function () {
    'use strict';

    var SECTIONS = ['contact', 'shipping', 'shipping-method', 'payment', 'review'];

    function component() {
        return {
            version: 0,
            checkout: null,
            synced: false,

            init: function () {
                var self = this,
                    config = JSON.parse(this.$root.getAttribute('data-bf-osc-config')),
                    renderers = window.bluefairyOscPayments || {};

                this.checkout = window.BlueFairyOscCore.createCheckout(config);
                Object.keys(renderers).forEach(function (code) {
                    self.checkout.registerPaymentRenderer(code, renderers[code]);
                });
                this.checkout.subscribe(function () {
                    self.version += 1;
                    self.$nextTick(function () {
                        self.afterChange();
                    });
                });
                this.checkout.load();
            },

            /** The core state, read through the version counter so Alpine tracks it. */
            state: function () {
                return this.version >= 0 ? this.checkout.state : null;
            },

            // ---- sections --------------------------------------------------------------------------
            sectionOpen: function (key) {
                var state = this.state();

                return !this.checkout.config.accordionMode || state.activeSection === key;
            },
            sectionShown: function (key) {
                return this.version >= 0 && this.checkout.sections().indexOf(key) !== -1;
            },
            get contactOpen() { return this.sectionOpen('contact'); },
            get shippingOpen() { return this.sectionOpen('shipping'); },
            get shippingMethodOpen() { return this.sectionOpen('shipping-method'); },
            get paymentOpen() { return this.sectionOpen('payment'); },
            get reviewOpen() { return this.sectionOpen('review'); },
            get contactExpanded() { return String(this.contactOpen); },
            get shippingExpanded() { return String(this.shippingOpen); },
            get shippingMethodExpanded() { return String(this.shippingMethodOpen); },
            get paymentExpanded() { return String(this.paymentOpen); },
            get reviewExpanded() { return String(this.reviewOpen); },
            get shippingShown() { return this.sectionShown('shipping'); },
            get shippingMethodShown() { return this.sectionShown('shipping-method'); },

            toggleSection: function (event) {
                var key = event.currentTarget.getAttribute('data-osc-toggle');

                if (this.checkout.config.accordionMode && SECTIONS.indexOf(key) !== -1) {
                    this.checkout.setActiveSection(key);
                }
            },

            /** In the all-sections layout, remember the section the shopper is working in. */
            onFocusIn: function (event) {
                var section = event.target.closest('[data-osc-section]');

                if (section && !this.checkout.config.accordionMode && !this.checkout.state.loading) {
                    this.checkout.setActiveSection(section.getAttribute('data-osc-section'));
                }
            },

            // ---- page state ------------------------------------------------------------------------
            get loading() { return this.state().loading; },
            get rootClass() { return this.state().loading ? 'bf-osc--loading' : ''; },
            get globalError() { return this.state().errors._global || ''; },
            get hasGlobalError() { return !!this.state().errors._global; },
            get placeDisabled() { return this.state().loading || this.state().placing; },
            get placing() { return this.state().placing; },
            get notPlacing() { return !this.state().placing; },

            // ---- address ---------------------------------------------------------------------------
            get addressBookRows() { return this.version >= 0 ? this.checkout.addressBookRows() : []; },
            get hasAddressBook() { return this.addressBookRows.length > 0; },
            get billingSame() { return this.state().billingSame; },
            get billingSameShown() { return !this.state().isVirtual; },
            get billingFormShown() { return this.state().isVirtual || !this.state().billingSame; },

            // ---- shipping methods ------------------------------------------------------------------
            get shippingRows() { return this.version >= 0 ? this.checkout.shippingRows() : []; },
            get hasShippingRows() { return this.shippingRows.length > 0; },
            get noShippingRows() { return this.shippingRows.length === 0; },
            get shippingEmptyText() {
                var state = this.state();

                return this.checkout.t(state.shipping.country_id && !state.loading ? 'shipping.none' : 'shipping.empty');
            },
            optionClass: function () {
                return this.row.selected ? 'bf-osc__option bf-osc__option--selected' : 'bf-osc__option';
            },

            // ---- payment methods -------------------------------------------------------------------
            get paymentRows() { return this.version >= 0 ? this.checkout.paymentRows() : []; },
            get noPaymentRows() { return !this.state().loading && this.paymentRows.length === 0; },
            rowHasInfo: function () {
                return this.row.selected && !this.row.isPurchaseOrder && this.row.info !== '';
            },
            rowNeedsPoNumber: function () {
                return this.row.selected && this.row.isPurchaseOrder;
            },

            // ---- coupon, agreements, summary -------------------------------------------------------
            get couponCode() { return this.state().couponCode || ''; },
            get hasCoupon() { return !!this.state().couponCode; },
            get noCoupon() { return !this.state().couponCode; },
            get agreements() { return this.state().agreements; },
            agreementIsManual: function () {
                return this.agreement.mode === 1;
            },
            agreementIsAutomatic: function () {
                return this.agreement.mode !== 1;
            },
            agreementContentRef: function () {
                return 'content:' + this.agreement.agreementId;
            },
            agreementLabelRef: function () {
                return 'label:' + this.agreement.agreementId;
            },
            agreementErrorKey: function () {
                return 'agreement.' + this.agreement.agreementId;
            },
            get summaryItems() { return this.version >= 0 ? this.checkout.summaryItems() : []; },
            get totalRows() { return this.version >= 0 ? this.checkout.totalRows() : []; },
            totalClass: function () {
                return this.total.grand ? 'bf-osc__grand' : '';
            },

            // ---- input -----------------------------------------------------------------------------
            onInput: function (event) {
                var target = event.target,
                    kind = target.getAttribute('data-osc'),
                    form = target.closest('[data-osc-address]');

                if (form && target.hasAttribute('data-osc-input')) {
                    this.checkout.setAddressField(form.getAttribute('data-osc-address'), target.getAttribute('data-osc-input'), target.value);
                } else if (kind === 'email') {
                    this.checkout.setEmail(target.value);
                } else if (kind === 'coupon-input') {
                    this.checkout.setCouponInput(target.value);
                } else if (kind === 'po-number') {
                    this.checkout.setPoNumber(target.value);
                } else if (target.hasAttribute('data-osc-gift')) {
                    this.checkout.setGiftMessageField(target.getAttribute('data-osc-gift'), target.value);
                }
            },

            onChange: function (event) {
                var self = this,
                    target = event.target,
                    kind = target.getAttribute('data-osc');

                if (target.name === 'bf-osc-shipping-method') {
                    this.checkout.selectShippingMethod(target.value);
                } else if (target.name === 'bf-osc-payment-method') {
                    this.checkout.selectPaymentMethod(target.value);
                } else if (kind === 'billing-same') {
                    this.checkout.setBillingSame(target.checked);
                } else if (kind === 'newsletter') {
                    this.checkout.setNewsletter(target.checked);
                } else if (kind === 'address-book') {
                    this.checkout.useCustomerAddress(target.value).then(function () {
                        self.syncInputs('shipping');
                    });
                    this.syncInputs('shipping');
                } else if (target.hasAttribute('data-osc-agreement')) {
                    this.checkout.setAgreement(target.getAttribute('data-osc-agreement'), target.checked);
                } else if (target.tagName === 'SELECT') {
                    this.onInput(event);
                }
            },

            applyCoupon: function () {
                var self = this;

                this.checkout.applyCoupon().then(function () {
                    var input = self.$root.querySelector('[data-osc="coupon-input"]');

                    if (!self.checkout.state.errors.coupon && input) {
                        input.value = '';
                    }
                });
            },

            removeCoupon: function () {
                this.checkout.removeCoupon();
            },

            placeOrder: function () {
                var self = this;

                this.checkout.placeOrder().then(function (placed) {
                    if (placed) {
                        window.location.assign(self.checkout.config.successUrl);

                        return;
                    }
                    self.$nextTick(function () {
                        var invalid = self.$root.querySelector('[aria-invalid="true"]'),
                            global = self.$root.querySelector('[data-osc="global-error"]');

                        if (self.checkout.state.errors._global && global) {
                            global.focus();
                        } else if (invalid) {
                            invalid.focus();
                        }
                    });
                });
            },

            // ---- the parts written to the DOM directly ---------------------------------------------
            // Field errors, input values and region options live in the server-rendered address forms
            // shared with the Luma view; they are addressed by data attribute, not by Alpine bindings.

            afterChange: function () {
                var state = this.checkout.state;

                if (!state.loading && !this.synced) {
                    this.synced = true;
                    this.syncInputs('shipping');
                    this.syncInputs('billing');
                    this.setValue('[data-osc="email"]', state.email);
                }
                this.drawRegion('shipping');
                this.drawRegion('billing');
                this.drawErrors();
                this.drawAgreementTexts();
            },

            setValue: function (selector, value) {
                var node = this.$root.querySelector(selector);

                if (node) {
                    node.value = value || '';
                }
            },

            syncInputs: function (type) {
                var address = this.checkout.state[type],
                    form = this.$root.querySelector('[data-osc-address="' + type + '"]');

                if (!form) {
                    return;
                }
                Array.prototype.forEach.call(form.querySelectorAll('[data-osc-input]'), function (input) {
                    var field = input.getAttribute('data-osc-input');

                    if (field !== 'region_id') {
                        input.value = address[field] || '';
                    }
                });
                this.drawRegion(type);
            },

            drawRegion: function (type) {
                var address = this.checkout.state[type],
                    form = this.$root.querySelector('[data-osc-address="' + type + '"]'),
                    select = form && form.querySelector('[data-osc-input="region_id"]'),
                    input = form && form.querySelector('[data-osc-input="region"]'),
                    regions = this.checkout.regionsFor(address.country_id),
                    signature = address.country_id + ':' + regions.length,
                    self = this;

                if (!select || !input) {
                    return;
                }
                if (select.getAttribute('data-osc-drawn') !== signature) {
                    select.setAttribute('data-osc-drawn', signature);
                    while (select.firstChild) {
                        select.removeChild(select.firstChild);
                    }
                    [{id: '', name: this.checkout.t('region.select')}].concat(regions).forEach(function (region) {
                        var option = document.createElement('option');

                        option.value = region.id;
                        option.textContent = region.name;
                        select.appendChild(option);
                    });
                    if (document.activeElement !== input) {
                        input.value = address.region || '';
                    }
                }
                select.hidden = regions.length === 0;
                input.hidden = regions.length > 0;
                if (document.activeElement !== select) {
                    select.value = address.region_id || '';
                }

                return self;
            },

            drawErrors: function () {
                var errors = this.checkout.state.errors;

                Array.prototype.forEach.call(this.$root.querySelectorAll('[data-osc-error]'), function (node) {
                    node.textContent = errors[node.getAttribute('data-osc-error')] || '';
                });
                Array.prototype.forEach.call(this.$root.querySelectorAll('[data-osc-address]'), function (form) {
                    var type = form.getAttribute('data-osc-address');

                    Array.prototype.forEach.call(form.querySelectorAll('[data-osc-input]'), function (input) {
                        var field = input.getAttribute('data-osc-input');

                        field = field === 'region_id' ? 'region' : field;
                        if (errors[type + '.' + field]) {
                            input.setAttribute('aria-invalid', 'true');
                        } else {
                            input.removeAttribute('aria-invalid');
                        }
                    });
                });
            },

            /**
             * Terms and conditions. The server prepares both texts as HTML exactly as Magento's own
             * checkout does (the merchant's markup for an HTML agreement, escaped text otherwise); the
             * Alpine CSP build has no x-html, so they are set here. Nothing else is written as markup.
             */
            drawAgreementTexts: function () {
                var agreements = this.checkout.state.agreements;

                Array.prototype.forEach.call(this.$root.querySelectorAll('[data-osc-agreement-html]'), function (node) {
                    var parts = node.getAttribute('data-osc-agreement-html').split(':'),
                        agreement = agreements.filter(function (candidate) {
                            return String(candidate.agreementId) === parts[1];
                        })[0];

                    if (!agreement || node.getAttribute('data-osc-drawn') === '1') {
                        return;
                    }
                    node.setAttribute('data-osc-drawn', '1');
                    node.innerHTML = parts[0] === 'content' ? agreement.content : agreement.checkboxText;
                });
            }
        };
    }

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('bfOscCheckout', component);
    });
}());
