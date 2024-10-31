<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUser\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\Plugin\AuthenticationException;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Magento\ReCaptchaValidationApi\Model\ValidationErrorMessagesProvider;
use Psr\Log\LoggerInterface;

/**
 * Observer of login.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginObserver implements ObserverInterface
{
    /**
     * @var CaptchaResponseResolverInterface
     */
    private $captchaResponseResolver;

    /**
     * @var ValidationConfigResolverInterface
     */
    private $validationConfigResolver;

    /**
     * @var ValidatorInterface
     */
    private $captchaValidator;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string
     * @deprecated
     * @see no actuall usage in the class
     */
    private $loginActionName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ErrorMessageConfigInterface|null
     */
    private $errorMessageConfig;

    /**
     * @var ValidationErrorMessagesProvider|null
     */
    private $validationErrorMessagesProvider;

    /**
     * @param CaptchaResponseResolverInterface $captchaResponseResolver
     * @param ValidationConfigResolverInterface $validationConfigResolver
     * @param ValidatorInterface $captchaValidator
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param string $loginActionName
     * @param ErrorMessageConfigInterface|null $errorMessageConfig
     * @param ValidationErrorMessagesProvider|null $validationErrorMessagesProvider
     */
    public function __construct(
        CaptchaResponseResolverInterface $captchaResponseResolver,
        ValidationConfigResolverInterface $validationConfigResolver,
        ValidatorInterface $captchaValidator,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        RequestInterface $request,
        LoggerInterface $logger,
        string $loginActionName,
        ?ErrorMessageConfigInterface $errorMessageConfig = null,
        ?ValidationErrorMessagesProvider $validationErrorMessagesProvider = null
    ) {
        $this->captchaResponseResolver = $captchaResponseResolver;
        $this->validationConfigResolver = $validationConfigResolver;
        $this->captchaValidator = $captchaValidator;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->request = $request;
        $this->loginActionName = $loginActionName;
        $this->logger = $logger;
        $this->errorMessageConfig = $errorMessageConfig
            ?? ObjectManager::getInstance()->get(ErrorMessageConfigInterface::class);
        $this->validationErrorMessagesProvider = $validationErrorMessagesProvider
            ?? ObjectManager::getInstance()->get(ValidationErrorMessagesProvider::class);
    }

    /**
     * @inheritdoc
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $key = 'user_login';
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            $validationConfig = $this->validationConfigResolver->get($key);
            try {
                $reCaptchaResponse = $this->captchaResponseResolver->resolve($this->request);
            } catch (InputException $e) {
                $this->logger->error($e);
                $this->processError([], $key);

                return;
            }

            $validationResult = $this->captchaValidator->isValid($reCaptchaResponse, $validationConfig);
            if (false === $validationResult->isValid()) {
                $this->processError($validationResult->getErrors(), $key);
            }
        }
    }

    /**
     * Process errors from reCAPTCHA response.
     *
     * @param array $errorMessages
     * @param string $sourceKey
     * @return void
     * @throws AuthenticationException
     */
    private function processError(array $errorMessages, string $sourceKey): void
    {
        $validationErrorText = $this->errorMessageConfig->getValidationFailureMessage();
        $technicalErrorText = $this->errorMessageConfig->getTechnicalFailureMessage();

        $message = $errorMessages ? $validationErrorText : $technicalErrorText;

        foreach ($errorMessages as $errorMessageCode => $errorMessageText) {
            if (!$this->isValidationError($errorMessageCode)) {
                $message = $technicalErrorText;
                $this->logger->error(
                    __(
                        'reCAPTCHA \'%1\' form error: %2',
                        $sourceKey,
                        $errorMessageText
                    )
                );
            }
        }

        throw new AuthenticationException(__($message));
    }

    /**
     * Check if error code present in validation errors list.
     *
     * @param string $errorMessageCode
     * @return bool
     */
    private function isValidationError(string $errorMessageCode): bool
    {
        return $errorMessageCode !== $this->validationErrorMessagesProvider->getErrorMessage($errorMessageCode);
    }
}
