<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsLog\Fixture;

use DateTimeImmutable;
use Derafu\Log\Caller;
use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Level;
use Monolog\Level as MonologLevel;

/**
 * Exposes a real record through LogInterface without Monolog's public properties.
 */
final class LogRecordView implements LogInterface
{
    public function __construct(private readonly LogInterface $record)
    {
    }

    public function getDateTime(): DateTimeImmutable
    {
        return $this->record->getDateTime();
    }

    public function getChannel(): string
    {
        return $this->record->getChannel();
    }

    public function getCode(): int
    {
        return $this->record->getCode();
    }

    public function getLevel(): Level
    {
        return $this->record->getLevel();
    }

    public function getMonologLevel(): MonologLevel
    {
        return $this->record->getMonologLevel();
    }

    public function getMessage(): string
    {
        return $this->record->getMessage();
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): array
    {
        return $this->record->getContext();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtra(): array
    {
        return $this->record->getExtra();
    }

    public function getFormattedRecord(): mixed
    {
        return $this->record->getFormattedRecord();
    }

    public function getFormattedMessage(): ?string
    {
        return $this->record->getFormattedMessage();
    }

    public function getCaller(): ?Caller
    {
        return $this->record->getCaller();
    }
}
