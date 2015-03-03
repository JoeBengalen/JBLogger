<?php

namespace JoeBengalen\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface
{
    use LoggerTrait;
    
    protected $handlers = [];
    
    protected $logLevels = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::ALERT,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::EMERGENCY,
        LogLevel::CRITICAL
    ];

    /**
     * Create a logger instance and register handlers
     * 
     * @param JoeBengalen\Logger\Handler\HandlerInterface[] $handlers List of handlers
     * 
     * @throws InvalidArgumentException If any handler does not implement the JoeBengalen\Logger\Handler\HandlerInterface
     */
    public function __construct(array $handlers)
    {
        foreach ($handlers as $handler) {
            if (!$handler instanceof Handler\HandlerInterface) {
                throw new InvalidArgumentException("Handler must implement the JoeBengalen\Logger\Handler\HandlerInterface");
            }
        }
        $this->handlers = $handlers;
    }

    /**
     * Calls each registered handler
     * 
     * @param string $level The log level. Must be a Psr\Log\LogLevel.
     * @param string $message The message to log
     * @param array $context Context values sent along with the message
     * 
     * @throws InvalidArgumentException If the $level is not a Psr\Log\LogLevel
     */
    public function log($level, $message, array $context = [])
    {
        // check if the log level is valid
        if (!in_array($level, $this->logLevels)) {
            throw new InvalidArgumentException("Log level '{$level}' is not reconized.");
        }

        // call each handler
        foreach ($this->handlers as $handler) {
            $handler->log($level, $message, $context);
        }
    }
}