<?php

use Butler\Audit\Audit;
use Butler\Audit\Contracts\Auditable;
use Butler\Audit\Facades\Auditor;

if (! function_exists('audit')) {
    function audit(
        string|array|Auditable $entityType,
        mixed $entityId = null,
    ): Audit {
        return Auditor::entity($entityType, $entityId);
    }
}
