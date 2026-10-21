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

use Derafu\Log\Contract\LoggerInterface;

function logFromFunction(LoggerInterface $logger): int
{
    $line = __LINE__ + 1;
    $logger->info('Function log');

    return $line;
}
