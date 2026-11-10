<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\OneStepCheckoutHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Every image in docs/images ships in the package, the guide PDF, the store's product gallery and
 * its FAQ page, and nothing in between asks what is in it. docs/images/REVIEWED.md records the
 * SHA-256 of the exact bytes somebody opened and read; this test fails when an image changed,
 * was added without an entry, or was removed without the record being updated.
 *
 * It proves somebody claimed to have looked, not that they looked carefully.
 */
class ShippedImagesAreReviewedTest extends TestCase
{
    private static function packageRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /** @return array<string, string> filename => sha256 */
    private static function reviewed(): array
    {
        $path = self::packageRoot() . '/docs/images/REVIEWED.md';
        $text = is_file($path) ? file_get_contents($path) : false;
        if ($text === false || trim($text) === '') {
            throw new RuntimeException("the review record is missing or empty at {$path}. Every "
                . 'assertion below would pass over an empty expectation set, which is exactly '
                . 'the state that let four unreviewed images ship.');
        }

        // | `name.png` | 1234 | `<hash>…` | note |
        preg_match_all('/^\|\s*`([^`]+\.png)`\s*\|\s*(\d+)\s*\|\s*`([0-9a-f]{16})/mi', $text, $m, PREG_SET_ORDER);

        $reviewed = [];
        foreach ($m as $row) {
            $reviewed[$row[1]] = $row[3];
        }

        return $reviewed;
    }

    /** @return array<string, string> filename => sha256 prefix of the bytes on disk */
    private static function onDisk(): array
    {
        $found = glob(self::packageRoot() . '/docs/images/*.png') ?: [];
        if ($found === []) {
            throw new RuntimeException('no images found under docs/images - the comparison would '
                . 'be empty on both sides and report agreement');
        }

        $actual = [];
        foreach ($found as $file) {
            $actual[basename($file)] = substr(hash_file('sha256', $file), 0, 16);
        }

        return $actual;
    }

    public function testEveryShippedImageHasBeenReviewedAtItsCurrentBytes(): void
    {
        $reviewed = self::reviewed();
        $actual = self::onDisk();

        self::assertNotEmpty($reviewed, 'REVIEWED.md lists no images, so nothing is attested');
        self::assertGreaterThanOrEqual(
            2,
            count($actual),
            'fewer images on disk than this package is known to ship - a clean result here would '
            . 'mean the glob lost its subject rather than that the images agree'
        );

        $unreviewed = array_values(array_diff(array_keys($actual), array_keys($reviewed)));
        $stale = array_values(array_diff(array_keys($reviewed), array_keys($actual)));

        self::assertSame([], $unreviewed, 'image(s) ship with no entry in docs/images/REVIEWED.md, '
            . 'so nobody has recorded opening them: ' . implode(', ', $unreviewed)
            . '. Open the file, look at it, and add a row saying what is in it.');
        self::assertSame([], $stale, 'REVIEWED.md lists image(s) that are no longer in the '
            . 'package: ' . implode(', ', $stale) . '. Remove the row, or restore the file.');

        $changed = [];
        foreach ($actual as $name => $hash) {
            if ($reviewed[$name] !== $hash) {
                $changed[] = $name . ' (reviewed ' . $reviewed[$name] . ', on disk ' . $hash . ')';
            }
        }

        self::assertSame(
            [],
            $changed,
            'image(s) have changed since they were reviewed: ' . implode('; ', $changed)
            . '. The recorded hash is the bytes somebody looked at; different bytes have not '
            . 'been looked at. Re-open them and update REVIEWED.md.'
        );
    }

    /**
     * The control. The comparison must catch a hash that does not match - otherwise its
     * silence over the current set means nothing.
     */
    public function testTheHashComparisonCatchesAChangedImage(): void
    {
        $reviewed = self::reviewed();
        $name = array_key_first($reviewed);

        self::assertNotNull($name, 'no reviewed image to mutate');

        $mutated = self::onDisk();
        $mutated[$name] = str_repeat('0', 16);

        $changed = [];
        foreach ($mutated as $file => $hash) {
            if (($reviewed[$file] ?? null) !== $hash) {
                $changed[] = $file;
            }
        }

        self::assertSame([$name], $changed, 'the comparison must flag exactly the changed file');
    }

    /** And it must catch an image added with no review entry. */
    public function testTheComparisonCatchesAnUnreviewedAddition(): void
    {
        $actual = self::onDisk();
        $actual['bf-never-reviewed.png'] = str_repeat('a', 16);

        $unreviewed = array_values(array_diff(array_keys($actual), array_keys(self::reviewed())));

        self::assertSame(['bf-never-reviewed.png'], $unreviewed);
    }

    /**
     * The record has to say what is IN each image, not merely name it. A row with no
     * description is a hash with no review behind it.
     */
    public function testEveryReviewRowDescribesWhatIsInTheImage(): void
    {
        $text = (string) file_get_contents(self::packageRoot() . '/docs/images/REVIEWED.md');
        preg_match_all('/^\|\s*`([^`]+\.png)`\s*\|[^|]*\|[^|]*\|([^|]*)\|/mi', $text, $m, PREG_SET_ORDER);

        self::assertNotEmpty($m, 'no review rows parsed, so this test examined nothing');

        $empty = [];
        foreach ($m as $row) {
            if (strlen(trim($row[2])) < 20) {
                $empty[] = $row[1];
            }
        }

        self::assertSame([], $empty, 'review row(s) name an image without saying what is in it: '
            . implode(', ', $empty));
    }
}
