<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log\Contract;

use Derafu\Container\Contract\JournalInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Interface for the logging class.
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Get the journal where the logger is writing.
     *
     * @return JournalInterface
     */
    public function getJournal(): JournalInterface;

    /**
     * Register a message in the log.
     *
     * @param int|string $level Level of the message that you want to register.
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function log($level, $message, array $context = []): void;

    /**
     * Register a DEBUG message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function debug($message, array $context = []): void;

    /**
     * Register a INFO message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function info($message, array $context = []): void;

    /**
     * Register a NOTICE message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function notice($message, array $context = []): void;

    /**
     * Register a WARNING message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function warning($message, array $context = []): void;

    /**
     * Register a ERROR message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function error($message, array $context = []): void;

    /**
     * Register a CRITICAL message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function critical($message, array $context = []): void;

    /**
     * Register a ALERT message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function alert($message, array $context = []): void;

    /**
     * Register a EMERGENCY message in the log.
     *
     * @param string $message The message that you want to register.
     * @param array $context Additional context for the message.
     * @return void
     */
    public function emergency($message, array $context = []): void;
}
