<?php

/**
 * @package monolog-factory
 * @link https://github.com/bayfrontmedia/monolog-factory
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\MonologFactory;

use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;
use Bayfront\MonologFactory\Exceptions\FormatterException;
use Bayfront\MonologFactory\Exceptions\HandlerException;
use Bayfront\MonologFactory\Exceptions\InvalidConfigurationException;
use Bayfront\MonologFactory\Exceptions\ProcessorException;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class LoggerFactory
{

    private $config;

    private static $channels; // Logger instances

    private static $default_channel;

    private static $current_channel;

    /**
     * Logger constructor.
     *
     * @param array $config
     *
     * @throws InvalidConfigurationException
     * @throws HandlerException
     * @throws FormatterException
     * @throws ProcessorException
     */

    public function __construct(array $config)
    {

        $this->config = $config;

        foreach ($this->config as $channel => $options) {

            if (isset($options['enabled']) && true !== $options['enabled']) { // Skip this channel if not enabled
                continue;
            }

            if (isset($options['default']) && true === $options['default']) {

                self::$default_channel = $channel;

                self::$current_channel = $channel;

            }

            /*
             * @throws HandlerException
             * @throws FormatterException
             * @throws ProcessorException
             */

            $this->_createChannel($channel, $options);

        }

        if (NULL === self::$default_channel) { // No default channel specified
            throw new InvalidConfigurationException('Invalid configuration: no default channel specified');
        }

    }

    /*
     * ############################################################
     * Channels
     * ############################################################
     */

    /**
     * Create channel from config array.
     *
     * @param string $channel
     * @param array $options
     *
     * @return self
     *
     * @throws HandlerException
     * @throws FormatterException
     * @throws ProcessorException
     */

    private function _createChannel(string $channel, array $options): self
    {

        // Create Logger object

        $logger = new Logger($channel);

        if (isset($options['handlers'])) {

            foreach ($options['handlers'] as $handler => $handler_array) {

                // Create handler

                $handler_class = '\Monolog\Handler\\' . $handler;

                try {

                    $handler_reflection = new ReflectionClass($handler_class);

                } catch (ReflectionException $e) {

                    throw new HandlerException('Unable to create handler (' . $handler_class . ')', 0, $e);

                }

                if (isset($handler_array['params'])) { // If parameters exist to pass to the constructor

                    $handler = $handler_reflection->newInstanceArgs($handler_array['params']);

                } else {

                    $handler = new $handler_class();

                }

                // Create formatter if specified

                if (isset($handler_array['formatter']['name'])) {

                    // Create formatter instance

                    $formatter_class = '\Monolog\Formatter\\' . $handler_array['formatter']['name'];

                    try {

                        $formatter_reflection = new ReflectionClass($formatter_class);

                    } catch (ReflectionException $e) {

                        throw new FormatterException('Unable to create formatter (' . $formatter_class . ')', 0, $e);

                    }


                    if (isset($handler_array['formatter']['params'])) { // If parameters exist to pass to the constructor

                        $formatter = $formatter_reflection->newInstanceArgs($handler_array['formatter']['params']);

                    } else {

                        $formatter = new $formatter_class();

                    }

                    // Set the handler's formatter

                    $handler->setFormatter($formatter);

                }

                // Bind the handler to the Logger object

                $logger->pushHandler($handler);

            }

        }

        // Create processors if specified

        if (isset($options['processors'])) {

            foreach ($options['processors'] as $processor => $processor_array) {

                // Create processor instance

                $processor_class = '\Monolog\Processor\\' . $processor;

                try {

                    $processor_reflection = new ReflectionClass($processor_class);

                } catch (ReflectionException $e) {

                    throw new ProcessorException('Unable to create processor (' . $processor_class . ')', 0, $e);

                }

                if (isset($processor_array['params'])) { // If parameters exist to pass to the constructor

                    $processor = $processor_reflection->newInstanceArgs($processor_array['params']);

                } else {

                    $processor = new $processor_class();

                }

                // Bind the processor to the Logger object

                $logger->pushProcessor($processor);

            }

        }

        // Bind the Logger object to the channels array

        self::$channels[$channel] = $logger;

        return $this;

    }

    /**
     * Add a logger instance as a new channel with the same name.
     *
     * If an existing instance exists with the same name, it will be overwritten.
     *
     * @param Logger $logger
     *
     * @return self
     */

    public function addChannel(Logger $logger): self
    {

        $name = $logger->getName();

        self::$channels[$name] = $logger;

        return $this;

    }

    /**
     * Returns logger instance for a given channel name.
     *
     * @param string $channel
     *
     * @return Logger
     *
     * @throws ChannelNotFoundException
     */

    public function getChannel(string $channel): Logger
    {

        if (!isset(self::$channels[$channel])) {
            throw new ChannelNotFoundException('Unable to get channel (' . $channel . '): channel not found');
        }

        return self::$channels[$channel];

    }

    /**
     * Checks if a given channel name exists.
     *
     * @param string $channel
     *
     * @return bool
     */

    public function isChannel(string $channel): bool
    {
        return isset(self::$channels[$channel]);
    }

    /**
     * Set the channel name to be used for the next logged event.
     *
     * By default, all logged events will be logged to the channel marked as "default" in the configuration array.
     *
     * @param string $channel
     *
     * @return self
     *
     * @throws ChannelNotFoundException
     */

    public function channel(string $channel): self
    {

        if (!isset(self::$channels[$channel])) {
            throw new ChannelNotFoundException('Unable to use channel (' . $channel . '): channel not found');
        }

        self::$current_channel = $channel;

        return $this;

    }

    /*
     * ############################################################
     * Logging events
     * ############################################################
     */

    /**
     * System is unusable.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function emergency($message, array $context = array())
    {

        self::$channels[self::$current_channel]->emergency($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function alert($message, array $context = array())
    {

        self::$channels[self::$current_channel]->alert($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function critical($message, array $context = array())
    {

        self::$channels[self::$current_channel]->critical($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function error($message, array $context = array())
    {

        self::$channels[self::$current_channel]->error($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function warning($message, array $context = array())
    {

        self::$channels[self::$current_channel]->warning($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function notice($message, array $context = array())
    {

        self::$channels[self::$current_channel]->notice($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function info($message, array $context = array())
    {

        self::$channels[self::$current_channel]->info($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */

    public function debug($message, array $context = array())
    {

        self::$channels[self::$current_channel]->debug($message, $context);

        self::$current_channel = self::$default_channel;

    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */

    public function log($level, $message, array $context = array())
    {

        self::$channels[self::$current_channel]->log($level, $message, $context);

        self::$current_channel = self::$default_channel;

    }

}