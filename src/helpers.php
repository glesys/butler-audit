<?php

if (! function_exists('audit')) {
    /**
     * @param  string|array|\Butler\Audit\Auditable  $entityType
     * @param  mixed  $entityId
     */
    function audit($entityType, $entityId = null): Butler\Audit\Auditor
    {
        return Butler\Audit\Facades\Auditor::entity($entityType, $entityId);
    }
}
