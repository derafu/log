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

use Derafu\Log\Caller;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Caller::class)]
final class CallerTest extends TestCase
{
    /** @return iterable<string, array{string|null, int|null, string|null, string|null, string|null, string}> */
    public static function origins(): iterable
    {
        yield 'instance method' => ['/app/task.php', 20, 'process', 'Worker', '->', 'in /app/task.php on line 20, called by Worker->process()'];
        yield 'static method' => ['/app/task.php', 20, 'process', 'Worker', '::', 'in /app/task.php on line 20, called by Worker::process()'];
        yield 'global function' => ['/app/task.php', 20, 'processTask', null, null, 'in /app/task.php on line 20, called by processTask()'];
        yield 'script location' => ['/app/task.php', 20, null, null, null, 'in /app/task.php on line 20'];
        yield 'method without location' => [null, null, 'process', 'Worker', '->', 'called by Worker->process()'];
        yield 'function without location' => [null, null, 'processTask', null, null, 'called by processTask()'];
        yield 'file without line' => ['/app/task.php', null, null, null, null, 'in /app/task.php'];
        yield 'unknown origin' => [null, null, null, null, null, ''];
    }

    #[DataProvider('origins')]
    public function testPreservesTheAvailableDataAndFormatsItsOrigin(
        ?string $file,
        ?int $line,
        ?string $function,
        ?string $class,
        ?string $type,
        string $expected
    ): void {
        $caller = new Caller($file, $line, $function, $class, $type);

        $this->assertSame($file, $caller->file);
        $this->assertSame($line, $caller->line);
        $this->assertSame($function, $caller->function);
        $this->assertSame($class, $caller->class);
        $this->assertSame($type, $caller->type);
        $this->assertSame($expected, (string) $caller);
    }
}
