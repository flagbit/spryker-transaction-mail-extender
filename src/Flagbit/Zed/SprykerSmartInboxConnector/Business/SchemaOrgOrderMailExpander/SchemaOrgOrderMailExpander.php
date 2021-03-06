<?php

namespace Flagbit\Zed\SprykerSmartInboxConnector\Business\SchemaOrgOrderMailExpander;

use Flagbit\Zed\SprykerSmartInboxConnector\Business\ParcelDelivery\ParcelDeliveryFactory;
use Flagbit\Zed\SprykerSmartInboxConnector\Persistence\SprykerSmartInboxConnectorRepositoryInterface;
use Flagbit\Zed\SprykerSmartInboxConnector\SprykerSmartInboxConnectorConfig;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MailTemplateTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\ParcelDeliveryTransfer;
use Generated\Shared\Transfer\SchemaOrgTransfer;

class SchemaOrgOrderMailExpander implements SchemaOrgOrderMailExpanderInterface
{
    /**
     * @var \Flagbit\Zed\SprykerSmartInboxConnector\SprykerSmartInboxConnectorConfig
     */
    private $config;

    /**
     * @var \Flagbit\Zed\SprykerSmartInboxConnector\Persistence\SprykerSmartInboxConnectorRepositoryInterface
     */
    private $repository;

    /**
     * @var \Flagbit\Zed\SprykerSmartInboxConnector\Business\ParcelDelivery\ParcelDeliveryFactory
     */
    private $parcelDeliveryFactory;

    /**
     * @param \Flagbit\Zed\SprykerSmartInboxConnector\SprykerSmartInboxConnectorConfig $config
     * @param \Flagbit\Zed\SprykerSmartInboxConnector\Persistence\SprykerSmartInboxConnectorRepositoryInterface $repository
     * @param \Flagbit\Zed\SprykerSmartInboxConnector\Business\ParcelDelivery\ParcelDeliveryFactory $parcelDeliveryFactory
     */
    public function __construct(
        SprykerSmartInboxConnectorConfig $config,
        SprykerSmartInboxConnectorRepositoryInterface $repository,
        ParcelDeliveryFactory $parcelDeliveryFactory
    ) {
        $this->config = $config;
        $this->repository = $repository;
        $this->parcelDeliveryFactory = $parcelDeliveryFactory;
    }

    /**
     * @param \Generated\Shared\Transfer\MailTransfer $mailTransfer
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param \Generated\Shared\Transfer\MailTemplateTransfer $mailTemplateTransfer
     * @param \Generated\Shared\Transfer\SchemaOrgTransfer $schemaOrgTransfer
     *
     * @return \Generated\Shared\Transfer\MailTransfer
     */
    public function expandOrderMailTransfer(
        MailTransfer $mailTransfer,
        OrderTransfer $orderTransfer,
        MailTemplateTransfer $mailTemplateTransfer,
        SchemaOrgTransfer $schemaOrgTransfer
    ): MailTransfer {
        $mailTemplateTransfer = $this->fillMailTemplateInfos($mailTemplateTransfer);
        $mailTransfer->addTemplate($mailTemplateTransfer);

        $schemaOrgTransfer = $this->fillSchemaOrgTransfer($orderTransfer, $schemaOrgTransfer);
        $mailTransfer->setSchemaOrg($schemaOrgTransfer);

        return $mailTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return string
     */
    protected function getLastChangedStatus(OrderTransfer $orderTransfer): string
    {
        $salesOrderItem = $this->getSalesOrderItem($orderTransfer);

        return $salesOrderItem->getState()->getName();
    }

    /**
     * @return string
     */
    protected function getShopName(): string
    {
        return $this->config->getShopName();
    }

    /**
     * @param string $status
     *
     * @return string
     */
    protected function getSchemaStatusWithOrderStatus(string $status): string
    {
        return $this->config->getStatusMatrix()[$status];
    }

    /**
     * @param \Generated\Shared\Transfer\MailTemplateTransfer $mailTemplateTransfer
     *
     * @return \Generated\Shared\Transfer\MailTemplateTransfer
     */
    protected function fillMailTemplateInfos(MailTemplateTransfer $mailTemplateTransfer): MailTemplateTransfer
    {
        $mailTemplateTransfer->setIsHtml(true);
        $mailTemplateTransfer->setName('Flagbit:SprykerSmartInboxConnector/mail/schema_org_order_connector.html.twig');

        return $mailTemplateTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param \Generated\Shared\Transfer\SchemaOrgTransfer $schemaOrgTransfer
     *
     * @return \Generated\Shared\Transfer\SchemaOrgTransfer
     */
    protected function fillSchemaOrgTransfer(
        OrderTransfer $orderTransfer,
        SchemaOrgTransfer $schemaOrgTransfer
    ): SchemaOrgTransfer {
        foreach ($orderTransfer->getItems() as $item) {
            $parcelDeliveryTransfer = $this->parcelDeliveryFactory->create();
            $this->fillParcelDelivery($parcelDeliveryTransfer, $item);
            $schemaOrgTransfer->addParcelDelivery($parcelDeliveryTransfer);
        }

        $schemaOrgTransfer->setShopName($this->getShopName());
        $schemaOrgTransfer->setStatus($this->getSchemaStatusWithOrderStatus($this->getLastChangedStatus($orderTransfer)));

        return $schemaOrgTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ParcelDeliveryTransfer $parcelDeliveryTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $item
     *
     * @return void
     */
    protected function fillParcelDelivery(ParcelDeliveryTransfer $parcelDeliveryTransfer, ItemTransfer $item): void
    {
        $parcelDeliveryTransfer->setDeliveryName($item->getShipment()->getCarrier()->getName());
        $parcelDeliveryTransfer->setStatus($this->getSchemaStatusWithOrderStatus($item->getState()->getName()));
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Propel\Runtime\Collection\ObjectCollection
     */
    protected function getSalesOrderItem(OrderTransfer $orderTransfer)
    {
        $orderIds = [];
        foreach ($orderTransfer->getItems() as $item) {
            $orderIds[] = $item->getIdSalesOrderItem();
        }

        return $this->repository->findSpySalesOrderItemByIdWithLastStateChange($orderIds);
    }
}
