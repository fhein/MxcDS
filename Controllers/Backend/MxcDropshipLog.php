<?php

use MxcCommons\Plugin\Controller\BackendApplicationController;
use MxcDropship\Models\DropshipLogEntry;
use MxcDropship\MxcDropship;

class Shopware_Controllers_Backend_MxcDropshipLog extends BackendApplicationController
{
    protected $model = DropshipLogEntry::class;
    protected $alias = 'dsilog';

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $log = MxcDropship::getServices()->get('logger');
        $log->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }
}
