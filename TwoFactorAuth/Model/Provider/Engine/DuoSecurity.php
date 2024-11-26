<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\User\Api\Data\UserInterface;
use Magento\TwoFactorAuth\Api\EngineInterface;
use Duo\DuoUniversal\Client;
use DuoAPI\Auth as DuoAuth;

/**
 * Duo Security engine
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
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
     * Duo auth suffix
     */
    public const AUTH_SUFFIX = 'DUOAUTH';

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
    public const XML_PATH_IKEY = 'twofactorauth/duo/integration_key';

    /**
     *  Configuration XML path for secret key
     */
    public const XML_PATH_SKEY = 'twofactorauth/duo/secret_key';

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
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param UrlInterface $urlBuilder
     * @param FormKey $formKey
     * @param SessionManagerInterface $session
     * @param Client|null $client
     * @param DuoAuth|null $duoAuth
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        UrlInterface $urlBuilder,
        FormKey $formKey,
        SessionManagerInterface $session,
        Client $client = null,
        DuoAuth $duoAuth = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->urlBuilder = $urlBuilder;
        $this->formKey = $formKey;
        $this->session = $session;
        $this->client = $client ?? new Client(
            $this->getClientId(),
            $this->getClientSecret(),
            $this->getApiHostname(),
            $this->getCallbackUrl()
        );
        $this->duoAuth = $duoAuth ?? new DuoAuth(
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
        return $this->scopeConfig->getValue(static::XML_PATH_IKEY);
    }

    /**
     * Get Secret Key
     *
     * @return string
     */
    private function getSkey(): string
    {
        return $this->scopeConfig->getValue(static::XML_PATH_SKEY);
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
        if ($this->formKey->getFormKey().static::AUTH_SUFFIX != $savedState) {
            return false;
        }

        try {
            $decoded_token = $this->client->exchangeAuthorizationCodeFor2FAResult($duoCode, $username);
            // Save the token in the session for later use
            $this->session->setData('duo_token', $decoded_token);
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
     * Generate URI to redirect to for the Duo Universal prompt.
     *
     * @param string $username
     * @param string $state
     * @return string
     */
    public function initiateAuth($username, string $state): string
    {
        return $this->client->createAuthUrl($username, $state);
    }

    /**
     * Health check for Duo Universal prompt.
     *
     * @return void
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function healthCheck(): void
    {
        $this->client->healthCheck();
    }

    /**
     * Enroll a new user for Duo Auth API.
     *
     * @param string|null $username
     * @param int|null $validSecs
     * @return mixed
     */
    public function enrollNewUser($username = null, $validSecs = null)
    {
        return $this->duoAuth->enroll($username, $validSecs);
    }

    /**
     * Check authentication for Duo Auth API.
     *
     * @param string $userIdentifier
     * @param string|null $ipAddr
     * @param string|null $trustedDeviceToken
     * @param bool $username
     * @return string
     */
    public function assertUserIsValid($userIdentifier, $ipAddr = null, $trustedDeviceToken = null, $username = true)
    {
        $response =  $this->duoAuth->preauth($userIdentifier, $ipAddr, $trustedDeviceToken, $username);
        return $response['response']['response']['result'];
    }

    /**
     * Authorize a user with Duo Auth API.
     *
     * @param string $userIdentifier
     * @param string $factor
     * @param array $factorParams
     * @param string|null $ipAddr
     * @param bool $async
     * @return array
     */
    public function authorizeUser($userIdentifier, $factor, $factorParams, $ipAddr = null, $async = false)
    {
        $response = $this->duoAuth->auth($userIdentifier, $factor, $factorParams, $ipAddr, $async);
        return [
            'status' => $response['response']['response']['status'],
            'msg' => $response['response']['response']['status_msg']
        ];
    }
}
