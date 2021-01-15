<?php

namespace Butler\Audit\Facades;

use Butler\Audit\Auditor as AuditorClass;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Butler\Audit\Audit entity(string|array|\Butler\Audot\Contracts\Auditable $type, mixed $identifier)
 * @method static \Butler\Audit\Audit event(string $type, array $context = [])
 * @method static \Butler\Audit\Audit eventContext(string $key, mixed $value)
 * @method static \Butler\Audit\Audit initiator(string $initiator, array $context = [])
 * @method static \Butler\Audit\Audit initiatorContext(string $key, mixed $value)
 * @method static void log(Audit $audit)
 * @method static void assertLogged(string $eventName, \Closure $callback = null)
 * @method static void assertNotLogged(string $eventName, \Closure $callback = null)
 * @method static void assertNothingLogged()
 *
 * @see \Butler\Audit\Auditor
 */
class Auditor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuditorClass::class;
    }
}
