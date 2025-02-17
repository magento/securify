<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWishlist\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;

/**
 * Adds Captcha support for share wishlist
 */
class ShareWishlistObserver implements ObserverInterface
{
    /**
     * @var string Captcha key
     */
    private const CAPTCHA_KEY = 'wishlist';

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @param RedirectInterface $redirect
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestHandlerInterface $requestHandler
     */
    public function __construct(
        RedirectInterface $redirect,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        RequestHandlerInterface $requestHandler
    ) {
        $this->redirect = $redirect;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->requestHandler = $requestHandler;
    }

    /**
     * @inheritdoc
     * @param Observer $observer
     * @return void
     * @throws InputException
     */
    public function execute(Observer $observer): void
    {
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor(self::CAPTCHA_KEY)) {
            /** @var Action $controller */
            $controller = $observer->getControllerAction();
            $request = $controller->getRequest();
            $response = $controller->getResponse();
            $redirectOnFailureUrl = $this->redirect->getRefererUrl();

            $this->requestHandler->execute(self::CAPTCHA_KEY, $request, $response, $redirectOnFailureUrl);
        }
    }
}
