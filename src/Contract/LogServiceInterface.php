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

/**
 * Interface for the logging class.
 */
interface LogServiceInterface
{
    /**
     * Returns the instance of the worker that writes to the log.
     *
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;

    /**
     * Retrieves the logs from the log.
     *
     * You can retrieve all logs or those of a specific level. Also, you can
     * deliver the logs in the order they were entered or from newest to oldest.
     *
     * @param int|string|null $level Level of the logs that you want to retrieve.
     * @param bool $newFirst Indicates whether to deliver new logs first.
     * @return array<LogInterface> Array with the requested logs.
     */
    public function logs(
        int|string|null $level = null,
        bool $newFirst = true
    ): array;

    /**
     * Deletes the logs of a specific level from the log.
     *
     * @param int|string|null $level Level of the logs that you want to clean.
     * @return void
     */
    public function clear(int|string|null $level = null): void;

    /**
     * Retrieves the logs and deletes them from the log.
     *
     * You can retrieve all logs or those of a specific level. Also, you can
     * deliver the logs in the order they were entered or from newest to oldest.
     *
     * @param int|string|null $level Level of the logs that you want to retrieve.
     * @param bool $newFirst Indicates whether to deliver new logs first.
     * @return array<LogInterface> Array with the requested logs.
     */
    public function flush(
        int|string|null $level = null,
        bool $newFirst = true
    ): array;
}
