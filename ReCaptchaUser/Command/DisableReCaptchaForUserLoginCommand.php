<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUser\Command;

use Magento\ReCaptchaUser\Model\DisableReCaptchaForUserLogin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableReCaptchaForUserLoginCommand extends Command
{
    /**
     * @var DisableReCaptchaForUserLogin
     */
    private $disableReCaptchaForUserLogin;

    /**
     * @param DisableReCaptchaForUserLogin $disableReCaptchaForUserLogin
     */
    public function __construct(
        DisableReCaptchaForUserLogin $disableReCaptchaForUserLogin
    ) {
        parent::__construct();
        $this->disableReCaptchaForUserLogin = $disableReCaptchaForUserLogin;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('security:recaptcha:disable-for-user-login');
        $this->setDescription('Disable reCAPTCHA for admin user login form');

        parent::configure();
    }

    /**
     * @inheritdoc
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->disableReCaptchaForUserLogin->execute();
        return Command::SUCCESS;
    }
}
