<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\ResourceModel;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * User config model
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class UserConfig extends AbstractDb
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param string $connectionName
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        $connectionName = null,
        ?EncryptorInterface $encryptor = null,
        ?SerializerInterface $serializer = null
    ) {
        parent::__construct($context, $connectionName);
        $this->encryptor = $encryptor ?:
            ObjectManager::getInstance()->get(EncryptorInterface::class);
        $this->serializer = $serializer ?:
            ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('tfa_user_config', 'config_id');
    }

    /**
     * Encode the provided config
     *
     * @param array $config
     * @return string
     */
    private function encodeConfig(array $config): string
    {
        return $this->encryptor->encrypt($this->serializer->serialize($config));
    }

    /**
     * Decode the provided config
     *
     * @param string $config
     * @return array
     */
    private function decodeConfig(string $config): array
    {
        // Support for legacy unencrypted configuration
        try {
            $config = $this->encryptor->decrypt($config);
        } catch (Exception $e) {
            unset($e);
        }

        return $this->serializer->unserialize($config);
    }

    /**
     * @inheritDoc
     */
    public function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        try {
            $object->setData('config', $this->decodeConfig($object->getData('encoded_config') ?? ''));
        } catch (Exception $e) {
            $object->setData('config', []);
        }

        try {
            $object->setData('providers', $this->serializer->unserialize($object->getData('encoded_providers') ?? ''));
        } catch (Exception $e) {
            $object->setData('providers', []);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function _beforeSave(AbstractModel $object)
    {
        $object->setData('encoded_config', $this->encodeConfig($object->getData('config') ?? []));
        $object->setData('encoded_providers', $this->serializer->serialize($object->getData('providers') ?? []));

        return parent::_beforeSave($object);
    }
}
