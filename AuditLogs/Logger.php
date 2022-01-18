<?php

namespace AuditLogs;

class Logger extends AbstractLogger
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var \Monolog\Logger
     */
    private \Monolog\Logger $logger;

    /**
     * @param ClientInterface $client
     * @param \Monolog\Logger $logger
     */
    protected function __construct(ClientInterface $client, \Monolog\Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function log(string $event, int $auditedUserId, array $modifiedResource = []): void
    {
        $data = $this->getMessageData($event, $auditedUserId, $modifiedResource);

        try {
            $this->client->send($data);
        } catch (AuditLogsException $exception) {
            $this->logger->error('Failed to send message', [
                'error_message' => $exception->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * @param string $event
     * @param int $auditedUserId
     * @param array $modifiedResource
     * @return array
     */
    private function getMessageData(string $event, int $auditedUserId, array $modifiedResource = []): array
    {
        $data = [
            'Data' => [
                'Source' => 'ELM',
                'EventType' => $event,
                'AuditedUser' => [
                    'Id' => $auditedUserId,
                ],
            ]
        ];

        if (! empty($modifiedResource)) {
            $data['Data']['ModifiedResource'] = $modifiedResource;
        }

        return $data;
    }
}
