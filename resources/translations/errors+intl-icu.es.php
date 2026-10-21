<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    'The log journal record at key "{key}" must implement LogInterface; {type} given.' =>
        'El registro del journal de log con clave "{key}" debe implementar LogInterface; se recibió {type}.',
    'The log channel must be a string; {type} given.' =>
        'El canal de log debe ser un string; se recibió {type}.',
    'The log handler at key "{key}" must implement HandlerInterface; {type} given.' =>
        'El handler de log con clave "{key}" debe implementar HandlerInterface; se recibió {type}.',
    'The formatted log message must be string|null; {type} given.' =>
        'El mensaje de log formateado debe ser string|null; se recibió {type}.',
    'The log level {level} is not supported.' =>
        'El nivel de log {level} no está soportado.',
    'The log level code {code} is invalid as a Monolog level.' =>
        'El código de nivel de log {code} no es válido como nivel de Monolog.',
];
