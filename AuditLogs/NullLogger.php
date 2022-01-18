<?php

namespace AuditLogs;

class NullLogger extends AbstractLogger
{
    /**
     * @inheritDoc
     */
    public function log(string $event, int $auditedUserId, array $modifiedResource = []): void
    {
        // Do nothing, it's null logger
    }
}
