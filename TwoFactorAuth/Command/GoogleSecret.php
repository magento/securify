<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\TwoFactorAuth\Api\UserConfigManagerInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Google;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * 2FA reset commandline
 */
class GoogleSecret extends Command
{
    /**
     * @var User
     */
    private $userResource;

    /**
     * @var UserFactory
     */
    private $userFactory;
    /**
     * @var Google
     */
    private $google;

    /**
     * @var UserConfigManagerInterface
     */
    private $configManager;

    /**
     * @param UserFactory $userFactory
     * @param User $userResource
     * @param Google $google
     * @param UserConfigManagerInterface $configManager
     */
    public function __construct(
        UserFactory $userFactory,
        User $userResource,
        Google $google,
        UserConfigManagerInterface $configManager
    ) {
        parent::__construct();
        $this->userResource = $userResource;
        $this->userFactory = $userFactory;
        $this->google = $google;
        $this->configManager = $configManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('security:tfa:google:set-secret');
        $this->setDescription('Set the secret used for Google OTP generation.');

        $this->addArgument('user', InputArgument::REQUIRED, __('Username')->render());
        $this->addArgument('secret', InputArgument::REQUIRED, __('Secret')->render());

        parent::configure();
    }

    /**
     * Set the secret used for google otp generation
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userName = $input->getArgument('user');
        $secret = $input->getArgument('secret');

        $user = $this->userFactory->create();

        $this->userResource->load($user, $userName, 'username');
        if (!$user->getId()) {
            throw new LocalizedException(__('Unknown user %1', $userName));
        }

        $this->google->setSharedSecret((int)$user->getId(), $secret);
        $this->configManager->addProviderConfig(
            (int)$user->getId(),
            Google::CODE,
            [
                UserConfigManagerInterface::ACTIVE_CONFIG_KEY => true
            ]
        );

        $output->writeln((string)__('Google OTP secret has been set'));
        return Command::SUCCESS;
    }
}
