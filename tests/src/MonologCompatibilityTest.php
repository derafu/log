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
use Derafu\Container\Journal;
use Derafu\Log\Caller;
use Derafu\Log\Level as DerafuLevel;
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\Processor;
use JsonSerializable;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Stringable;

#[CoversClass(Log::class)]
#[CoversClass(Processor::class)]
#[CoversClass(Logger::class)]
#[CoversClass(JournalHandler::class)]
#[UsesClass(Caller::class)]
#[UsesClass(DerafuLevel::class)]
final class MonologCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{mixed}> */
    public static function contextValues(): iterable
    {
        yield 'null' => [null];
        yield 'integer' => [123];
        yield 'zero' => [0];
        yield 'application code' => ['PAYMENT_FAILED'];
        yield 'numeric string' => ['123'];
        yield 'false' => [false];
        yield 'float' => [1.5];
        yield 'array' => [['nested' => ['code' => 500]]];
        yield 'object' => [new stdClass()];
        yield 'caller in consumer context' => [new Caller(null, null, 'consumer', null, null)];
    }

    #[DataProvider('contextValues')]
    public function testProcessorPreservesConsumerContextAndEveryOriginalField(mixed $value): void
    {
        $context = ['code' => $value, '__caller' => $value, 'other' => $value];
        $extra = ['request_id' => new stdClass()];
        $formatted = new stdClass();
        $original = new LogRecord(
            datetime: new DateTimeImmutable('2026-10-21T10:00:00+00:00'),
            channel: 'external',
            level: Level::Warning,
            message: 'External message',
            context: $context,
            extra: $extra,
            formatted: $formatted,
        );

        $record = (new Processor())($original);

        $this->assertSame($original->datetime, $record->getDateTime());
        $this->assertSame($original->channel, $record->getChannel());
        $this->assertSame($original->message, $record->getMessage());
        $this->assertSame(Level::Warning, $record->getMonologLevel());
        $this->assertSame(Level::Warning->value, $record->getCode());
        $this->assertSame(Level::Warning->value, $record->getLevel()->getCode());
        $this->assertSame($context, $record->getContext());
        $this->assertSame($extra, $record->getExtra());
        $this->assertSame($formatted, $record->getFormattedRecord());
        $this->assertNull($record->getCaller());
        $this->assertSame($context, $original->context);
        $this->assertSame($extra, $original->extra);
    }

    #[DataProvider('contextValues')]
    public function testDerafuLoggerKeepsReservedLookingKeysAsConsumerData(mixed $value): void
    {
        $context = ['code' => $value, '__caller' => $value];
        $logger = new Logger(formatter: new LineFormatter('%message%'));

        $line = __LINE__ + 1;
        $logger->error('Failed payment', $context);

        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);
        $this->assertSame($context, $record->getContext());
        $this->assertSame(Level::Error->value, $record->getCode());
        $caller = $record->getCaller();
        $this->assertInstanceOf(Caller::class, $caller);
        $this->assertSame(__FILE__, $caller->file);
        $this->assertSame($line, $caller->line);
        $this->assertSame(__FUNCTION__, $caller->function);
        $this->assertSame($caller, $record->getExtra()[Caller::class]);
    }

    public function testJournalHandlerWorksWithARealExternalMonologLogger(): void
    {
        $journal = new Journal();
        $handler = new JournalHandler($journal);
        $handler->setFormatter(new LineFormatter('%message%'));
        $logger = new MonologLogger('third-party', [$handler]);
        $logger->pushProcessor(static function (LogRecord $record): LogRecord {
            $record->extra['request_id'] = 'request-123';

            return $record;
        });
        $context = ['code' => 'PAYMENT_FAILED', '__caller' => ['external' => true]];

        $logger->error('External failure', $context);

        $records = $journal->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);
        $this->assertSame($context, $record->getContext());
        $this->assertSame(['request_id' => 'request-123'], $record->getExtra());
        $this->assertSame('External failure', $record->getFormattedMessage());
        $this->assertSame(Level::Error->value, $record->getCode());
        $this->assertNull($record->getCaller());
    }

    #[DataProvider('contextValues')]
    public function testUnrecognizedExtraMetadataIsPreservedWithoutRejectingExternalRecords(mixed $value): void
    {
        $extra = [Caller::class => $value, 'other' => new stdClass()];
        $original = new LogRecord(new DateTimeImmutable(), 'external', Level::Info, 'Message', extra: $extra);

        $record = (new Processor())($original);

        $this->assertSame($extra, $record->getExtra());
        $this->assertSame($value instanceof Caller ? $value : null, $record->getCaller());
        $this->assertSame($extra, $original->extra);
    }

    public function testResourceValuesRemainAvailableInConsumerContext(): void
    {
        $resource = fopen('php://temp', 'w+');
        $this->assertIsResource($resource);
        try {
            $logger = new Logger(formatter: new LineFormatter('%message%'));
            $context = ['code' => $resource, '__caller' => $resource];
            $logger->info('Resource context', $context);
            $records = $logger->getJournal()->all();
            $this->assertCount(1, $records);
            $record = $records[0];
            $this->assertInstanceOf(Log::class, $record);
            $this->assertSame($context, $record->getContext());
            $this->assertSame(Level::Info->value, $record->getCode());
        } finally {
            fclose($resource);
        }
    }

    public function testCallerMetadataIsCleanedUpWhenWritingThrows(): void
    {
        $logger = new Logger();
        $value = new class () implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                throw new RuntimeException('Message could not be rendered.');
            }
        };

        try {
            $logger->info('Failed serialization', ['value' => $value]);
            $this->fail('Serializing the context value must throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Message could not be rendered.', $exception->getMessage());
        }

        $line = __LINE__ + 1;
        $logger->warning('Recovered');
        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);
        $caller = $record->getCaller();
        $this->assertInstanceOf(Caller::class, $caller);
        $this->assertSame($line, $caller->line);
        $this->assertSame('Recovered', $record->getMessage());
    }

    public function testNestedLoggingDuringFormattingKeepsEachRecordsOwnCaller(): void
    {
        $logger = new Logger();
        $value = new class ($logger) implements Stringable {
            public function __construct(private Logger $logger)
            {
            }

            public function __toString(): string
            {
                $this->logger->info('Nested message');

                return 'Context value';
            }
        };

        $line = __LINE__ + 1;
        $logger->warning('Outer message', ['value' => $value]);
        $records = $logger->getJournal()->all();
        $this->assertCount(2, $records);
        $nested = $records[0];
        $outer = $records[1];
        $this->assertInstanceOf(Log::class, $nested);
        $this->assertInstanceOf(Log::class, $outer);
        $this->assertSame('Nested message', $nested->getMessage());
        $this->assertSame('Outer message', $outer->getMessage());
        $this->assertNotNull($nested->getCaller());
        $this->assertNotNull($outer->getCaller());
        $this->assertSame('__toString', $nested->getCaller()->function);
        $this->assertSame(__FUNCTION__, $outer->getCaller()->function);
        $this->assertSame($line, $outer->getCaller()->line);
        $this->assertNotSame($nested->getCaller(), $outer->getCaller());
    }
}
