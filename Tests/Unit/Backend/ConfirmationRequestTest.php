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

use FriendsOfTYPO3\SudoMode\Backend\ConfirmationRequest;
use FriendsOfTYPO3\SudoMode\Tests\TestingTrait;
use PHPUnit\Framework\TestCase;

class ConfirmationRequestTest extends TestCase
{
    use TestingTrait;

    public function constructorValueDataProvider(): array
    {
        return [
            [
                ConfirmationRequest::TYPE_TABLE_NAME,
                [$this->generateTestString()],
                $this->generateTestString(),
                $this->generateTestInteger()
            ],
        ];
    }

    /**
     * @param string $type
     * @param array $subjects
     * @param string $identifier
     * @param int $expirationTimestamp
     *
     * @test
     * @dataProvider constructorValueDataProvider
     */
    public function canRetrieveProperties(string $type, array $subjects, string $identifier, int $expirationTimestamp): void
    {
        $subject = new ConfirmationRequest($type, $subjects, $identifier, $expirationTimestamp);
        self::assertEquals($type, $subject->getType());
        self::assertEquals($subjects, $subject->getSubjects());
        self::assertEquals($identifier, $subject->getIdentifier());
        self::assertEquals($expirationTimestamp, $subject->getExpirationTimestamp());
    }

    /**
     * @param string $type
     * @param array $subjects
     * @param string $identifier
     * @param int $expirationTimestamp
     *
     * @test
     * @dataProvider constructorValueDataProvider
     */
    public function isJsonEncoded(string $type, array $subjects, string $identifier, int $expirationTimestamp): void
    {
        $subject = new ConfirmationRequest($type, $subjects, $identifier, $expirationTimestamp);
        self::assertEquals(
            sprintf(
                '{"type":"%s","subjects":["%s"],"identifier":"%s","expirationTimestamp":%d}',
                $type,
                $subjects[0],
                $identifier,
                $expirationTimestamp
            ),
            json_encode($subject)
        );
    }
}
