<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log;

use DateTimeImmutable;
use Derafu\Log\Contract\LogInterface;
use Monolog\Level as MonologLevel;
use Monolog\LogRecord as MonologLogRecord;

/**
 * Class that represents a log message.
 */
class Log extends MonologLogRecord implements LogInterface
{
    /**
     * Code of the record.
     *
     * @var int
     */
    public int $code;

    /**
     * Who called the log.
     *
     * @var Caller|null
     */
    public ?Caller $caller;

    /**
     * {@inheritDoc}
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->datetime;
    }

    /**
     * {@inheritDoc}
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getLevel(): Level
    {
        return new Level($this->level->value);
    }

    /**
     * {@inheritDoc}
     */
    public function getMonologLevel(): MonologLevel
    {
        return $this->level;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormattedMessage(): ?string
    {
        return $this->formatted;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaller(): ?Caller
    {
        return $this->caller;
    }
}
