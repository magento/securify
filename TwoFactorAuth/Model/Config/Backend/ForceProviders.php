<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\TwoFactorAuth\Api\TfaInterface;

/**
 * Managing "Force Providers" config value.
 */
class ForceProviders extends Value
{
    /**
     * @var TfaInterface
     */
    private $tfa;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param TfaInterface $tfa
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        TfaInterface $tfa,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
        $this->tfa = $tfa;
    }

    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $codes = [];
        $providers = $this->tfa->getAllProviders();
        foreach ($providers as $provider) {
            $codes[] = $provider->getCode();
        }

        $value = $this->getValue();
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $validValues = is_array($value) ? array_intersect($codes, $value) : [];
        if (count($value) === 0 || count($validValues) === 0) {
            throw new ValidatorException(__('You have to select at least one Two-Factor Authorization provider'));
        }

        // Removes invalid codes
        $this->setValue($validValues);

        return parent::beforeSave();
    }
}
