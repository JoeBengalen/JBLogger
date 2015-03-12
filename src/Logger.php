<?php
/**
 * JoeBengalen Logger library.
 *
 * @author      Martijn Wennink <joebengalen@gmail.com>
 * @copyright   Copyright (c) 2015 Martijn Wennink
 * @license     https://github.com/JoeBengalen/Logger/blob/master/LICENSE.md (MIT License)
 *
 * @version     0.1.0
 */

namespace JoeBengalen\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logger.
 *
 * Lightweight logger class thats implements the psr3 interface. Handlers should
 * be registered to this logger. The handlers must do the actual logging task.
 * This class just passes the log details to each registered handler.
 */
class Logger implements LoggerInterface
{
    /**
     * @const string The package version number
     */
    const VERSION = '0.1.0';

    use LoggerTrait;
    
    /**
     * @var array $options {
     *      @var callable $message.factory          Alternative MessageInterface factory.
     *                                              Callable arguments: mixed $level, string $message, array $context
     *                                              Callable MUST return an instance of MessageInterface.
     *      @var callable|null $collection.factory  Alternative CollectionInterface factory.
     *                                              Callable MUST return an instance of CollectionInterface.
     *                                              Null means no collection will be used.
     * }
     */
    protected $options = [];

    /**
     * @var callable[] Log handlers
     */
    protected $handlers = [];

    /**
     * @var \JoeBengalen\Logger\CollectionInterface Log message collector
     */
    protected $collection;
    
    /**
     * @var boolean $locked Indicator whether the object is locked or not 
     */
    protected $locked = false;

    /**
     * Create a logger
     */
    public function __construct()
    {
        $this->options = [
            
            // MessageInterface factory callable
            'message.factory' => function ($level, $message, $context) {
                return new Message($level, $message, $context);
            },
            
            // CollectionInterface factory callable
            'collection.factory' => function () {
                return new Collection();
            },
        ];
    }
    
    /**
     * Add a handler
     * 
     * @param callable $handler Callable handler
     * 
     * @return \JoeBengalen\Logger\Logger
     */
    public function handler(callable $handler)
    {
        $this->handlers[] = $handler;
        
        return $this;
    }
    
    /**
     * Change an option.
     * 
     * @param string    $key    Option to change.
     * @param mixed     $value  New value for option.
     * 
     * @return \JoeBengalen\Logger\Logger
     * 
     * @throws \RuntimeException         If called when object is locked.
     * @throws \InvalidArgumentException If option message.factory is not a callable.
     * @throws \InvalidArgumentException If option collection.factory is not a callable or null.
     */
    public function option($key, $value)
    {
        if ($this->locked) {
            throw new \RuntimeException('Cannot change an option once the logger is locked.');
        }
        
        if ($key === 'message.factory' && !is_callable($value)) {
            throw new \InvalidArgumentException("Option 'message.factory' must contain a callable.");
        }
        
        if ($key === 'collection.factory' && !is_null($value) && !is_callable($value)) {
            throw new \InvalidArgumentException("Option 'collection.factory' must contain a callable or be null.");
        }
        
        $this->options[$key] = $value;
        
        return $this;
    }
    
    /**
     * Initialize the logger.
     * 
     * This locks the object so from now on no option can be changed.
     */
    public function init()
    {
        if (!$this->locked) {
            $this->collection = $this->createCollection();
            $this->locked     = true;
        }
    }

    /**
     * Calls each registered handler.
     *
     * @param mixed  $level   Log level. Must be defined in \Psr\Log\LogLevel.
     * @param string $message Message to log
     * @param array  $context Context values sent along with the message
     *
     * @throws \Psr\Log\InvalidArgumentException If the $level is not defined in \Psr\Log\LogLevel
     * @throws \RuntimeException                 If callable option 'message.factory' does not return an instance of \JoeBengalen\Logger\MessageInterface
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->locked) {
            $this->init();
        }
        
        $messageInstance = $this->createMessage($level, $message, $context);

        if (!is_null($this->collection)) {
            $this->collection->addMessage($messageInstance);
        }

        $this->callHandlers($messageInstance);
    }

    /**
     * Get the message collection.
     *
     * @return \JoeBengalen\Logger\CollectionInterface|null $collection Log message collection or null if not used
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Call each handler.
     *
     * @param \JoeBengalen\Logger\MessageInterface $message
     */
    protected function callHandlers(MessageInterface $message)
    {
        foreach ($this->handlers as $handler) {
            call_user_func($handler, $message);
        }
    }

    /**
     * Create a new message.
     *
     * @param mixed  $level   Log level. Must be defined in \Psr\Log\LogLevel.
     * @param string $message Message to log
     * @param array  $context Context values sent along with the message
     *
     * @return \JoeBengalen\Logger\MessageInterface
     *
     * @throws \RuntimeException If callable option 'message.factory' does not return an instance of \JoeBengalen\Logger\MessageInterface
     */
    protected function createMessage($level, $message, array $context)
    {
        $messageInstance = call_user_func_array($this->options['message.factory'], [$level, $message, $context]);

        if (!$messageInstance instanceof MessageInterface) {
            throw new \RuntimeException("Option 'message.factory' callable must return an instance of \JoeBengalen\Logger\MessageInterface");
        }

        return $messageInstance;
    }

    /**
     * Create a new collection.
     *
     * @return \JoeBengalen\Logger\CollectionInterface|null
     *
     * @throws \RuntimeException If callable option collection.factory does not return an instance of \JoeBengalen\Logger\CollectionInterface
     */
    protected function createCollection()
    {
        if (!is_null($this->options['collection.factory'])) {
            $collection = call_user_func($this->options['collection.factory']);

            if (!$collection instanceof CollectionInterface) {
                throw new \RuntimeException("Option 'message.factory' callable must return an instance of \JoeBengalen\Logger\CollectionInterface");
            }

            return $collection;
        }
    }
}