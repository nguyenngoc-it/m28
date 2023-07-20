<?php

namespace Modules\OrderPacking\Services;

class PickingSessionEvent
{
    const CREATE_PICKING_SESSION = 'PICKING_SESSION.CREATE';
    const PICKED_PIECE           = 'PICKING_SESSION.PIECE_PICKD';
    const PICKED                 = 'PICKING_SESSION.PICKED';
}
