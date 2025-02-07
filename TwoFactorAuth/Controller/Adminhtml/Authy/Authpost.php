<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Controller\Adminhtml\Authy;

use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\TwoFactorAuth\Model\AlertInterface;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Controller\Adminhtml\AbstractAction;
use Magento\TwoFactorAuth\Model\Provider\Engine\Authy;
use Magento\User\Model\User;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\Framework\App\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Authpost extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var TfaSessionInterface
     */
    private $tfaSession;

    /**
     * @var Authy
     */
    private $authy;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var AlertInterface
     */
    private $alert;

    /**
     * Config path for the 2FA Attempts
     */
    private const XML_PATH_2FA_RETRY_ATTEMPTS = 'twofactorauth/general/twofactorauth_retry';

    /**
     * Config path for the 2FA Attempts
     */
    private const XML_PATH_2FA_LOCK_EXPIRE = 'twofactorauth/general/auth_lock_expire';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @param Action\Context $context
     * @param Session $session
     * @param JsonFactory $jsonFactory
     * @param Authy $authy
     * @param TfaSessionInterface $tfaSession
     * @param TfaInterface $tfa
     * @param AlertInterface $alert
     * @param DataObjectFactory $dataObjectFactory
     * @param UserResource|null $userResource
     * @param ScopeConfigInterface|null $scopeConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        Session $session,
        JsonFactory $jsonFactory,
        Authy $authy,
        TfaSessionInterface $tfaSession,
        TfaInterface $tfa,
        AlertInterface $alert,
        DataObjectFactory $dataObjectFactory,
        ?UserResource $userResource = null,
        ?ScopeConfigInterface $scopeConfig = null
    ) {
        parent::__construct($context);
        $this->tfa = $tfa;
        $this->session = $session;
        $this->jsonFactory = $jsonFactory;
        $this->tfaSession = $tfaSession;
        $this->authy = $authy;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->alert = $alert;
        $this->scopeConfig = $scopeConfig ?? ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->userResource = $userResource ?? ObjectManager::getInstance()->get(UserResource::class);
    }

    /**
     * Get current user
     *
     * @return User|null
     */
    private function getUser(): ?User
    {
        return $this->session->getUser();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $user = $this->getUser();
        $result = $this->jsonFactory->create();

        try {
            if (!$this->allowApiRetries()) { //locked the user
                $lockThreshold = $this->scopeConfig->getValue(self::XML_PATH_2FA_LOCK_EXPIRE);
                if ($this->userResource->lock((int)$user->getId(), 0, $lockThreshold)) {
                    $result->setData(['success' => false, 'message' => "Your account is temporarily disabled."]);
                    return $result;
                }
            }
            $this->authy->verify($user, $this->dataObjectFactory->create([
                'data' => $this->getRequest()->getParams(),
            ]));
            $this->tfaSession->grantAccess();
            $result->setData(['success' => true]);
        } catch (Exception $e) {
            $this->alert->event(
                'Magento_TwoFactorAuth',
                'Authy error',
                AlertInterface::LEVEL_ERROR,
                $this->getUser()->getUserName(),
                $e->getMessage()
            );

            $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        $user = $this->getUser();

        return
            $user &&
            $this->tfa->getProviderIsAllowed((int) $user->getId(), Authy::CODE) &&
            $this->tfa->getProvider(Authy::CODE)->isActive((int) $user->getId());
    }

    /**
     * Check if retry attempt above threshold value
     *
     * @return bool
     */
    private function allowApiRetries() : bool
    {
        $maxRetries = $this->scopeConfig->getValue(self::XML_PATH_2FA_RETRY_ATTEMPTS);
        $verifyAttempts = $this->session->getOtpAttempt();
        $verifyAttempts = $verifyAttempts === null ? 1 : $verifyAttempts+1;
        $this->session->setOtpAttempt($verifyAttempts);
        return  $maxRetries >= $verifyAttempts;
    }
}
