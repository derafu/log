<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log\Service;

use Derafu\Log\Contract\LoggerInterface;
use Derafu\Log\Contract\LogServiceInterface;
use Derafu\Log\Level;

/**
 * Log service.
 */
class LogService implements LogServiceInterface
{
    /**
     * Constructor of the component.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritDoc}
     */
    public function logs(
        int|string|null $level = null,
        bool $newFirst = true
    ): array {
        $journal = $this->logger->getJournal();

        // Get all logs.
        $records = $journal->all();
        if ($level === null) {
            return $newFirst ? array_reverse($records) : $records;
        }

        // Get logs of certain level.
        $level = (new Level($level))->getCode();
        $filtered = [];
        foreach ($records as $record) {
            if ($record->level->value === $level) {
                $filtered[] = $record;
            }
        }

        return $newFirst ? array_reverse($filtered) : $filtered;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(int|string|null $level = null): void
    {
        $journal = $this->logger->getJournal();

        // Delete all logs.
        if ($level === null) {
            $journal->clear();
            return;
        }

        // Delete all logs except those of certain level (those are deleted).
        $level = (new Level($level))->getCode();
        $logs = array_filter(
            $journal->all(),
            fn ($log) => $log->level->value !== $level
        );
        $journal->clear();
        foreach ($logs as $logRecord) {
            $journal->add($logRecord);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function flush(
        int|string|null $level = null,
        bool $newFirst = true
    ): array {
        $logs = $this->logs($level, $newFirst);
        $this->clear($level);

        return $logs;
    }
}
