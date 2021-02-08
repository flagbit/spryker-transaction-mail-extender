<?php

namespace OneAndOne\Zed\OneAndOneMailConnector\Business\SchemaOrgOrderMailExpander;

use Generated\Shared\Transfer\MailTemplateTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\SchemaOrgTransfer;

interface SchemaOrgOrderMailExpanderInterface
{
    /**
     * Expand the MailTransfer with an MailTemplateTransfer which adds additional schema.org information.
     *
     * @param MailTransfer         $mailTransfer
     * @param OrderTransfer        $orderTransfer
     * @param MailTemplateTransfer $mailTemplateTransfer
     * @param SchemaOrgTransfer    $schemaOrgTransfer
     *
     * @return MailTransfer
     */
    public function expandOrderMailTransfer(
        MailTransfer $mailTransfer,
        OrderTransfer $orderTransfer,
        MailTemplateTransfer $mailTemplateTransfer,
        SchemaOrgTransfer $schemaOrgTransfer
    ): MailTransfer;
}
