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

use Derafu\Log\Level;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * A level that is not supported is a translatable error that says which one it
 * is.
 */
#[CoversClass(Level::class)]
final class LevelTest extends TestCase
{
    /** @return iterable<string, array{int, string}> */
    public static function levelNames(): iterable
    {
        yield 'debug' => [Level::DEBUG, 'Debug'];
        yield 'info' => [Level::INFO, 'Info'];
        yield 'notice' => [Level::NOTICE, 'Notice'];
        yield 'warning' => [Level::WARNING, 'Warning'];
        yield 'error' => [Level::ERROR, 'Error'];
        yield 'critical' => [Level::CRITICAL, 'Critical'];
        yield 'alert' => [Level::ALERT, 'Alert'];
        yield 'emergency' => [Level::EMERGENCY, 'Emergency'];
    }

    #[DataProvider('levelNames')]
    public function testReturnsTheExpectedNameForEachSeverity(int $code, string $expected): void
    {
        $this->assertSame($expected, (new Level($code))->getName());
    }

    public function testAStringThatIsNotALevelIsATranslatableError(): void
    {
        $exception = null;
        try {
            new Level('bogus');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(LogicException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('The log level bogus is not supported.', $exception->getMessage());
    }

    public function testACodeThatIsNotAMonologLevelIsATranslatableError(): void
    {
        $level = new Level(12345);

        $exception = null;
        try {
            $level->getMonologLevel();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('The log level code 12345 is invalid as a Monolog level.', $exception->getMessage());
    }
}
