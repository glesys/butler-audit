<?php

if (! function_exists('audit')) {
    function audit(
        string|array|Butler\Audit\Contracts\Auditable $entityType,
        mixed $entityId = null,
    ): Butler\Audit\Audit {
        return Butler\Audit\Facades\Auditor::entity($entityType, $entityId);
    }
}
