<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiRest\Test\Unit\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Magento\ReCaptchaWebapiRest\Plugin\ValidationOverrider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidationOverriderTest extends TestCase
{
    /**
     * @var ValidationOverrider
     */
    private $model;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userContextMock = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->model = new ValidationOverrider($this->userContextMock);
    }

    public static function getUserContextData(): array
    {
        return [
            'customer' => [UserContextInterface::USER_TYPE_CUSTOMER, 1, true],
            'guest' => [UserContextInterface::USER_TYPE_GUEST, null, true],
            'admin' => [UserContextInterface::USER_TYPE_ADMIN, 1, true],
            'integration' => [UserContextInterface::USER_TYPE_INTEGRATION, 1, false],
        ];
    }

    /**
     * Test for other types of users.
     *
     * @param int $type User type.
     * @param int|null $id User ID.
     * @param bool $executed Whether the original validator will be called.
     * @return void
     * @dataProvider getUserContextData
     */
    public function testForUsers(int $type, ?int $id, bool $executed): void
    {
        $this->userContextMock->method('getUserType')
            ->willReturn($type);
        $this->userContextMock->method('getUserId')
            ->willReturn($id);

        $this->assertEquals($executed, $this->runModel());
    }

    /**
     * Execute the plugin.
     *
     * @return bool Whether the original validator was called.
     */
    private function runModel(): bool
    {
        return !$this->model->aroundIsValid(
            $this->getMockForAbstractClass(ValidatorInterface::class),
            function () {
                return new ValidationResult(['error' => 'some error']);
            },
            'recaptcha',
            $this->getMockForAbstractClass(ValidationConfigInterface::class)
        )->isValid();
    }
}
