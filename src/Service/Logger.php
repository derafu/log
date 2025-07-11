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
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger as MonologLogger;

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
     * @param array $configuration configuration for the logger.
     * @param array $handlers Additional handlers.
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
        $context = $this->normalizeContext($context);
        $this->getLogger()->log($level, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->debug($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->info($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->notice($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->warning($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->error($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->critical($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->alert($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = []): void
    {
        $context = $this->normalizeContext($context);
        $this->getLogger()->emergency($message, $context);
    }

    /**
     * Normalizes the context of the log record.
     *
     * @param array $context
     * @return array
     */
    private function normalizeContext(array $context): array
    {
        // Add the caller.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $context['__caller'] = new Caller(
            file: $trace[1]['file'] ?? null,
            line: $trace[1]['line'] ?? null,
            function: $trace[2]['function'] ?? null,
            class: $trace[2]['class'] ?? null,
            type: $trace[2]['type'] ?? null
        );

        // Return the normalized context.
        return $context;
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
        // Create the logger.
        $channel = $this->configuration['channel'] ?? 'derafu_lib';
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

        // Add the handlers.
        foreach ($this->handlers as $handler) {
            $this->logger->pushHandler($handler);
        }
    }
}
