<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Setup\Patch\Data;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\TwoFactorAuth\Model\Provider\Engine\Authy\Service;
use Magento\TwoFactorAuth\Model\Provider\Engine\DuoSecurity;

/**
 * Encrypt secrets in configuration
 */
class EncryptSecrets implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EncryptorInterface $encryptor
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $connection = $this->moduleDataSetup->getConnection();

        $query = $connection->select()
            ->from($configTable)
            ->where(
                'path in (?)',
                [
                    DuoSecurity::XML_PATH_CLIENT_SECRET,
                    Service::XML_PATH_API_KEY,
                ]
            );
        $configurations = $connection->fetchAll($query);

        foreach ($configurations as $configuration) {
            if (preg_match('/[^\x00-\x7F]/', $this->encryptor->decrypt($configuration['value']))) {
                $connection->update(
                    $configTable,
                    ['value' => $this->encryptor->encrypt($configuration['value'])],
                    $connection->quoteInto('config_id = ?', $configuration['config_id'])
                );
            }
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
