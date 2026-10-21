<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log\Service;

use Derafu\Container\Contract\JournalInterface;
use Derafu\Container\Journal;
use Derafu\Log\Caller;
use Derafu\Log\Contract\LoggerInterface;
use Derafu\Log\Level;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Level as MonologLevel;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord as MonologLogRecord;
use Stringable;

/**
 * Class to implement the logger service.
 */
class Logger implements LoggerInterface
{
    /**
     * Instance of the Monolog logger.
     *
     * @var MonologLogger
     */
    private MonologLogger $logger;

    /**
     * Instance of the log storage.
     *
     * @var JournalInterface
     */
    private JournalInterface $journal;

    /**
     * Constructor of the logger.
     *
     * @param array<array-key, mixed> $configuration Configuration for the logger.
     * @param array<array-key, mixed> $handlers Additional handlers, each implementing HandlerInterface.
     * @param FormatterInterface|null $formatter Formatter for the logs.
     */
    public function __construct(
        private array $configuration = [],
        private array $handlers = [],
        private readonly ?FormatterInterface $formatter = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getJournal(): JournalInterface
    {
        return $this->journal;
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $level = (new Level($level))->getMonologLevel();
        $this->write($level, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->write(MonologLevel::Debug, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = []): void
    {
        $this->write(MonologLevel::Info, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->write(MonologLevel::Notice, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->write(MonologLevel::Warning, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = []): void
    {
        $this->write(MonologLevel::Error, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->write(MonologLevel::Critical, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->write(MonologLevel::Alert, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->write(MonologLevel::Emergency, $message, $context);
    }

    /**
     * Writes a record with caller metadata separate from the consumer's context.
     *
     * @param MonologLevel $level
     * @param string|Stringable $message
     * @param array<array-key, mixed> $context
     */
    private function write(
        MonologLevel $level,
        string|Stringable $message,
        array $context,
    ): void {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = new Caller(
            file: $trace[1]['file'] ?? null,
            line: $trace[1]['line'] ?? null,
            function: $trace[2]['function'] ?? null,
            class: $trace[2]['class'] ?? null,
            type: $trace[2]['type'] ?? null
        );

        $logger = $this->getLogger();
        $logger->pushProcessor(static function (MonologLogRecord $record) use ($caller): MonologLogRecord {
            $record->extra[Caller::class] ??= $caller;

            return $record;
        });

        try {
            $logger->log($level, $message, $context);
        } finally {
            $logger->popProcessor();
        }
    }

    /**
     * Returns the instance of the logger ensuring that it is initialized.
     *
     * @return MonologLogger
     */
    private function getLogger(): MonologLogger
    {
        if (!isset($this->logger)) {
            $this->initialize();
        }

        return $this->logger;
    }

    /**
     * Initializes the logger.
     *
     * @return void
     */
    private function initialize(): void
    {
        $channel = $this->configuration['channel'] ?? 'derafu_lib';
        if (!is_string($channel)) {
            throw new InvalidArgumentException([
                'The log channel must be a string; {type} given.',
                'type' => get_debug_type($channel),
            ]);
        }
        $handlers = $this->validatedHandlers();

        // Create the logger after validating its configuration.
        $this->logger = new MonologLogger($channel);

        // Create the processor.
        $processor = new Processor();
        $this->logger->pushProcessor($processor);

        // Create the journal.
        $this->journal = new Journal();

        // Create the journal handler.
        $journalHandler = new JournalHandler($this->journal);
        if ($this->formatter !== null) {
            $journalHandler->setFormatter($this->formatter);
        }
        $this->handlers[] = $journalHandler;
        $handlers[] = $journalHandler;

        // Add the handlers.
        foreach ($handlers as $handler) {
            $this->logger->pushHandler($handler);
        }
    }

    /**
     * @return array<array-key, HandlerInterface>
     */
    private function validatedHandlers(): array
    {
        $handlers = [];
        foreach ($this->handlers as $key => $handler) {
            if (!$handler instanceof HandlerInterface) {
                throw new InvalidArgumentException([
                    'The log handler at key "{key}" must implement HandlerInterface; {type} given.',
                    'key' => $key,
                    'type' => get_debug_type($handler),
                ]);
            }
            $handlers[$key] = $handler;
        }

        return $handlers;
    }
}
