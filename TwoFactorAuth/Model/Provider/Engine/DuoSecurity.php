<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\User\Api\Data\UserInterface;
use Magento\TwoFactorAuth\Api\EngineInterface;
use Duo\DuoUniversal\Client;
use DuoAPI\Auth as DuoAuth;

/**
 * Duo Security engine
 */
class DuoSecurity implements EngineInterface
{
    /**
     * Engine code
     */
    public const CODE = 'duo_security'; // Must be the same as defined in di.xml

    /**
     * Duo request prefix
     */
    public const DUO_PREFIX = 'TX';

    /**
     * Duo auth prefix
     */
    public const AUTH_PREFIX = 'AUTH';

    /**
     * Configuration XML path for enabled flag
     */
    public const XML_PATH_ENABLED = 'twofactorauth/duo/enabled';

    /**
     * Configuration XML path for Client Id
     */
    public const XML_PATH_CLIENT_ID = 'twofactorauth/duo/client_id';

    /**
     * Configuration XML path for Client secret
     */
    public const XML_PATH_CLIENT_SECRET = 'twofactorauth/duo/client_secret';

    /**
     * Configuration XML path for host name
     */
    public const XML_PATH_API_HOSTNAME = 'twofactorauth/duo/api_hostname';

    /**
     * Configuration XML path for integration key
     */
    public const XML_PATH_IKEY = 'two_factor_auth/duo/integration_key';

    /**
     *  Configuration XML path for secret key
     */
    public const XML_PATH_SKEY = 'two_factor_auth/duo/secret_key';

    /**
     * Configuration path for Duo Mode
     */
    public const DUO_FAILMODE = 'twofactorauth/duo/duo_failmode';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var DuoAuth
     */
    private $duoAuth;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param UrlInterface $urlBuilder
     * @param FormKey $formKey
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        UrlInterface $urlBuilder,
        FormKey $formKey
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->urlBuilder = $urlBuilder;
        $this->formKey = $formKey;
        $this->client = new Client(
            $this->getClientId(),
            $this->getClientSecret(), // Replace with your actual client secret
            $this->getApiHostname(), // Replace with your actual API host
            $this->getCallbackUrl()
        );
        $this->duoAuth = new DuoAuth(
            $this->getIkey(),
            $this->getSkey(),
            $this->getApiHostname()
        );
    }

    /**
     * Get API hostname
     *
     * @return string
     */
    public function getApiHostname(): string
    {
        return $this->scopeConfig->getValue(static::XML_PATH_API_HOSTNAME);
    }

    /**
     * Get Client Secret
     *
     * @return string
     */
    private function getClientSecret(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(static::XML_PATH_CLIENT_SECRET));
    }

    /**
     * Get Client Id
     *
     * @return string
     */
    private function getClientId(): string
    {
        return $this->scopeConfig->getValue(static::XML_PATH_CLIENT_ID);
    }

    /**
     * Get Duo Mode
     *
     * @return string
     */
    public function getDuoFailmode(): string
    {
        return strtoupper($this->scopeConfig->getValue(static::DUO_FAILMODE));
    }

    /**
     * Get callback URL
     *
     * @return string
     */
    private function getCallbackUrl(): string
    {
        return $this->urlBuilder->getUrl('tfa/duo/authpost');
    }

    /**
     * Get Integration Key
     *
     * @return string
     */
    private function getIkey(): string
    {
        return $this->scopeConfig->getValue(static::XML_PATH_IKEY) ?? '';
    }

    /**
     * Get Secret Key
     *
     * @return string
     */
    private function getSkey(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(static::XML_PATH_SKEY)) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function verify(UserInterface $user, DataObject $request): bool
    {
        $savedState = $request->getData('state');
        $duoCode = $request->getData('duo_code');
        $username = $user->getUserName();

        if (empty($savedState) || empty($username)) {
            return false;
        }
        if ($this->formKey->getFormKey().'lavijain' != $savedState) {
            return false;
        }

        try {
            $decoded_token = $this->client->exchangeAuthorizationCodeFor2FAResult($duoCode, $username);
        } catch (LocalizedException $e) {
            return false;
        }
        # Exchange happened successfully so render success page
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        try {
            return !!$this->getApiHostname() &&
                !!$this->getClientId() &&
                !!$this->getClientSecret();
        } catch (\TypeError $exception) {
            //At least one of the methods returned null instead of a string
            return false;
        }
    }

    /**
     * Generate URI to redirect to for the Duo prompt.
     *
     * @param string $username
     * @param string $state
     * @return string
     */
    public function initiateAuth($username, string $state): string
    {
        $authUrl = $this->client->createAuthUrl($username, $state);
        return $authUrl;
    }

    /**
     * Health check for Duo
     *
     * @return void
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function healthCheck(): void
    {
        $this->client->healthCheck();
    }

    /**
     * @param $username
     * @param $valid_secs
     * @return mixed
     */
    public function enrollNewUser($username = null, $valid_secs = null) {
        $enrolledUserData =  $this->duoAuth->enroll($username, $valid_secs);
        return $enrolledUserData;
    }

    public function checkAuth($user_id, $ipaddr= null, $trusted_device_token = null, $username = true) {
        $this->duoAuth->preauth($user_id, $ipaddr= null, $trusted_device_token = null, $username = true);
    }

    public function duoAuthorize($user_identifier, $factor, $factor_params, $ipaddr = null, $async = false) {
        return $this->duoAuth->auth($user_identifier, $factor, $factor_params, $ipaddr = null, $async = false);
    }
}
