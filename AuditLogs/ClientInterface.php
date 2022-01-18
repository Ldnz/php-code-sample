<?php

namespace AuditLogs;

interface ClientInterface
{
    /**
     * @param array $data
     * @throws AuditLogsException
     */
    public function send(array $data): void;
}
