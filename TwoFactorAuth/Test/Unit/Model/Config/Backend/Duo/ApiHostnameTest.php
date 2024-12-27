<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\Config\Backend\Duo;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TwoFactorAuth\Model\Config\Backend\Duo\ApiHostname;
use PHPUnit\Framework\TestCase;

class ApiHostnameTest extends TestCase
{
    /**
     * @var ApiHostname
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(ApiHostname::class);
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testBefore($value, $isValid): void
    {
        if (!$isValid) {
            $this->expectException(ValidatorException::class);
        }
        $this->model->setValue($value);
        $this->model->beforeSave();
    }

    public static function valuesDataProvider()
    {
        return [
            ['', true],
            ['foo', false],
            ['123', false],
            ['http://google.com', false],
            ['http://duosecurity.com', false],
            ['http://foo.duosecurity.com', false],
            ['http://foo.duosecurity.com', false],
            ['foo.duosecurity.com', true],
            ['abc123-123dc.duosecurity.com', true],
            ['abc123-123dc.duosecurity.com.foo', false],
            ['abc123/123dc.duosecurity.com.foo', false],
            ['abc123/123dc.duosecurity.com/foo', false],
            ['abc123-123dc.duosecurity.com/foo', false],
        ];
    }
}
