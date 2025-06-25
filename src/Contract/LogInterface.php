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

use DateTimeImmutable;
use Derafu\Log\Caller;
use Derafu\Log\Level;
use Monolog\Level as MonologLevel;

/**
 * Interface for the entity that represents a log record.
 */
interface LogInterface
{
    /**
     * Returns the date and time of the log record.
     *
     * @return DateTimeImmutable
     */
    public function getDateTime(): DateTimeImmutable;

    /**
     * Returns the channel of the log record.
     *
     * @return string
     */
    public function getChannel(): string;

    /**
     * Returns the code of the log record.
     *
     * @return int
     */
    public function getCode(): int;

    /**
     * Returns the level of the log record.
     *
     * @return Level
     */
    public function getLevel(): Level;

    /**
     * Returns the level of the log record.
     *
     * @return MonologLevel
     */
    public function getMonologLevel(): MonologLevel;

    /**
     * Returns the message of the log record.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Returns the context of the log record.
     *
     * @return array<mixed>
     */
    public function getContext(): array;

    /**
     * Returns the extra of the log record.
     *
     * @return array<mixed>
     */
    public function getExtra(): array;

    /**
     * Returns the formatted message of the log record.
     *
     * @return string|null
     */
    public function getFormattedMessage(): ?string;

    /**
     * Returns the caller of the log record.
     *
     * @return Caller|null
     */
    public function getCaller(): ?Caller;
}
