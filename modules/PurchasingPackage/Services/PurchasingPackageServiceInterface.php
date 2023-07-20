<?php

namespace Modules\PurchasingPackage\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Modules\Document\Models\Document;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\User\Models\User;

interface PurchasingPackageServiceInterface
{
    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);


    /**
     * @param PurchasingPackage $purchasingPackage
     * @param $status
     * @param User $creator
     * @return PurchasingPackage
     */
    public function changeState(PurchasingPackage $purchasingPackage, $status, User $creator);

    /**
     * @param PurchasingPackage $purchasingPackage
     * @param $status
     * @param User $creator
     * @return PurchasingPackage
     */
    public function updateFinanceStatus(PurchasingPackage $purchasingPackage, $status, User $creator);

    /**
     * @param PurchasingPackage $purchasingPackage
     * @param Document $document
     * @return PurchasingPackage
     */
    public function updateReceivedQuantityByDocument(PurchasingPackage $purchasingPackage, Document $document);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export(array $filter, User $user);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantExport(array $filter, User $user);

    /**
     * @param $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFinanceStatus($filePath, User $creator);

    /**
     * Thêm sản phẩm cho kiện nhập
     *
     * @param PurchasingPackage $purchasingPackage
     * @param array $inputs
     * @return void
     */
    public function addItems(PurchasingPackage $purchasingPackage, array $inputs);
}
