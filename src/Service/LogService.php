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
use Derafu\Log\Contract\LoggerInterface;
use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Contract\LogServiceInterface;
use Derafu\Log\Level;
use Derafu\Translation\Exception\Runtime\TranslatableUnexpectedValueException as UnexpectedValueException;

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
        $records = $this->validatedRecords($journal);
        if ($level === null) {
            return $newFirst ? array_reverse($records) : $records;
        }

        // Get logs of certain level.
        $level = (new Level($level))->getCode();
        $filtered = [];
        foreach ($records as $record) {
            if ($record->getMonologLevel()->value === $level) {
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
            $this->validatedRecords($journal),
            fn (LogInterface $log) => $log->getMonologLevel()->value !== $level
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

    /**
     * Reads all records before any filtering or removal takes place.
     *
     * @return array<array-key, LogInterface>
     */
    private function validatedRecords(JournalInterface $journal): array
    {
        $records = [];
        foreach ($journal->all() as $key => $record) {
            if (!$record instanceof LogInterface) {
                throw new UnexpectedValueException([
                    'The log journal record at key "{key}" must implement LogInterface; {type} given.',
                    'key' => $key,
                    'type' => get_debug_type($record),
                ]);
            }
            $records[$key] = $record;
        }

        return $records;
    }
}
