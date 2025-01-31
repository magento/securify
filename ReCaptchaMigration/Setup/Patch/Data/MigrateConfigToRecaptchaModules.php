<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaMigration\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Migrate config from frontend and backend scopes to reCAPTCHA modules.
 */
class MigrateConfigToRecaptchaModules implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer,
        EncryptorInterface $encryptor
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $scopes = ['frontend', 'backend'];
        foreach ($scopes as $scope) {
            $this->copyRecaptchaKeys($scope);
            $this->copyModuleSpecificRecords($scope);
            $this->copyEnabledRecaptcha($scope);
            $this->disableLegacyRecaptcha($scope);
        }

        return $this;
    }

    /**
     * Copy 'enabled' reCAPTCHA.
     *
     * @param string $scope
     */
    private function copyEnabledRecaptcha(string $scope): void
    {
        $type = $this->getActiveRecaptchaType();
        if (!$type) {
            return;
        }

        $availableRecaptchaPreferences = $this->getAvailableRecaptchaPreferences();
        foreach ($availableRecaptchaPreferences[$scope] as $availablePreference => $legacyPreference) {
            $availableRecaptchaPreferencePath = "recaptcha_$scope/type_for/$availablePreference";
            $recaptchaPreferenceEnabled = $this->scopeConfig->getValue($availableRecaptchaPreferencePath);
            $recaptchaPreferenceEnabledLegacy = $this->scopeConfig->getValue(
                "msp_securitysuite_recaptcha/$scope/enabled$legacyPreference"
            );
            if (null === $recaptchaPreferenceEnabled && '0' !== $recaptchaPreferenceEnabledLegacy) {
                $this->writer->save($availableRecaptchaPreferencePath, $type);
            }
        }
    }

    /**
     * Disable legacy reCAPTCHA module to prevent multiple widget rendering.
     *
     * @param string $scope
     */
    private function disableLegacyRecaptcha(string $scope): void
    {
        $this->writer->save("msp_securitysuite_recaptcha/$scope/enabled", 0);
    }

    /**
     * Copy reCAPTCHA keys.
     *
     * @param string $scope
     */
    private function copyRecaptchaKeys(string $scope): void
    {
        $type = $this->getActiveRecaptchaType();
        if ($type) {
            $publicKey = $this->scopeConfig->getValue(
                "recaptcha_$scope/type_$type/public_key"
            );
            $publicKeyLegacy = $this->scopeConfig->getValue(
                'msp_securitysuite_recaptcha/general/public_key'
            );
            if (!$publicKey && $publicKeyLegacy) {
                $publicKeyEncrypted = $this->encryptor->encrypt($publicKeyLegacy);
                $this->writer->save("recaptcha_$scope/type_$type/public_key", $publicKeyEncrypted);
            }
            $privateKey = $this->scopeConfig->getValue(
                "recaptcha_$scope/type_$type/private_key"
            );
            $privateKeyLegacy = $this->scopeConfig->getValue(
                'msp_securitysuite_recaptcha/general/private_key'
            );
            if (!$privateKey && $privateKeyLegacy) {
                $privateKeyEncrypted = $this->encryptor->encrypt($privateKeyLegacy);
                $this->writer->save("recaptcha_$scope/type_$type/private_key", $privateKeyEncrypted);
            }
        }
    }

    /**
     * Copy module-specific records.
     *
     * @param string $scope
     */
    private function copyModuleSpecificRecords(string $scope): void
    {
        foreach ($this->getModuleSpecificRecords() as $module => $specificRecords) {
            foreach ($specificRecords as $actualRecord => $legacyRecord) {
                $this->copyRecord(
                    "msp_securitysuite_recaptcha/$scope/$legacyRecord",
                    "recaptcha_$scope/type_$module/$actualRecord"
                );
            }
        }
    }

    /**
     * Get module-specific records.
     *
     * @return array
     */
    private function getModuleSpecificRecords(): array
    {
        return [
            'recaptcha' => [
                'theme' => 'theme',
                'lang' => 'lang',
                'size' => 'size'
            ],
            'invisible' => [
                'theme' => 'theme',
                'lang' => 'lang',
                'position' => 'position'
            ],
            'recaptcha_v3' => [
                'theme' => 'theme',
                'lang' => 'lang',
                'score_threshold' => 'min_score',
                'position' => 'position'],
        ];
    }

    /**
     * Get available recaptcha preferences.
     *
     * @return array
     */
    private function getAvailableRecaptchaPreferences(): array
    {
        return [
            'frontend' => [
                'customer_login' => '_login',
                'customer_forgot_password' => '_forgot',
                'customer_create' => '_create',
                'contact' => '_contact',
                'product_review' => '_review',
                'newsletter' => '_newsletter',
                'sendfriend' => '_sendfriend',
            ],
            'backend' => [
                'user_login' => '',
                'user_forgot_password' => '',
            ],
        ];
    }

    /**
     * Get active reCAPTCHA type from config (recaptcha/scope/type).
     *
     * @return string|null
     */
    private function getActiveRecaptchaType(): ?string
    {
        return $this->scopeConfig->getValue('msp_securitysuite_recaptcha/general/type');
    }

    /**
     * Copy one config record.
     *
     * Skip if record on destination path already exists.
     *
     * @param string $srcPath
     * @param string $dstPath
     */
    private function copyRecord(string $srcPath, string $dstPath): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configDataTable = $this->moduleDataSetup->getTable('core_config_data');

        $dstSelect = $connection->select()
            ->from($configDataTable)
            ->where('path = ?', $dstPath);

        if (!$connection->fetchOne($dstSelect)) {
            $srcSelect = $connection->select()
                ->from($configDataTable)
                ->where('path = ?', $srcPath);

            $rows = $connection->fetchAll($srcSelect);
            foreach ($rows as $row) {
                unset($row['config_id']);
                $row['path'] = $dstPath;

                $connection->insert($configDataTable, $row);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion(): string
    {
        return '3.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
