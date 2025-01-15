<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\TwoFactorAuth\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\TwoFactorAuth\Api\Data\CountryInterface;
use Magento\TwoFactorAuth\Api\Data\CountryInterfaceFactory;

/**
 * Country Data model
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Country extends AbstractModel
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CountryInterfaceFactory
     */
    private $countryDataFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectHelper $dataObjectHelper
     * @param CountryInterfaceFactory $countryDataFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectHelper $dataObjectHelper,
        CountryInterfaceFactory $countryDataFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->dataObjectHelper = $dataObjectHelper;
        $this->countryDataFactory = $countryDataFactory;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Country::class);
    }

    /**
     * Retrieve Country model
     *
     * @return CountryInterface
     */
    public function getDataModel(): CountryInterface
    {
        $countryData = $this->getData();

        /** @var CountryInterface $countryDataObject */
        $countryDataObject = $this->countryDataFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $countryDataObject,
            $countryData,
            CountryInterface::class
        );
        $countryDataObject->setId($this->getId());

        return $countryDataObject;
    }
}
