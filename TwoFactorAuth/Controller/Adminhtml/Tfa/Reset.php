<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Controller\Adminhtml\Tfa;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User as UserResourceModel;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Controller\Adminhtml\AbstractAction;

/**
 * Reset 2FA configuration controller
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Reset extends AbstractAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var UserResourceModel
     */
    private $userResourceModel;

    /**
     * @var UserFactory
     */
    private $userInterfaceFactory;

    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @param Context $context
     * @param UserResourceModel $userResourceModel
     * @param TfaInterface $tfa
     * @param UserFactory $userFactory
     */
    public function __construct(
        Context $context,
        UserResourceModel $userResourceModel,
        TfaInterface $tfa,
        UserFactory $userFactory
    ) {
        parent::__construct($context);
        $this->userResourceModel = $userResourceModel;
        $this->userInterfaceFactory = $userFactory;
        $this->tfa = $tfa;
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $userId = $this->getRequest()->getParam('id');
        $providerCode = $this->getRequest()->getParam('provider');

        $user = $this->userInterfaceFactory->create();
        $this->userResourceModel->load($user, $userId);

        if (!$user->getId()) {
            throw new LocalizedException(__('Invalid user'));
        }

        $provider = $this->tfa->getProvider($providerCode);
        if (!$provider) {
            throw new LocalizedException(__('Unknown provider'));
        }

        $provider->resetConfiguration((int) $user->getId());

        $this->messageManager->addSuccessMessage(__('Configuration has been reset for this user'));
        return $this->_redirect('adminhtml/user/edit', ['user_id' => $userId]);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && $this->_authorization->isAllowed('Magento_TwoFactorAuth::tfa')
            && $this->_authorization->isAllowed('Magento_User::acl_users');
    }
}
