<?php

namespace Butler\Audit\Facades;

use Butler\Audit\Auditor as AuditorClass;
use Butler\Audit\Testing\AuditorFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Butler\Audit\Auditor entity(string|array|\Butler\Audot\Contracts\Auditable $type, mixed $identifier)
 * @method static \Butler\Audit\Auditor event(string $type, array $context = [])
 * @method static \Butler\Audit\Auditor eventContext(string $key, mixed $value)
 * @method static \Butler\Audit\Auditor initiator(string $initiator, array $context = [])
 * @method static \Butler\Audit\Auditor initiatorContext(string $key, mixed $value)
 * @method static void log(string $event, array $eventContext = [])
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

    public static function fake(): AuditorFake
    {
        static::swap($fake = new AuditorFake('uuid'));

        return $fake;
    }
}
