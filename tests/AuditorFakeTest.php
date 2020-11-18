<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use Butler\Audit\Testing\AuditData;
use Butler\Audit\Testing\AuditorFake;
use Illuminate\Support\Collection;
use PHPUnit\Framework\ExpectationFailedException;

class AuditorFakeTest extends AbstractTestCase
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
        Auditor::fake()->assertNothingLogged();
    }

    public function test_assertNothingLogged_sad_path()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Audit events were logged unexpectedly.');

        $this->makeAuditor()->assertNothingLogged();
    }

    public function test_logged_with_string()
    {
        $result = $this->makeAuditor()->logged('eventName');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
    }

    public function test_logged_with_callback()
    {
        $result = $this->makeAuditor()->logged('eventName', function (AuditData $data) {
            return $data->initiator === 'phpunit';
        });

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
    }

    private function makeAuditor(): AuditorFake
    {
        return tap(Auditor::fake(), function ($auditor) {
            $auditor->entity('entityType', 'entity-id')
                ->event('eventName')
                ->initiator('phpunit')
                ->log();
        });
    }
}
