<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Audit;
use Butler\Audit\Contracts\Auditable;
use Butler\Audit\Facades\Auditor;
use Butler\Audit\Testing\AuditData;
use Exception;
use Illuminate\Support\Carbon;

class AuditTest extends AbstractTestCase
{
    /**
     * @dataProvider entityProvider
     */
    public function test_entity($type, $id, $expectedEntities)
    {
        $audit = $this->makeAudit()->entity($type, $id);

        $this->assertEquals($expectedEntities, $audit['entities']);
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

        $this->makeAudit()->entity('foo');
    }

    public function test_event()
    {
        $audit = $this->makeAudit()->event('name');

        $this->assertEquals('name', $audit['event']);
    }

    public function test_event_with_context()
    {
        $audit = $this->makeAudit()->event('name', ['foo' => 'bar']);

        $this->assertEquals('name', $audit['event']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $audit['eventContext']);
    }

    public function test_eventContext_with_valid_values()
    {
        $audit = $this->makeAudit()->eventContext('foo', 'bar');
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $audit['eventContext']);

        $audit = $this->makeAudit()->eventContext('foo', 123);
        $this->assertEquals([['key' => 'foo', 'value' => 123]], $audit['eventContext']);

        $audit = $this->makeAudit()->eventContext('foo', null);
        $this->assertEquals([['key' => 'foo', 'value' => null]], $audit['eventContext']);
    }

    public function test_eventContext_throws_exception_for_invalid_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Event context value must be null or scalar.');

        $this->makeAudit()->eventContext('foo', ['bar' => 'baz']);
    }

    public function test_initiator()
    {
        $audit = $this->makeAudit()->initiator('api');

        $this->assertEquals('api', $audit['initiator']);
    }

    public function test_initiator_with_context()
    {
        $audit = $this->makeAudit()->initiator('api', ['foo' => 'bar']);

        $this->assertEquals('api', $audit['initiator']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $audit['initiatorContext']);
    }

    public function test_initiatorContext_with_valid_values()
    {
        $audit = $this->makeAudit()->initiatorContext('foo', 'bar');
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $audit['initiatorContext']);

        $audit = $this->makeAudit()->initiatorContext('foo', 123);
        $this->assertEquals([['key' => 'foo', 'value' => 123]], $audit['initiatorContext']);

        $audit = $this->makeAudit()->initiatorContext('foo', null);
        $this->assertEquals([['key' => 'foo', 'value' => null]], $audit['initiatorContext']);
    }

    public function test_initiatorContext_throws_exception_for_invalid_value()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Initiator context value must be null or scalar.');

        $this->makeAudit()->initiatorContext('foo', ['bar' => 'baz']);
    }

    public function test_initiatorResolver_is_used_when_set()
    {
        Auditor::initiatorResolver(fn () => ['api', ['ip' => '1.2.3.4']]);

        $array = $this->makeAudit()->event('foo')->entity('bar', 1)->toArray();

        $this->assertEquals('api', $array['initiator']);
        $this->assertEquals([['key' => 'ip', 'value' => '1.2.3.4']], $array['initiatorContext']);
    }

    public function test_initiator_can_be_overriden_when_initiatorResolver_is_used()
    {
        Auditor::initiatorResolver(fn () => ['api1']);

        $array = $this->makeAudit()
            ->event('foo')
            ->entity('bar', 1)
            ->initiator('api2', ['ip' => '2.3.4.5'])
            ->toArray();

        $this->assertEquals('api2', $array['initiator']);
        $this->assertEquals([['key' => 'ip', 'value' => '2.3.4.5']], $array['initiatorContext']);
    }

    public function test_log_sends_correct_data_to_auditor()
    {
        Carbon::setTestNow('2020-07-01 12:00:00');

        $this->makeAudit()
            ->entity('entityType', 'entity-id')
            ->event('foobarbaz', ['foo' => 'bar'])
            ->eventContext('foo', 'baz')
            ->initiator('api', ['ip' => '1.2.3.4'])
            ->initiatorContext('userAgent', 'lynx')
            ->log();

        Auditor::assertLogged('foobarbaz', function (AuditData $data) {
            return $data->correlationId === 'uuid'
                && $data->hasEntity('entityType', 'entity-id')
                && $data->eventContext('foo', 'bar')
                && $data->eventContext('foo', 'baz')
                && $data->initiator === 'api'
                && $data->hasInitiatorContext('ip', '1.2.3.4')
                && $data->hasInitiatorContext('userAgent', 'lynx')
                && $data->occurredAt === now()->toRfc3339String();
        });
    }

    public function test_log_can_set_event_and_eventContext()
    {
        $audit = $this->makeAudit()
            ->event('name1')
            ->initiator('api')
            ->entity('type', 'id');

        $audit->log('name2', ['foo' => 'bar']);

        $this->assertEquals('name2', $audit['event']);
        $this->assertEquals([['key' => 'foo', 'value' => 'bar']], $audit['eventContext']);

        Auditor::assertLogged('name2');
    }

    public function test_toArray_throws_exception_if_event_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Event is required.');

        $this->makeAudit()->toArray();
    }

    public function test_toArray_throws_exception_if_entities_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('At least one entity is required.');

        $this->makeAudit()
            ->event('foo')
            ->toArray();
    }

    public function test_toArray_throws_exception_if_initiator_is_empty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Initiator is required.');

        $this->makeAudit()
            ->event('foo')
            ->entity('type', 'id')
            ->toArray();
    }

    public function test_ArrayAccess_offsetSet_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Audit data may not be mutated using array access.');

        $this->makeAudit()['event'] = null;
    }

    public function test_ArrayAccess_offsetExists()
    {
        $audit = $this->makeAudit()->event('name');

        $this->assertTrue(isset($audit['event']));
    }

    public function test_ArrayAccess_offsetUnset_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Audit data may not be mutated using array access.');

        unset($this->makeAudit()['event']);
    }

    public function test_ArrayAccess_offsetGet()
    {
        $this->assertEquals('name', $this->makeAudit()->event('name')['event']);
        $this->assertNull($this->makeAudit()['foobar']);
    }

    public function test_magic_call_sets_prefixed_event_and_eventContext()
    {
        $audit = $this->makeAudit()
            ->entity('entityType', 'entityId')
            ->initiator('api');

        $audit->fooBar(['contextKey' => 'context value']);

        $this->assertEquals('entityType.fooBar', $audit['event']);
        $this->assertEquals(
            [['key' => 'contextKey', 'value' => 'context value']],
            $audit['eventContext']
        );

        Auditor::assertLogged('entityType.fooBar');
    }

    public function test_magic_call_handles_Collection_as_context()
    {
        $audit = $this->makeAudit()
            ->entity('entityType', 'entityId')
            ->initiator('api');

        $audit->fooBar(collect(['contextKey' => 'context value']));

        $this->assertEquals(
            [['key' => 'contextKey', 'value' => 'context value']],
            $audit['eventContext']
        );

        Auditor::assertLogged('entityType.fooBar');
    }

    public function test_magic_call_with_two_entities_with_same_type_prefixes_event()
    {
        $audit = $this->makeAudit()
            ->entity('foo', 1)
            ->entity('foo', 2)
            ->initiator('api');

        $audit->barBaz();

        $this->assertEquals('foo.barBaz', $audit['event']);

        Auditor::assertLogged('foo.barBaz');
    }

    public function test_magic_call_with_two_entities_with_different_type_dont_prefixes_event()
    {
        $auditor = $this->makeAudit()
            ->entity('foo', 1)
            ->entity('bar', 2)
            ->initiator('api');

        $auditor->baz();

        $this->assertEquals('baz', $auditor['event']);

        Auditor::assertLogged('baz');
    }

    private function makeAudit(string $correlationId = 'uuid'): Audit
    {
        $auditor = tap(Auditor::fake())->correlationId($correlationId);

        return new Audit($auditor);
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
