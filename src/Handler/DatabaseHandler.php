<?php
/**
 * JoeBengalen Logger library
 * 
 * @author      Martijn Wennink <joebengalen@gmail.com>
 * @copyright   Copyright (c) 2015 Martijn Wennink
 * @license     https://github.com/JoeBengalen/Logger/blob/master/LICENSE.md (MIT License)
 * @version     0.1.0
 */
namespace JoeBengalen\Logger\Handler;

/**
 * Database log handler
 * 
 * Database log handler that uses a \PDO instance. All log levels will be logged.
 */
class DatabaseHandler extends AbstractHandler
{
    /**
     * @var \PDO $connection Connection instance
     */
    protected $connection;

    /**
     * @var array $options {
     *      @var string $table              Table name
     *      @var string $column.datetime    Datetime column name
     *      @var string $column.level       Level column name
     *      @var string $column.message     Message column name
     *      @var string $column.context     Context column name
     * }
     */
    protected $options;

    /**
     * Create database log handler
     * 
     * This handler logs the message to a database
     * 
     * @param \PDO  $connection Connection instance
     * @param array $options (optional) {
     *      @var string $table              Table name
     *      @var string $column.datetime    Datetime column name
     *      @var string $column.level       Level column name
     *      @var string $column.message     Message column name
     *      @var string $column.context     Context column name
     * }
     */
    public function __construct(\PDO $connection, array $options = [])
    {
        $this->connection = $connection;

        $this->options = array_merge([
            'table'           => 'logs',
            'column.datetime' => 'datetime',
            'column.level'    => 'level',
            'column.message'  => 'message',
            'column.context'  => 'context'
                ], $options);
    }

    /**
     * Log a message
     * 
     * @param mixed     $level      Log level defined in \Psr\Log\LogLevel
     * @param string    $message    Message to log
     * @param mixed[]   $context    Extra information
     */
    public function __invoke($level, $message, array $context = [])
    {
        $interpolatedMessage = $this->interpolate($message, $context);

        // Check for a \Exception in the context
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            $interpolatedMessage .= " " . (string) $context['exception'];
            unset($context['exception']);
        }

        $sql = "INSERT INTO {$this->options['table']} ({$this->options['column.datetime']}, {$this->options['column.level']}, {$this->options['column.message']}, {$this->options['column.context']}) VALUES (NOW(), ?, ?, ?)";
        $sth = $this->connection->prepare($sql);

        $sth->execute([
            $level,
            $interpolatedMessage,
            json_encode($context)
        ]);
    }
}