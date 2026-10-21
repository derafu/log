<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsLog;

use DateTimeImmutable;
use Derafu\Log\Caller;
use Derafu\Log\Log;
use Derafu\Log\Service\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LineFormatter::class)]
#[UsesClass(Caller::class)]
#[UsesClass(Log::class)]
final class LineFormatterTest extends TestCase
{
    /** @return iterable<string, array{Caller|null, string}> */
    public static function callers(): iterable
    {
        yield 'no caller' => [null, 'Message'];
        yield 'unknown origin' => [new Caller(null, null, null, null, null), 'Message'];
        yield 'full origin' => [new Caller('/app/task.php', 20, 'process', 'Worker', '->'), 'Message in /app/task.php on line 20, called by Worker->process().'];
        yield 'script' => [new Caller('/app/task.php', 20, null, null, null), 'Message in /app/task.php on line 20.'];
        yield 'function' => [new Caller(null, null, 'processTask', null, null), 'Message called by processTask().'];
    }

    #[DataProvider('callers')]
    public function testAppendsOnlyAvailableOriginInformation(?Caller $caller, string $expected): void
    {
        $record = new Log(new DateTimeImmutable(), 'test', Level::Info, 'Message');
        $record->caller = $caller;

        $this->assertSame($expected, (new LineFormatter('%message%'))->format($record));
    }

    public function testFormatsAPlainMonologRecordWithoutAddingAnOrigin(): void
    {
        $record = new LogRecord(new DateTimeImmutable(), 'test', Level::Info, 'Plain message');

        $this->assertSame('Plain message', (new LineFormatter('%message%'))->format($record));
    }
}
