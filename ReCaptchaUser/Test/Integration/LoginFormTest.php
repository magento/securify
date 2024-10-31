<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUser\Test\Integration;

use Magento\Backend\Model\Auth;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaValidation\Model\Validator;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Should be used "admin/security/use_form_key 0" since \Magento\Backend\Model\UrlInterface is initialized
 * several times (each new instance generates different secret keys)
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class LoginFormTest extends AbstractController
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @var ValidationResult|MockObject
     */
    private $captchaValidationResultMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = $this->_objectManager->get(Auth::class);
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->backendUrl = $this->_objectManager->get(UrlInterface::class);

        $this->captchaValidationResultMock = $this->createMock(ValidationResult::class);
        $captchaValidatorMock = $this->createMock(Validator::class);
        $captchaValidatorMock->expects($this->any())
            ->method('isValid')
            ->willReturn($this->captchaValidationResultMock);
        $this->_objectManager->addSharedInstance($captchaValidatorMock, Validator::class);
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     */
    public function testGetRequestIfReCaptchaIsDisabled(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_backend/type_for/user_login invisible
     */
    public function testGetRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_backend/type_for/user_login invisible
     */
    public function testGetRequestIfReCaptchaIsEnabled(): void
    {
        $this->checkSuccessfulGetResponse(true);
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     */
    public function testPostRequestIfReCaptchaIsDisabled(): void
    {
        $this->checkSuccessfulPostResponse();
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     */
    public function testPostRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulPostResponse();
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     */
    public function testPostRequestWithSuccessfulReCaptchaValidation(): void
    {
        $this->captchaValidationResultMock->expects($this->once())->method('isValid')->willReturn(true);

        $this->checkSuccessfulPostResponse(
            [
                CaptchaResponseResolverInterface::PARAM_RECAPTCHA => 'test',
            ]
        );
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     */
    public function testPostRequestIfReCaptchaParameterIsMissed(): void
    {
        $this->checkFailedPostResponse();
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     */
    public function testPostRequestIfReCaptchaParameterIsMissedWithRef(): void
    {
        $postValues = [];
        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(array_replace_recursive(
                [
                    'form_key' => $this->formKey->getFormKey(),
                    'login' => [
                        'username' => Bootstrap::ADMIN_NAME,
                        'password' => Bootstrap::ADMIN_PASSWORD,
                    ],
                ],
                $postValues
            ));
        $this->dispatch('backend/admin/dashboard/index');
        $this->assertSessionMessages(
            self::equalTo(['Something went wrong with reCAPTCHA. Please contact the store owner.']),
            MessageInterface::TYPE_ERROR
        );
        self::assertFalse($this->auth->isLoggedIn());
    }

    /**
     * @magentoAdminConfigFixture admin/security/use_form_key 0
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/public_key test_public_key
     * @magentoAdminConfigFixture recaptcha_backend/type_invisible/private_key test_private_key
     * @magentoAdminConfigFixture recaptcha_backend/type_for/user_login invisible
     */
    public function testPostRequestWithFailedReCaptchaValidation(): void
    {
        $this->captchaValidationResultMock->expects($this->once())->method('isValid')->willReturn(false);

        $this->checkFailedPostResponse([CaptchaResponseResolverInterface::PARAM_RECAPTCHA => 'test']);
    }

    /**
     * @param bool $shouldContainReCaptcha
     * @return void
     */
    private function checkSuccessfulGetResponse($shouldContainReCaptcha = false): void
    {
        $this->getRequest()->setUri($this->backendUrl->getUrl('admin'));

        $this->dispatch('backend/admin/auth/login');

        if ($this->getResponse()->getHeader('Location')) {
            $this->dispatch($this->getResponse()->getHeader('Location')->uri()->getPath());
        }

        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);

        $shouldContainReCaptcha
            ? $this->assertStringContainsString('admin-recaptcha', $content)
            : $this->assertStringNotContainsString('admin-recaptcha', $content);

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function checkSuccessfulPostResponse(array $postValues = []): void
    {
        $this->makePostRequest($postValues);

        $this->assertRedirect(self::equalTo('backend/admin/index/index'));
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
        self::assertTrue($this->auth->isLoggedIn());
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function checkFailedPostResponse(array $postValues = []): void
    {
        $this->makePostRequest($postValues);

        // Location header is different than in the successful case
        $this->assertRedirect(self::equalTo($this->backendUrl->getUrl('admin')));
        $this->assertSessionMessages(
            self::equalTo(['Something went wrong with reCAPTCHA. Please contact the store owner.']),
            MessageInterface::TYPE_ERROR
        );
        self::assertFalse($this->auth->isLoggedIn());
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function makePostRequest(array $postValues = []): void
    {
        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(array_replace_recursive(
                [
                    'form_key' => $this->formKey->getFormKey(),
                    'login' => [
                        'username' => Bootstrap::ADMIN_NAME,
                        'password' => Bootstrap::ADMIN_PASSWORD,
                    ],
                ],
                $postValues
            ));
        $this->dispatch('backend/admin/index/index');
    }
}
