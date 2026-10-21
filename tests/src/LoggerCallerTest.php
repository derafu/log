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
use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\LineFormatter;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\Processor;

use function Derafu\TestsLog\Fixture\logFromFunction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Logger::class)]
#[UsesClass(Caller::class)]
#[UsesClass(Log::class)]
#[UsesClass(JournalHandler::class)]
#[UsesClass(LineFormatter::class)]
#[UsesClass(Processor::class)]
final class LoggerCallerTest extends TestCase
{
    public function testLogsFromAGlobalFunctionWithoutAClassOrCallType(): void
    {
        require_once dirname(__DIR__) . '/fixtures/log-from-function.php';
        $logger = new Logger(formatter: new LineFormatter('%message%'));

        $line = logFromFunction($logger);

        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(LogInterface::class, $record);
        $this->assertSame('Function log', $record->getMessage());
        $caller = $record->getCaller();
        $this->assertInstanceOf(Caller::class, $caller);
        $this->assertSame(dirname(__DIR__) . '/fixtures/log-from-function.php', $caller->file);
        $this->assertSame($line, $caller->line);
        $this->assertSame('Derafu\\TestsLog\\Fixture\\logFromFunction', $caller->function);
        $this->assertNull($caller->class);
        $this->assertNull($caller->type);
        $this->assertSame(
            'Function log in ' . $caller->file . ' on line ' . $line . ', called by Derafu\\TestsLog\\Fixture\\logFromFunction().',
            $record->getFormattedMessage(),
        );
    }

    public function testLogsFromARealScriptWithoutAContainingFunction(): void
    {
        $file = dirname(__DIR__) . '/fixtures/log-from-script.php';
        $process = proc_open([PHP_BINARY, $file], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process));
        $this->assertSame('', $stderr);
        $this->assertIsString($stdout);
        $result = json_decode($stdout, true);
        $this->assertIsArray($result);
        $this->assertSame('Script log', $result['message']);
        $this->assertSame($file, $result['file']);
        $this->assertIsInt($result['line']);
        $this->assertSame($result['expected_line'], $result['line']);
        $this->assertNull($result['function']);
        $this->assertNull($result['class']);
        $this->assertNull($result['type']);
        $this->assertSame('Script log in ' . $file . ' on line ' . $result['line'] . '.', $result['formatted']);
    }
}
