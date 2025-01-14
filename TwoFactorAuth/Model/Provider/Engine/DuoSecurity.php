<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Provider\Engine;

use Duo\DuoUniversal\DuoException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Client|null $client
     * @param DuoAuth|null $duoAuth
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        ?Client $client = null,
        ?DuoAuth $duoAuth = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        if ($this->isDuoForcedProvider()) {
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
        return $this->scopeConfig->getValue(static::XML_PATH_CLIENT_SECRET);
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
     * Verify the user
     *
     * @param UserInterface $user
     * @param DataObject $request
     * @return bool
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function verify(UserInterface $user, DataObject $request): bool
    {
        $duoCode = $request->getData('duo_code');
        $username = $user->getUserName();

        try {
            // Not saving token as this is for verification purpose
            $this->client->exchangeAuthorizationCodeFor2FAResult($duoCode, $username);
        } catch (DuoException $e) {
            return false;
        }
        # Exchange happened successfully so render success page
        return true;
    }

    /**
     * Check if Duo is selected as forced provider
     */
    private function isDuoForcedProvider(): bool
    {
        $providers = $this->scopeConfig->getValue('twofactorauth/general/force_providers') ?? '';
        $forcedProviders = array_map('trim', explode(',', $providers));
        return in_array(self::CODE, $forcedProviders, true);
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        try {
            return $this->isDuoForcedProvider() &&
                !!$this->getApiHostname() &&
                !!$this->getClientId() &&
                !!$this->getClientSecret();
        } catch (\TypeError $exception) {
            //At least one of the methods returned null instead of a string
            return false;
        }
    }

    /**
     * Initiate authentication with Duo Universal Prompt
     *
     * @param string $username
     * @param string $state
     * @return array
     * @throws \Duo\DuoUniversal\DuoException
     */
    public function initiateAuth($username, string $state): array
    {
        try {
            $this->healthCheck();
        } catch (DuoException $e) {
                return [
                    'status' => 'failure',
                    'redirect_url' => '',
                    'message' => __("2FA Unavailable. Confirm Duo client/secret/host values are correct")
                ];
        }

        return [
            'status' => 'success',
            'redirect_url' => $this->client->createAuthUrl($username, $state),
            'message' => __('Duo Auth URL created successfully.')
        ];
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
     * Generate a state for Duo Universal prompt
     *
     * @return string
     */
    public function generateDuoState() : string
    {
        return $this->client->generateState();
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
