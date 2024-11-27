<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Test\Unit\Model\UserConfig;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\User;
use Magento\TwoFactorAuth\Api\UserConfigTokenManagerInterface;
use Magento\TwoFactorAuth\Model\UserConfig\HtmlAreaTokenVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Backend\Model\Session as SessionManager;
use PHPUnit\Framework\TestCase;

class HtmlAreaTokenVerifierTest extends TestCase
{
    /**
     * @var HtmlAreaTokenVerifier
     */
    private $model;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var UserConfigTokenManagerInterface|MockObject
     */
    private $tokenManagerMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookiesMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookiesMetaFactoryMock;

    /**
     * @var Session|MockObject
     */
    private $sessionMock;

    /**
     * @var SessionManager|MockObject
     */
    private $sessionManagerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->tokenManagerMock = $this->getMockForAbstractClass(UserConfigTokenManagerInterface::class);
        $this->cookiesMock = $this->getMockForAbstractClass(CookieManagerInterface::class);
        $this->cookiesMetaFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionManagerMock = $this->getMockBuilder(SessionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(
            HtmlAreaTokenVerifier::class,
            [
                'request' => $this->requestMock,
                'tokenManager' => $this->tokenManagerMock,
                'cookies' => $this->cookiesMock,
                'cookieMetadataFactory' => $this->cookiesMetaFactoryMock,
                'session' => $this->sessionMock,
                'sessionManager' => $this->sessionManagerMock
            ]
        );
    }

    /**
     * Request/cookies data sets.
     *
     * @return array
     */
    public static function getTokenRequestData(): array
    {
        return [
            'token in query' => [
                true,
                'token',
                null,
                true,
                true,
                'token'
            ],
            'token in cookies' => [
                true,
                null,
                'token',
                true,
                false,
                'token'
            ],
            'token is absent' => [
                true,
                null,
                null,
                false,
                false,
                null
            ],
            'invalid token' => [
                true,
                'token',
                null,
                false,
                false,
                null
            ],
            'invalid token from cookies' => [
                true,
                null,
                'token',
                false,
                false,
                null
            ],
            'token in both' => [
                true,
                'token',
                'token',
                true,
                false,
                'token'
            ],
            'no user' => [
                false,
                'token',
                'token',
                true,
                false,
                null
            ]
        ];
    }

    /**
     * Test "readConfigToken" method with different variation of request/cookie parameters provided.
     *
     * @param bool $userPresent
     * @param string|null $fromRequest
     * @param string|null $fromCookies
     * @param bool $isValid
     * @param bool $cookieSet
     * @param string|null $expected
     * @return void
     * @dataProvider getTokenRequestData
     */
    public function testReadConfigToken(
        bool $userPresent,
        ?string $fromRequest,
        ?string $fromCookies,
        bool $isValid,
        bool $cookieSet,
        ?string $expected
    ): void {
        $this->sessionMock->method('__call')
            ->willReturn(
                $userPresent ? $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock() : null
            );
        $this->requestMock->method('getParam')->with('tfat')->willReturn($fromRequest);
        $this->cookiesMock->method('getCookie')->with('tfa-ct')->willReturn($fromCookies);
        $this->tokenManagerMock->method('isValidFor')->willReturn($isValid);
        $this->sessionManagerMock->method('getCookiePath')->willReturn('admin_path');
        $this->cookiesMetaFactoryMock->method('createSensitiveCookieMetadata')
            ->willReturn(
                $metaMock = $this->getMockBuilder(SensitiveCookieMetadata::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );
        if ($cookieSet) {
            $metaMock->expects($this->atLeastOnce())->method('setPath')->with('admin_path')->willReturn($metaMock);
            $this->cookiesMock->expects($this->once())
                ->method('setSensitiveCookie')
                ->with('tfa-ct', $fromRequest, $metaMock);
        } else {
            $this->cookiesMock->expects($this->never())->method('setSensitiveCookie');
        }

        $this->assertEquals($expected, $this->model->readConfigToken());
    }
}
