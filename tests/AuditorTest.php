<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Audit;
use Butler\Audit\Auditor;
use Butler\Audit\Testing\AuditData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\ExpectationFailedException;

class AuditorTest extends AbstractTestCase
{
    public function test_assertLogged_happy_path()
    {
        $this->makeAuditor()->assertLogged('eventName');

        $this->makeAuditor()->assertLogged('eventName', function ($data) {
            return $data->initiator === 'phpunit';
        });
    }

    public function test_assertLogged_sad_path()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected audit event [foobar] was not logged.');

        $this->makeAuditor()->assertLogged('foobar');
    }

    public function test_assertNotLogged_happy_path()
    {
        $this->makeAuditor()->assertNotLogged('foobar');

        $this->makeAuditor()->assertNotLogged('eventName', function ($data) {
            return $data->initiator === 'foobar';
        });
    }

    public function test_assertNotLogged_sad_path()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('A unexpected audit event [eventName] was logged.');

        $this->makeAuditor()->assertNotLogged('eventName');
    }

    public function test_assertNothingLogged_happy_path()
    {
        tap(new Auditor())->fake()->assertNothingLogged();
    }

    public function test_assertNothingLogged_sad_path()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Audit events were logged unexpectedly.');

        $this->makeAuditor()->assertNothingLogged();
    }

    public function test_assertLoggedCount_happy_path()
    {
        $this->makeAuditor()->assertLoggedCount(1);
    }

    public function test_assertLoggedCount_sad_path()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that actual size 1 matches expected size 2.');

        $this->makeAuditor()->assertLoggedCount(2);
    }

    public function test_recorded_with_string()
    {
        $result = $this->makeAuditor()->recorded('eventName');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
    }

    public function test_recorded_with_callback()
    {
        $result = $this->makeAuditor()->recorded(
            'eventName',
            fn (AuditData $data) => $data->initiator === 'phpunit'
        );

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
    }

    public function test_correlationId_returns_value_from_http_header()
    {
        request()->headers->set('X-Correlation-ID', $uuid = Str::uuid());

        $this->assertEquals($uuid, $this->makeAuditor()->correlationId());
    }

    public function test_correlationId_returns_uuid_if_http_header_is_not_set()
    {
        $this->assertTrue(Str::isUuid($this->makeAuditor()->correlationId()));
    }

    public function test_correlationId_can_be_resetted()
    {
        $auditor = $this->makeAuditor();

        $id1 = $auditor->correlationId();
        $id2 = $auditor->correlationId(null);

        $this->assertTrue(Str::isUuid($id1));
        $this->assertTrue(Str::isUuid($id2));
        $this->assertNotEquals($id1, $id2);
    }

    public function test_correlationId_can_be_set_manually()
    {
        $auditor = $this->makeAuditor();

        $this->assertEquals('not a uuid', $auditor->correlationId('not a uuid'));
        $this->assertEquals('not a uuid', $auditor->correlationId());
    }

    public function test_initiatorResolver_can_be_set()
    {
        $auditor = $this->makeAuditor();

        $auditor->initiatorResolver(fn () => ['foo', 'bar']);

        $this->assertEquals(['foo', 'bar'], value($auditor->initiatorResolver()));
    }

    public function test_initiatorResolver_can_be_unset()
    {
        $auditor = $this->makeAuditor();

        $auditor->initiatorResolver(fn () => ['foo']);
        $auditor->initiatorResolver(null);

        $this->assertNull($auditor->initiatorResolver());
    }

    private function makeAuditor(): Auditor
    {
        $auditor = (new Auditor())->fake();

        (new Audit($auditor))
            ->entity('entityType', 'entity-id')
            ->event('eventName')
            ->initiator('phpunit')
            ->log();

        return $auditor;
    }
}
