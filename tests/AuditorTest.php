<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Auditor;
use Butler\Audit\Contracts\Auditable;
use Butler\Audit\Jobs\Audit as AuditJob;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

class AuditorTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2020-07-01 12:00:00');

        Queue::fake();
    }

    /**
     * @dataProvider entityProvider
     */
    public function test_entity($type, $id, $expectedEntities)
    {
        $auditor = $this->makeAuditor()->entity($type, $id);

        $this->assertEquals($expectedEntities, $auditor['entities']);
    }

    public function entityProvider()
    {
        return [
            'string type and string id' => [
                'foo',
                'bar',
                [['type' => 'foo', 'identifier' => 'bar']],
            ],
            'string type and array of ids' => [
                'foo',
                [1, 2],
                [
                    ['type' => 'foo', 'identifier' => 1],
                    ['type' => 'foo', 'identifier' => 2],
                ],
            ],
            'Auditable type' => [
                $this->makeAuditable('foo', 123),
                null,
                [['type' => 'foo', 'identifier' => 123]],
            ],
            'array with string key and string value' => [
                ['foo' => 'bar'],
                null,
                [['type' => 'foo', 'identifier' => 'bar']],
            ],
            'array with string type and array values' => [
                ['foo' => [1, 2]],
                null,
                [
                    ['type' => 'foo', 'identifier' => 1],
                    ['type' => 'foo', 'identifier' => 2],
                ],
            ],
            'array with Auditables' => [
                [
                    $this->makeAuditable('server', 123),
                    $this->makeAuditable('backup', 'abc'),
                ],
                null,
                [
                    ['type' => 'server', 'identifier' => 123],
                    ['type' => 'backup', 'identifier' => 'abc'],
                ],
            ],
        ];
    }

    public function test_entity_throws_exception_if_used_incorreclty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid entity.');

        $this->makeAuditor()->entity('foo');
    }

    public function test_event()
    {
        $auditor = $this->makeAuditor()->event('name');

        $this->assertEquals('name', $auditor['event']);
    }

    public function test_event_with_context()
    {
        $auditor = $this->makeAuditor()->event('name', ['foo' => 'bar']);

        $this->assertEquals('name', $auditor['event']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $auditor['eventContext']);
    }

    public function test_eventContext()
    {
        $auditor = $this->makeAuditor()->eventContext('foo', 'bar');

        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $auditor['eventContext']);
    }

    public function test_initiator()
    {
        $auditor = $this->makeAuditor()->initiator('api');

        $this->assertEquals('api', $auditor['initiator']);
    }

    public function test_initiator_with_context()
    {
        $auditor = $this->makeAuditor()->initiator('api', ['foo' => 'bar']);

        $this->assertEquals('api', $auditor['initiator']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $auditor['initiatorContext']);
    }

    public function test_initiatorContext()
    {
        $auditor = $this->makeAuditor()->initiatorContext('foo', 'bar');

        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $auditor['initiatorContext']);
    }

    public function test_initiatorResolver_is_used_when_set_by_setInitiatorResolver()
    {
        Auditor::setInitiatorResolver(fn () => ['api', ['ip' => '1.2.3.4']]);

        $auditor = $this->makeAuditor();

        $this->assertEquals('api', $auditor['initiator']);
        $this->assertEquals([['key' => 'ip', 'value' => '1.2.3.4']], $auditor['initiatorContext']);
    }

    public function test_initiator_can_be_overriden_when_initiatorResolver_is_used()
    {
        Auditor::setInitiatorResolver(fn () => ['api1']);

        $auditor = $this->makeAuditor()->initiator('api2');

        $this->assertEquals('api2', $auditor['initiator']);
    }

    public function test_log_queues_job_with_correct_data()
    {
        $this->makeAuditor()
            ->entity('entityType', 'entity-id')
            ->event('foobarbaz', ['foo' => 'bar'])
            ->eventContext('foo', 'baz')
            ->initiator('api', ['ip' => '1.2.3.4'])
            ->initiatorContext('userAgent', 'lynx')
            ->log();

        Queue::assertPushed(fn (AuditJob $job) => $job->data === [
            'correlationId' => 'uuid',
            'entities' => [
                [
                    'type' => 'entityType',
                    'identifier' => 'entity-id',
                ],
            ],
            'event' => 'foobarbaz',
            'eventContext' => [
                [
                    'key' => 'foo',
                    'value' => 'bar',
                ],
                [
                    'key' => 'foo',
                    'value' => 'baz',
                ],
            ],
            'initiator' => 'api',
            'initiatorContext' => [
                [
                    'key' => 'ip',
                    'value' => '1.2.3.4',
                ],
                [
                    'key' => 'userAgent',
                    'value' => 'lynx',
                ],
            ],
            'occurredAt' => now()->toRfc3339String(),
        ]);
    }

    public function test_log_can_set_event_and_eventContext()
    {
        $auditor = $this->makeAuditor()
            ->event('event1')
            ->initiator('api')
            ->entity('type', 'id');

        $auditor->log('event2', ['foo' => 'bar']);

        $this->assertEquals('event2', $auditor['event']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $auditor['eventContext']);

        Queue::assertPushed(AuditJob::class);
    }

    public function test_log_throws_exception_if_event_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Event is required.');

        $this->makeAuditor()->log();

        Queue::assertNothingPushed();
    }

    public function test_log_throws_exception_if_entities_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('At least one entity is required.');

        $this->makeAuditor()
            ->event('foo')
            ->log();

        Queue::assertNothingPushed();
    }

    public function test_log_throws_exception_if_initiator_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Initiator is required.');

        $this->makeAuditor()
            ->event('foo')
            ->entity('type', 'id')
            ->log();

        Queue::assertNothingPushed();
    }

    public function test_ArrayAccess_offsetSet_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Auditor data may not be mutated using array access.');

        $this->makeAuditor()['correlationId'] = null;
    }

    public function test_ArrayAccess_offsetExists()
    {
        $this->assertTrue(isset($this->makeAuditor()['correlationId']));
    }

    public function test_ArrayAccess_offsetUnset_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Auditor data may not be mutated using array access.');

        unset($this->makeAuditor()['correlationId']);
    }

    public function test_ArrayAccess_offsetGet()
    {
        $this->assertEquals('uuid', $this->makeAuditor()['correlationId']);
        $this->assertNull($this->makeAuditor()['foobar']);
    }

    public function test_magic_call_sets_prefixed_event_and_eventContext()
    {
        $auditor = $this->makeAuditor()
            ->entity('entityType', 'entityId')
            ->initiator('api');

        $auditor->fooBar(['contextKey' => 'context value']);

        $this->assertEquals('entityType.fooBar', $auditor['event']);
        $this->assertEquals(
            [['key' => 'contextKey', 'value' => 'context value']],
            $auditor['eventContext']
        );

        Queue::assertPushed(AuditJob::class);
    }

    public function test_magic_call_handles_Collection_as_context()
    {
        $auditor = $this->makeAuditor()
            ->entity('entityType', 'entityId')
            ->initiator('api');

        $auditor->fooBar(collect(['contextKey' => 'context value']));

        $this->assertEquals(
            [['key' => 'contextKey', 'value' => 'context value']],
            $auditor['eventContext']
        );

        Queue::assertPushed(AuditJob::class);
    }

    public function test_magic_call_with_two_entities_with_same_type_prefixes_event()
    {
        $auditor = $this->makeAuditor()
            ->entity('foo', 1)
            ->entity('foo', 2)
            ->initiator('api');

        $auditor->barBaz();

        $this->assertEquals('foo.barBaz', $auditor['event']);

        Queue::assertPushed(AuditJob::class);
    }

    public function test_magic_call_with_two_entities_with_different_type_dont_prefixes_event()
    {
        $auditor = $this->makeAuditor()
            ->entity('foo', 1)
            ->entity('bar', 2)
            ->initiator('api');

        $auditor->baz();

        $this->assertEquals('baz', $auditor['event']);

        Queue::assertPushed(AuditJob::class);
    }

    private function makeAuditor(string $correlationId = 'uuid'): Auditor
    {
        return new Auditor($correlationId);
    }

    private function makeAuditable(string $type = 'type', $identifier = 1): Auditable
    {
        return new class ($type, $identifier) implements Auditable
        {
            public function __construct(string $type, $identifier)
            {
                $this->type = $type;
                $this->identifier = $identifier;
            }

            public function auditorType(): string
            {
                return $this->type;
            }

            public function auditorIdentifier()
            {
                return $this->identifier;
            }
        };
    }
}
