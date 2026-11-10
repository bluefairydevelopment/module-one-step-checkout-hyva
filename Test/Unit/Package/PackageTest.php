<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\OneStepCheckoutHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * Package-level facts a merchant and the release depend on.
 *
 * @coversNothing
 */
class PackageTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    private function template(): string
    {
        return (string) file_get_contents(self::ROOT . '/view/frontend/templates/checkout/osc-root.phtml');
    }

    private function script(): string
    {
        return (string) file_get_contents(self::ROOT . '/view/frontend/web/js/osc-alpine.js');
    }

    public function testManifestAndRegistrationAgree(): void
    {
        $composer = json_decode((string) file_get_contents(self::ROOT . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('bluefairy/module-one-step-checkout-hyva', $composer['name']);
        self::assertSame(['MIT'], $composer['license']);
        self::assertStringStartsWith('MIT License', (string) file_get_contents(self::ROOT . '/LICENSE.txt'));
        self::assertSame('', $composer['autoload']['psr-4']['BlueFairy\\OneStepCheckoutHyva\\']);
        self::assertArrayHasKey('bluefairy/module-one-step-checkout', $composer['require']);
        // Hyvä theme-module 1.3.x is fatal on Magento 2.4.9, so the package must not allow it.
        self::assertSame('^1.5', $composer['require']['hyva-themes/magento2-theme-module']);
        self::assertStringContainsString("'BlueFairy_OneStepCheckoutHyva'", (string) file_get_contents(self::ROOT . '/registration.php'));
        $module = (string) file_get_contents(self::ROOT . '/etc/module.xml');
        self::assertStringContainsString('<module name="BlueFairy_OneStepCheckout"/>', $module);
        self::assertStringContainsString('<module name="Hyva_Theme"/>', $module);
    }

    public function testTheArchiveLeavesOutDevelopmentTooling(): void
    {
        $attributes = (string) file_get_contents(self::ROOT . '/.gitattributes');
        foreach (['Test/', 'phpunit.xml.dist', 'phpcs.xml.dist', 'phpstan.neon.dist', '.gitattributes'] as $path) {
            self::assertMatchesRegularExpression('#^' . preg_quote($path, '#') . '\s+export-ignore#m', $attributes, $path);
        }
    }

    public function testTheLayoutSwapsTheBaseModulesRootTemplateOnHyvaThemes(): void
    {
        $layout = (string) file_get_contents(self::ROOT . '/view/frontend/layout/hyva_checkout_index_index.xml');
        self::assertStringContainsString(
            '<referenceBlock name="bluefairy.osc.root" template="BlueFairy_OneStepCheckoutHyva::checkout/osc-root.phtml"/>',
            $layout
        );
        self::assertSame(1, substr_count($layout, '<referenceBlock'), 'the layout does nothing else on the checkout page');
    }

    public function testThereIsNoRequireJsKnockoutOrMageInit(): void
    {
        foreach ([$this->template(), $this->script()] as $source) {
            foreach (['data-mage-init', 'x-magento-init', 'require(', 'define(', 'data-bind', 'ko.'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source, $forbidden);
            }
        }
    }

    /**
     * Hyvä's strict CSP uses the Alpine CSP build, which evaluates a bare property or method name and
     * nothing else: no calls with arguments, no operators, no object literals, no x-model, no x-html.
     */
    public function testEveryAlpineAttributeIsABareNameTheComponentDefines(): void
    {
        $template = $this->template();
        $script = $this->script();
        preg_match_all('/\s(x-[a-z:.-]+|:[a-z-]+|@[a-z.-]+)="([^"]*)"/', $template, $matches, PREG_SET_ORDER);
        self::assertGreaterThan(60, count($matches), 'the scan found the Alpine attributes');

        $loopVariables = [];
        foreach ($matches as [, $attribute, $value]) {
            if ($attribute === 'x-for') {
                self::assertMatchesRegularExpression('/^([a-z]+) in ([A-Za-z]+)$/', $value);
                [$variable, , $list] = explode(' ', $value);
                $loopVariables[$variable] = true;
                self::assertMatchesRegularExpression('/\bget ' . $list . '\(\)/', $script, $list);
                continue;
            }
            if ($attribute === 'x-data') {
                self::assertSame('bfOscCheckout', $value);
                continue;
            }
            self::assertNotContains($attribute, ['x-model', 'x-html', 'x-init', 'x-effect'], $attribute . ' is not available or not allowed here');
            self::assertMatchesRegularExpression('/^[A-Za-z]+(\.[A-Za-z_]+)?$/', $value, $attribute . '="' . $value . '" must be a bare name');
            if (str_contains($value, '.')) {
                self::assertArrayHasKey(explode('.', $value)[0], $loopVariables, $value . ' reads a loop item');
                continue;
            }
            self::assertMatchesRegularExpression(
                '/\b(get ' . $value . '\(\)|' . $value . ': function \()/',
                $script,
                $attribute . '="' . $value . '" names nothing the component defines'
            );
        }
        self::assertSame(['agreement' => true, 'item' => true, 'row' => true, 'total' => true], (function (array $v) {
            ksort($v);

            return $v;
        })($loopVariables));
        self::assertStringContainsString("window.Alpine.data('bfOscCheckout', component)", $script);
    }

    public function testThereIsNoInlineScriptAndBothScriptsAreLoadedFromTheirModules(): void
    {
        $template = $this->template();
        // PHP output tags are part of the script tag's attributes: take them out before looking for
        // where the tag ends, or the "->" inside one is read as its end.
        $rendered = (string) preg_replace('/<\?=.*?\?>/s', 'PHP', $template);
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $rendered, $scripts);
        self::assertCount(2, $scripts[0]);
        self::assertSame(['', ''], $scripts[1], 'no inline script: strict CSP would block it');
        self::assertStringContainsString("getViewFileUrl('BlueFairy_OneStepCheckout::js/osc-core.js')", $template);
        self::assertStringContainsString("getViewFileUrl('BlueFairy_OneStepCheckoutHyva::js/osc-alpine.js')", $template);
        self::assertLessThan(strpos($template, 'osc-alpine.js'), strpos($template, 'osc-core.js'), 'the core loads first');
    }

    public function testEveryOutputIsEscaped(): void
    {
        preg_match_all('/<\?=(.*?)\?>/s', $this->template(), $echoes);
        self::assertGreaterThan(30, count($echoes[1]));
        foreach ($echoes[1] as $echo) {
            $echo = trim($echo);
            self::assertMatchesRegularExpression(
                '/^(\$escaper->escape(Html|HtmlAttr|Url)\(|\$block->getChildHtml\(\'address\.(shipping|billing)\'\)|\$viewModel->isAccordionMode\(\) \? \' bf-osc--accordion\' : \'\')/',
                $echo,
                'unescaped output: ' . $echo
            );
        }
    }

    public function testTheOnlyMarkupWrittenFromDataIsTheMerchantsAgreementText(): void
    {
        $script = $this->script();
        self::assertSame(1, preg_match_all('/\binnerHTML\b/', $script), 'one markup write: the server-prepared terms and conditions');
        self::assertSame(0, preg_match_all('/\b(outerHTML|insertAdjacentHTML|document\.write|eval\()/', $script));
    }

    public function testEveryStringTheTemplateShowsIsInTheDictionary(): void
    {
        $dictionary = [];
        $handle = fopen(self::ROOT . '/i18n/en_US.csv', 'r');
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $dictionary[$row[0]] = true;
        }
        fclose($handle);
        preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'\\)/", $this->template(), $m);
        $strings = array_fill_keys($m[1], true);
        self::assertGreaterThan(20, count($strings));
        self::assertSame([], array_keys(array_diff_key($strings, $dictionary)), 'missing from i18n/en_US.csv');
        self::assertSame([], array_keys(array_diff_key($dictionary, $strings)), 'dictionary entries nothing uses');
    }

    public function testNoTicketKeysInShippedCode(): void
    {
        $count = 0;
        $hits = [];
        $walk = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator((string) realpath(self::ROOT), \FilesystemIterator::SKIP_DOTS));
        foreach ($walk as $file) {
            $path = $file->getPathname();
            if (str_contains($path, '/.git/') || str_contains($path, '/Test/') || str_contains($path, '/node_modules/')
                || !preg_match('/\.(php|phtml|xml|js|json|csv)$/', $path)) {
                continue;
            }
            $count++;
            if (preg_match('/\b[A-Z][A-Z0-9]*[A-Z][A-Z0-9]*-\d+\b/', (string) file_get_contents($path), $m)
                && !preg_match('/^(UTF|ISO|SHA|HMAC|PO)-/', $m[0])) {
                $hits[] = $path . ': ' . $m[0];
            }
        }
        self::assertGreaterThan(6, $count, 'the scan must have examined the shipped files');
        self::assertSame([], $hits);
    }
}
