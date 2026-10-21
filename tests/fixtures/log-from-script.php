<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Service\LineFormatter;
use Derafu\Log\Service\Logger;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$logger = new Logger(formatter: new LineFormatter('%message%'));
$line = __LINE__ + 1;
$logger->info('Script log');
$records = $logger->getJournal()->all();
$record = $records[0] ?? null;
if (!$record instanceof LogInterface) {
    throw new RuntimeException('The script must produce a log record.');
}
$caller = $record->getCaller();

echo json_encode([
    'message' => $record->getMessage(),
    'file' => $caller?->file,
    'line' => $caller?->line,
    'function' => $caller?->function,
    'class' => $caller?->class,
    'type' => $caller?->type,
    'expected_line' => $line,
    'formatted' => $record->getFormattedMessage(),
], JSON_THROW_ON_ERROR);
