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
use Magento\TwoFactorAuth\Api\Data\UserConfigInterface;
use Magento\TwoFactorAuth\Api\Data\UserConfigInterfaceFactory;

/**
 * User configuration data model
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class UserConfig extends AbstractModel
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var UserConfigInterfaceFactory
     */
    private $userConfigDataFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectHelper $dataObjectHelper
     * @param UserConfigInterfaceFactory $userConfigDataFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectHelper $dataObjectHelper,
        UserConfigInterfaceFactory $userConfigDataFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->dataObjectHelper = $dataObjectHelper;
        $this->userConfigDataFactory = $userConfigDataFactory;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\UserConfig::class);
    }

    /**
     * Retrieve UserConfig model
     *
     * @return UserConfigInterface
     */
    public function getDataModel(): UserConfigInterface
    {
        $userConfigData = $this->getData();

        /** @var UserConfigInterface $userConfigDataObject */
        $userConfigDataObject = $this->userConfigDataFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $userConfigDataObject,
            $userConfigData,
            UserConfigInterface::class
        );
        $userConfigDataObject->setId($this->getId());

        return $userConfigDataObject;
    }
}
