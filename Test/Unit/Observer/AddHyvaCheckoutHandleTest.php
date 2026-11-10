<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\OneStepCheckoutHyva\Test\Unit\Observer;

use BlueFairy\OneStepCheckout\Observer\AddCheckoutHandle;
use BlueFairy\OneStepCheckoutHyva\Observer\AddHyvaCheckoutHandle;
use Hyva\Theme\Service\CurrentTheme;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlueFairy\OneStepCheckoutHyva\Observer\AddHyvaCheckoutHandle
 */
class AddHyvaCheckoutHandleTest extends TestCase
{
    /**
     * @param string[] $handles the handles already on the page
     * @return string[] the handles this observer added
     */
    private function added(array $handles, bool $isHyva, bool $withLayout = true): array
    {
        $added = [];
        $update = $this->createMock(ProcessorInterface::class);
        $update->method('getHandles')->willReturn($handles);
        $update->method('addHandle')->willReturnCallback(function ($handle) use (&$added, $update) {
            $added[] = $handle;

            return $update;
        });
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($update);
        $theme = $this->createMock(CurrentTheme::class);
        $theme->method('isHyva')->willReturn($isHyva);

        (new AddHyvaCheckoutHandle($theme))->execute(new Observer(['layout' => $withLayout ? $layout : null]));

        return $added;
    }

    public function testOnAHyvaThemeTheHandleFollowsTheBaseModulesCheckoutHandle(): void
    {
        self::assertSame(
            ['bluefairy_osc_checkout_hyva'],
            $this->added(['default', 'checkout_index_index', AddCheckoutHandle::HANDLE], true)
        );
    }

    public function testNothingIsAddedOnALumaThemeOrWhereTheBaseCheckoutIsNotOnThePage(): void
    {
        self::assertSame([], $this->added(['default', 'checkout_index_index', AddCheckoutHandle::HANDLE], false), 'a Luma store view');
        self::assertSame([], $this->added(['default', 'checkout_index_index'], true), 'One Step Checkout is off, or stood down');
        self::assertSame([], $this->added(['default', 'catalog_product_view'], true), 'another page');
        self::assertSame([], $this->added([AddCheckoutHandle::HANDLE], true, false), 'no layout in the event');
    }

    public function testTheHandleHasALayoutFileAndTheObserverIsRegisteredForTheStorefront(): void
    {
        $root = __DIR__ . '/../../..';
        self::assertFileExists($root . '/view/frontend/layout/' . AddHyvaCheckoutHandle::HANDLE . '.xml');
        $events = (string) file_get_contents($root . '/etc/frontend/events.xml');
        self::assertStringContainsString('<event name="layout_load_before">', $events);
        self::assertStringContainsString(AddHyvaCheckoutHandle::class, $events);
        // The base module must load first, so that its observer has added its handle before this one looks.
        $module = (string) file_get_contents($root . '/etc/module.xml');
        self::assertStringContainsString('<module name="BlueFairy_OneStepCheckout"/>', $module);
    }
}
