<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWishlist\Test\Integration;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaValidation\Model\Validator;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for create wish list
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class ShareWishlistFormTest extends AbstractController
{
    /**
     * @var string Customer ID
     */
    private const CUSTOMER_ID = 1;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var string
     */
    private $formKey;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var int
     */
    private $wishlistId;

    /**
     * @var ValidationResult|MockObject
     */
    private $captchaValidationResultMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formKey = $this->_objectManager->get(FormKey::class)->getFormKey();
        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->customerSession->setCustomerId(self::CUSTOMER_ID);
        $this->wishlistId = $this->_objectManager->get(GetWishlistByCustomerId::class)
            ->execute(self::CUSTOMER_ID)
            ->getId();
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->captchaValidationResultMock = $this->createMock(ValidationResult::class);
        $captchaValidatorMock = $this->createMock(Validator::class);
        $captchaValidatorMock->expects($this->any())
            ->method('isValid')
            ->willReturn($this->captchaValidationResultMock);
        $this->_objectManager->addSharedInstance($captchaValidatorMock, Validator::class);
    }

    /**
     * Checks the content of the 'Wish List Sharing' page when ReCaptcha is disabled
     */
    public function testGetRequestIfReCaptchaIsDisabled(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * Checks the content of the 'Wish List Sharing' page when ReCaptcha is enabled
     * but keys are not configured
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testGetRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * Checks the content of the 'Wish List Sharing' page when ReCaptcha is enabled
     * and keys are configured
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testGetRequestIfReCaptchaIsEnabled(): void
    {
        $this->checkSuccessfulGetResponse(true);
    }

    /**
     * Checks GET response
     *
     * @param bool $shouldContainReCaptcha
     * @return void
     */
    private function checkSuccessfulGetResponse(bool $shouldContainReCaptcha = false): void
    {
        $this->dispatch('wishlist/index/share/wishlist_id/' . $this->wishlistId);
        $content = $this->getResponse()->getBody();

        $this->assertNotEmpty($content);

        $shouldContainReCaptcha
            ? $this->assertStringContainsString('field-recaptcha', $content)
            : $this->assertStringNotContainsString('field-recaptcha', $content);

        $this->assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * Checks the sharing process without ReCaptcha validation
     */
    public function testPostRequestWithoutReCaptchaValidation(): void
    {
        $this->checkSuccessfulPostRequest();
    }

    /**
     * Checks the sharing process if ReCaptcha is enabled but keys are not configured
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testPostRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulPostRequest();
    }

    /**
     * Checks the successful sharing process with ReCaptcha validation
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testPostRequestWithSuccessfulReCaptchaValidation(): void
    {
        $this->captchaValidationResultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->checkSuccessfulPostRequest(true);
    }

    /**
     * Checks successful sharing process
     *
     * @param bool $withParamReCaptcha
     */
    private function checkSuccessfulPostRequest(bool $withParamReCaptcha = false):void
    {
        $this->makePostRequest($withParamReCaptcha);
        $url = $this->url->getRouteUrl('wishlist/index/index/wishlist_id/' . $this->wishlistId . '/');
        $this->assertRedirect(self::equalTo($url));
        $this->assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * Checks the sharing process with ReCaptcha validation when `g-recaptcha-response` missed
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testPostRequestIfReCaptchaParameterIsMissed(): void
    {
        $this->checkFailedPostRequest();
    }

    /**
     * Checks the failed sharing process with ReCaptcha validation
     *
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/wishlist invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/wishlist invisible
     */
    public function testPostRequestWithFailedReCaptchaValidation(): void
    {
        $this->captchaValidationResultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->checkFailedPostRequest(true);
    }

    /**
     * Checks failed sharing process
     *
     * @param bool $withParamReCaptcha
     */
    private function checkFailedPostRequest(bool $withParamReCaptcha = false): void
    {
        $this->makePostRequest($withParamReCaptcha);
        $this->assertSessionMessages(
            $this->equalTo(['Something went wrong with reCAPTCHA. Please contact the store owner.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Makes post request
     *
     * @param bool $withParamReCaptcha
     * @return void
     */
    private function makePostRequest(bool $withParamReCaptcha = false): void
    {
        $postValue = [
            'form_key' => $this->formKey,
            'emails' => 'example1@gmail.com, example2@gmail.com, example3@gmail.com',
        ];

        if ($withParamReCaptcha) {
            $postValue[CaptchaResponseResolverInterface::PARAM_RECAPTCHA] = 'test';
        }

        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue($postValue);

        $this->dispatch('wishlist/index/send/wishlist_id/' . $this->wishlistId);
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        $this->customerSession->setCustomerId(null);
        parent::tearDown();
    }
}
