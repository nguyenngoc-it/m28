<?php

namespace Modules\Document\Commands;

use Modules\Document\Events\DocumentCodComparisonCreated;
use Modules\Document\Models\Document;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class CreateDocumentFreightBillInventory
{
    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * CreateDocumentFreightBillInventory constructor.
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     */
    public function __construct(ShippingPartner $shippingPartner, User $creator)
    {
        $this->shippingPartner = $shippingPartner;
        $this->creator         = $creator;
    }

    /**
     * @return Document
     */
    public function handle(): Document
    {
        $input = [
            'type' => Document::TYPE_FREIGHT_BILL_INVENTORY,
            'status' => Document::STATUS_DRAFT,
            'tenant_id' => $this->shippingPartner->tenant_id,
            'shipping_partner_id' => $this->shippingPartner->id,
            'creator_id' => $this->creator->id,
        ];

        $document                = Service::document()->create($input, $this->creator);
        $document->received_date = $document->created_at;
        $document->save();
        (new DocumentCodComparisonCreated($document))->queue();

        return $document;
    }
}
