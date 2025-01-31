<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaUser\Command;

use Magento\Framework\Console\Cli;
use Magento\ReCaptchaUser\Model\DisableReCaptchaForUserForgotPassword;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableReCaptchaForUserForgotPasswordCommand extends Command
{
    /**
     * @var DisableReCaptchaForUserForgotPassword
     */
    private $disableReCaptchaForUserForgotPassword;

    /**
     * @param DisableReCaptchaForUserForgotPassword $disableReCaptchaForUserForgotPassword
     */
    public function __construct(
        DisableReCaptchaForUserForgotPassword $disableReCaptchaForUserForgotPassword
    ) {
        parent::__construct();
        $this->disableReCaptchaForUserForgotPassword = $disableReCaptchaForUserForgotPassword;
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('security:recaptcha:disable-for-user-forgot-password');
        $this->setDescription('Disable reCAPTCHA for admin user forgot password form');

        parent::configure();
    }

    /**
     * @inheritdoc
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disableReCaptchaForUserForgotPassword->execute();

        return Cli::RETURN_SUCCESS;
    }
}
