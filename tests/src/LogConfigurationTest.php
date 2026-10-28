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
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\Processor;
use Derafu\Log\Translation\LogTranslationResourceProvider;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Derafu\Translation\TranslatorFactory;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Logger::class)]
#[UsesClass(Caller::class)]
#[UsesClass(Log::class)]
#[UsesClass(JournalHandler::class)]
#[UsesClass(Processor::class)]
#[UsesClass(LogTranslationResourceProvider::class)]
final class LogConfigurationTest extends TestCase
{
    /** @return iterable<string, array{array<array-key, mixed>, string}> */
    public static function channels(): iterable
    {
        yield 'missing' => [[], 'derafu_lib'];
        yield 'null' => [['channel' => null], 'derafu_lib'];
        yield 'empty string' => [['channel' => ''], ''];
        yield 'zero string' => [['channel' => '0'], '0'];
        yield 'custom' => [['channel' => 'billing'], 'billing'];
        yield 'spaces preserved' => [['channel' => ' billing '], ' billing '];
        yield 'unrelated configuration' => [['other' => ['option' => true]], 'derafu_lib'];
    }

    /** @param array<array-key, mixed> $configuration */
    #[DataProvider('channels')]
    public function testPreservesValidChannelsAndTheirExistingDefaults(array $configuration, string $expected): void
    {
        $logger = new Logger(configuration: $configuration);
        $logger->info('Channel test');
        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);
        $this->assertSame($expected, $record->getChannel());
        $this->assertSame('Channel test', $record->getMessage());
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidChannels(): iterable
    {
        yield 'false' => [false];
        yield 'true' => [true];
        yield 'integer' => [123];
        yield 'float' => [1.5];
        yield 'array' => [['channel']];
        yield 'object' => [new stdClass()];
    }

    #[DataProvider('invalidChannels')]
    public function testRejectsAnInvalidChannelOnEveryInitializationAttempt(mixed $channel): void
    {
        $logger = new Logger(configuration: ['channel' => $channel]);
        $translator = TranslatorFactory::create('es', resourceProviders: [new LogTranslationResourceProvider()]);
        $type = get_debug_type($channel);

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $logger->info('Invalid channel');
                $this->fail('An invalid channel must be rejected before initializing the logger.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame("The log channel must be a string; $type given.", $exception->getMessage());
                $this->assertSame("El canal de log debe ser un string; se recibió $type.", $exception->trans($translator));
            }
        }
    }

    /** @return iterable<string, array{int|string, mixed}> */
    public static function invalidHandlers(): iterable
    {
        foreach ([null, false, 123, 1.5, 'handler', [], new stdClass()] as $handler) {
            $type = get_debug_type($handler);
            yield "named key/$type" => ['broken', $handler];
            yield "numeric key/$type" => [4, $handler];
        }
    }

    #[DataProvider('invalidHandlers')]
    public function testRejectsAnInvalidHandlerWithoutPartiallyInitializingTheLogger(int|string $key, mixed $handler): void
    {
        $stream = fopen('php://temp', 'w+');
        $this->assertIsResource($stream);
        try {
            $logger = new Logger(handlers: ['valid' => new StreamHandler($stream), $key => $handler]);
            $translator = TranslatorFactory::create('es', resourceProviders: [new LogTranslationResourceProvider()]);
            $type = get_debug_type($handler);

            for ($attempt = 0; $attempt < 2; ++$attempt) {
                try {
                    $logger->info('Invalid handler');
                    $this->fail('An invalid handler must be rejected before initializing the logger.');
                } catch (InvalidArgumentException $exception) {
                    $this->assertSame("The log handler at key \"$key\" must implement HandlerInterface; $type given.", $exception->getMessage());
                    $this->assertSame("El handler de log con clave \"$key\" debe implementar HandlerInterface; se recibió $type.", $exception->trans($translator));
                }
            }

            rewind($stream);
            $this->assertSame('', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testPreservesTheExistingHandlerStackOrderAndWritesEachRecordOnce(): void
    {
        $stream = fopen('php://temp', 'w+');
        $this->assertIsResource($stream);
        try {
            $first = new StreamHandler($stream);
            $first->setFormatter(new LineFormatter("first:%message%\n"));
            $second = new StreamHandler($stream);
            $second->setFormatter(new LineFormatter("second:%message%\n"));
            $logger = new Logger(handlers: ['first' => $first, 'second' => $second]);

            $logger->info('One');
            $logger->info('Two');

            rewind($stream);
            $this->assertSame("second:One\nfirst:One\nsecond:Two\nfirst:Two\n", stream_get_contents($stream));
            $records = $logger->getJournal()->all();
            $this->assertCount(2, $records);
            $this->assertInstanceOf(Log::class, $records[0]);
            $this->assertInstanceOf(Log::class, $records[1]);
            $this->assertSame('One', $records[0]->getMessage());
            $this->assertSame('Two', $records[1]->getMessage());
        } finally {
            fclose($stream);
        }
    }

    public function testJournalIsAvailableBeforeWritingAndRemainsTheSameAfterWriting(): void
    {
        $logger = new Logger();
        $journal = $logger->getJournal();
        $this->assertSame([], $journal->all());
        $this->assertSame($journal, $logger->getJournal());
        $logger->info('First message');
        $this->assertSame($journal, $logger->getJournal());
        $this->assertCount(1, $journal->all());
    }

    public function testIterableHandlersPreserveTheExistingExecutionOrder(): void
    {
        $stream = fopen('php://temp', 'w+');
        $this->assertIsResource($stream);
        try {
            $first = new StreamHandler($stream);
            $first->setFormatter(new LineFormatter("first:%message%\n"));
            $second = new StreamHandler($stream);
            $second->setFormatter(new LineFormatter("second:%message%\n"));
            $handlers = static function () use ($first, $second): iterable {
                yield 'first' => $first;
                yield 'second' => $second;
            };
            $logger = new Logger(handlers: $handlers());
            $logger->info('One');
            $logger->info('Two');
            rewind($stream);
            $this->assertSame("second:One\nfirst:One\nsecond:Two\nfirst:Two\n", stream_get_contents($stream));
            $this->assertCount(2, $logger->getJournal()->all());
        } finally {
            fclose($stream);
        }
    }
}
