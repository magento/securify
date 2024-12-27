<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaWebapiGraphQl\Test\Api;

use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test recaptcha config query
 */
class ReCaptchaV3ConfigTest extends GraphQlAbstract
{
    private const QUERY = <<<QUERY
query {
    recaptchaV3Config {
        is_enabled
        website_key
        minimum_score
        badge_position
        language_code
        failure_message
        forms
        theme
    }
}
QUERY;

    /** @var EncryptorInterface $encryptor */
    private $encryptor;

    /** @var \Magento\Config\Model\Config $config */
    private $config;

    public function setUp(): void
    {
        $this->encryptor = Bootstrap::getObjectManager()->get(EncryptorInterface::class);
        $this->config = Bootstrap::getObjectManager()->get(\Magento\Config\Model\Config::class);
    }

    #[
        Config('recaptcha_frontend/type_recaptcha_v3/score_threshold', 0.75),
        Config('recaptcha_frontend/type_recaptcha_v3/position', 'bottomright'),
        Config('recaptcha_frontend/type_recaptcha_v3/lang', 'en'),
        Config('recaptcha_frontend/type_recaptcha_v3/theme', 'light'),
        Config('recaptcha_frontend/failure_messages/validation_failure_message', 'Test failure message'),
        Config('recaptcha_frontend/type_for/customer_login', 'recaptcha_v3')
    ]
    public function testQueryRecaptchaNoPublicKeyConfigured(): void
    {
        $this->assertEquals(
            [
                'recaptchaV3Config' => [
                    'is_enabled' => false,
                    'website_key' => '',
                    'minimum_score' => 0.75,
                    'badge_position' => 'bottomright',
                    'language_code' => 'en',
                    'failure_message' => 'Test failure message',
                    'forms' => [
                        'CUSTOMER_LOGIN'
                    ],
                    'theme' => 'light'
                ]
            ],
            $this->graphQlQuery(self::QUERY)
        );
    }

    #[
        Config('recaptcha_frontend/type_recaptcha_v3/score_threshold', 0.75),
        Config('recaptcha_frontend/type_recaptcha_v3/position', 'bottomright'),
        Config('recaptcha_frontend/type_recaptcha_v3/lang', 'en'),
        Config('recaptcha_frontend/type_recaptcha_v3/theme', 'light'),
        Config('recaptcha_frontend/failure_messages/validation_failure_message', 'Test failure message')
    ]
    public function testQueryRecaptchaNoFormsConfigured(): void
    {
        $this->config->setDataByPath(
            'recaptcha_frontend/type_recaptcha_v3/public_key',
            $this->encryptor->encrypt('test_public_key')
        );
        $this->config->setDataByPath(
            'recaptcha_frontend/type_recaptcha_v3/private_key',
            $this->encryptor->encrypt('test_private_key')
        );

        $this->config->save();

        $this->assertEquals(
            [
                'recaptchaV3Config' => [
                    'is_enabled' => false,
                    'website_key' => 'test_public_key',
                    'minimum_score' => 0.75,
                    'badge_position' => 'bottomright',
                    'language_code' => 'en',
                    'failure_message' => 'Test failure message',
                    'forms' => [],
                    'theme' => 'light'
                ]
            ],
            $this->graphQlQuery(self::QUERY)
        );
    }

    #[
        Config('recaptcha_frontend/type_recaptcha_v3/score_threshold', 0.75),
        Config('recaptcha_frontend/type_recaptcha_v3/position', 'bottomright'),
        Config('recaptcha_frontend/type_recaptcha_v3/lang', 'en'),
        Config('recaptcha_frontend/type_recaptcha_v3/theme', 'dark'),
        Config('recaptcha_frontend/failure_messages/validation_failure_message', 'Test failure message'),
        Config('recaptcha_frontend/type_for/customer_login', 'recaptcha_v3')
    ]
    public function testQueryRecaptchaConfigured(): void
    {
        $this->config->setDataByPath(
            'recaptcha_frontend/type_recaptcha_v3/public_key',
            $this->encryptor->encrypt('test_public_key')
        );
        $this->config->setDataByPath(
            'recaptcha_frontend/type_recaptcha_v3/private_key',
            $this->encryptor->encrypt('test_private_key')
        );

        $this->config->save();

        $this->assertEquals(
            [
                'recaptchaV3Config' => [
                    'is_enabled' => true,
                    'website_key' => 'test_public_key',
                    'minimum_score' => 0.75,
                    'badge_position' => 'bottomright',
                    'language_code' => 'en',
                    'failure_message' => 'Test failure message',
                    'forms' => [
                        'CUSTOMER_LOGIN'
                    ],
                    'theme' => 'dark'
                ]
            ],
            $this->graphQlQuery(self::QUERY)
        );
    }

    public function tearDown(): void
    {
        /** @var ResourceConnection $resource */
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        /** @var AdapterInterface $connection */
        $connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $connection->delete(
            $resource->getTableName('core_config_data')
        );
        parent::tearDown();
    }
}
