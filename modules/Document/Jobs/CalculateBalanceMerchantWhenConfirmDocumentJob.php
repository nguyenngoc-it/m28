<?php

namespace Modules\Document\Jobs;

use App\Base\Job;
use Modules\Document\Commands\CalculateBalanceMerchantWhenConfirmDocument;
use Modules\Document\Models\Document;

class CalculateBalanceMerchantWhenConfirmDocumentJob extends Job
{
    public $queue = 'calculate_balance_merchant';

    /**
     * @var integer
     */
    protected $documentId;

    /**
     * AfterConfirmDocumentFreightBillInventoryJob constructor.
     * @param $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle()
    {
        $document = Document::find($this->documentId);
        /**
         * trừ tiền dịch vụ vào ví seller
         */
        (new CalculateBalanceMerchantWhenConfirmDocument($document))->handle();
    }
}
