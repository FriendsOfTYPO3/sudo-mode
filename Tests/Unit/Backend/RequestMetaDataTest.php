<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Tests\Unit\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use FriendsOfTYPO3\SudoMode\Backend\RequestMetaData;
use FriendsOfTYPO3\SudoMode\Tests\TestingTrait;
use PHPUnit\Framework\TestCase;

class RequestMetaDataTest extends TestCase
{
    use TestingTrait;

    public function stringValueDataProvider(): array
    {
        return [
            [$this->generateTestString()],
        ];
    }

    /**
     * @param $returnUrl
     *
     * @test
     * @dataProvider stringValueDataProvider
     */
    public function canRetrieveReturnUrl(string $returnUrl): void
    {
        $subject = new RequestMetaData();
        $subject = $subject->withReturnUrl($returnUrl);
        self::assertEquals($returnUrl, $subject->getReturnUrl());
    }

    /**
     * @param $returnUrl
     *
     * @test
     * @dataProvider stringValueDataProvider
     */
    public function isJsonEncoded(string $returnUrl): void
    {
        $subject = new RequestMetaData();
        $subject = $subject->withReturnUrl($returnUrl);
        self::assertEquals(
            sprintf('{"returnUrl":"%s"}', $returnUrl),
            json_encode($subject)
        );
    }
}
