<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\TwoFactorAuth\Api\ProviderPoolInterface;
use Magento\TwoFactorAuth\Api\UserConfigManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * 2FA reset commandline
 */
class TfaReset extends Command
{
    /**
     * @var UserConfigManagerInterface
     */
    private $userConfigManager;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var ProviderPoolInterface
     */
    private $providerPool;

    /**
     * @param UserConfigManagerInterface $userConfigManager
     * @param ProviderPoolInterface $providerPool
     * @param UserFactory $userFactory
     * @param User $userResource
     */
    public function __construct(
        UserConfigManagerInterface $userConfigManager,
        ProviderPoolInterface $providerPool,
        UserFactory $userFactory,
        User $userResource
    ) {
        parent::__construct();
        $this->userConfigManager = $userConfigManager;
        $this->userResource = $userResource;
        $this->userFactory = $userFactory;
        $this->providerPool = $providerPool;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('security:tfa:reset');
        $this->setDescription('Reset configuration for one user');

        $this->addArgument('user', InputArgument::REQUIRED, __('Username')->render());
        $this->addArgument('provider', InputArgument::REQUIRED, __('Provider code')->render());

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userName = $input->getArgument('user');
        $providerCode = $input->getArgument('provider');

        $user = $this->userFactory->create();

        $this->userResource->load($user, $userName, 'username');
        if (!$user->getId()) {
            throw new LocalizedException(__('Unknown user %1', $userName));
        }

        $provider = $this->providerPool->getProviderByCode($providerCode);

        $this->userConfigManager->resetProviderConfig((int) $user->getId(), $providerCode);

        $output->writeln('' . __('Provider %1 has been reset for user %2', $provider->getName(), $userName));

        return 0;
    }
}
