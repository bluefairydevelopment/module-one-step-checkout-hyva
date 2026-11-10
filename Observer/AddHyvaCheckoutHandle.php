<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\OneStepCheckoutHyva\Observer;

use BlueFairy\OneStepCheckout\Observer\AddCheckoutHandle;
use Hyva\Theme\Service\CurrentTheme;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * On a Hyvä theme, follows the base module's checkout handle with this package's own, which
 * swaps in the Alpine template.
 *
 * The handle is added here, after the base module's, and not left to Hyvä's hyva_ prefix for
 * checkout_index_index: layout updates are merged in handle order, and a template set on the
 * block before the handle that declares it is overwritten by that declaration. This module
 * loads after the base module (etc/module.xml), so this observer runs after the base one.
 */
class AddHyvaCheckoutHandle implements ObserverInterface
{
    public const HANDLE = 'bluefairy_osc_checkout_hyva';

    public function __construct(private readonly CurrentTheme $currentTheme)
    {
    }

    public function execute(Observer $observer): void
    {
        $layout = $observer->getData('layout');
        if (!$layout instanceof LayoutInterface) {
            return;
        }
        $update = $layout->getUpdate();
        // Only where the base module has put its checkout on the page, and only on a Hyvä theme:
        // a Luma store view on the same installation keeps the base module's own template.
        if (!in_array(AddCheckoutHandle::HANDLE, $update->getHandles(), true) || !$this->currentTheme->isHyva()) {
            return;
        }

        $update->addHandle(self::HANDLE);
    }
}
