<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace Magento\NotifierEvent\Test\Integration\Rule\Validator;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\NotifierEvent\Model\Rule\Validator\ValidateEvent;
use Magento\NotifierEventApi\Api\Data\RuleInterface;
use PHPUnit\Framework\TestCase;

class ValidateEventTest extends TestCase
{
    /**
     * @var ValidateEvent
     */
    private $subject;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->get(ValidateEvent::class);
    }

    /**
     * @return array
     */
    public function invalidDataProvider(): array
    {
        return [
            [
                'data' => [
                    'events' => ''
                ],
                'errorMessage' => 'You must specify at least one event'
            ],
            [
                'data' => [
                    'events' => 'invalid content'
                ],
                'errorMessage' => 'Invalid events data format'
            ],
            [
                'data' => [
                    'events' => '[]'
                ],
                'errorMessage' => 'You must specify at least one event'
            ]
        ];
    }

    /**
     * @return array
     */
    public function validDataProvider(): array
    {
        return [
            [
                'data' => [
                    'events' => '["event"]'
                ]
            ],
            [
                'data' => [
                    'events' => '["event1", "event2"]'
                ]
            ]
        ];
    }

    /**
     * @param array $data
     * @param string $errorMessage
     * @dataProvider invalidDataProvider
     */
    public function testShouldTriggerValidationException(array $data, string $errorMessage): void
    {
        $channel = $this->objectManager->create(
            RuleInterface::class,
            [
                'data' => $data
            ]
        );

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($errorMessage);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->subject->execute($channel);
    }

    /**
     * @param array $data
     * @dataProvider validDataProvider
     */
    public function testShouldValidateChannel(array $data): void
    {
        $channel = $this->objectManager->create(
            RuleInterface::class,
            [
                'data' => $data
            ]
        );

        $this->subject->execute($channel);
    }
}
