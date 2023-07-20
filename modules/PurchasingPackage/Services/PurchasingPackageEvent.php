<?php

namespace Modules\PurchasingPackage\Services;

class PurchasingPackageEvent
{
    const CREATE = 'PURCHASING_PACKAGE.CREATE';
    const UPDATE = 'PURCHASING_PACKAGE.UPDATE';
    const CHANGE_STATUS = 'PURCHASING_PACKAGE.CHANGE_STATUS';
    const UPDATE_FINANCE_STATUS = 'PURCHASING_PACKAGE.UPDATE_FINANCE_STATUS';
}
