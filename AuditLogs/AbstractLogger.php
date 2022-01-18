<?php

namespace AuditLogs;

abstract class AbstractLogger
{
    /**
     * @var AbstractLogger
     */
    private static AbstractLogger $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (! self::$instance instanceof AbstractLogger) {
            switch (DEPLOYMENT_ENVIRONMENT) {
                case 'local':
                    self::$instance = new NullLogger();
                    break;
                default:
                    self::$instance = new Logger(
                        new RabbitMQClient(\getConfig('rabbitmq_connection')),
                        \newLogger('AuditLogs')
                    );
            }
        }

        return self::$instance;
    }

    public function __clone() {}

    /**
     * @param string $event
     * @param int $auditedUserId
     * @param array $modifiedResource
     * @return void
     */
    public abstract function log(string $event, int $auditedUserId, array $modifiedResource = []): void;
}
