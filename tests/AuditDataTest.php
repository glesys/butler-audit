<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Testing\AuditData;
use PHPUnit\Framework\TestCase;

class AuditDataTest extends TestCase
{
    private $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = $this->makeAuditData();
    }

    public function test_fluent_attributes()
    {
        $this->assertEquals('uuid', $this->data->correlationId);
        $this->assertEquals(0, $this->data->correlationDepth);
        $this->assertEquals('eventName', $this->data->event);
        $this->assertEquals('phpunit', $this->data->initiator);
        $this->assertEquals('2020-11-19T10:38:34+00:00', $this->data->occurredAt);
    }

    public function test_hasEventContext()
    {
        $this->assertTrue($this->data->hasEventContext('eventContext', 1));
        $this->assertFalse($this->data->hasEventContext('eventContext', 2));
    }

    public function test_hasInitiatorContext()
    {
        $this->assertTrue($this->data->hasInitiatorContext('ip', '1.1.1.1'));
        $this->assertFalse($this->data->hasInitiatorContext('ip', '2.2.2.2'));
    }

    public function test_hasEntity()
    {
        $this->assertTrue($this->data->hasEntity('entityType', 'entity-id'));
        $this->assertFalse($this->data->hasEntity('foo', 'bar'));
    }

    private function makeAuditData(): AuditData
    {
        return new AuditData([
            'correlationId' => 'uuid',
            'correlationDepth' => 0,
            'entities' => [
                [
                    'type' => 'entityType',
                    'identifier' => 'entity-id',
                ],
            ],
            'event' => 'eventName',
            'eventContext' => [
                [
                    'key' => 'eventContext',
                    'value' => 1,
                ],
            ],
            'initiator' => 'phpunit',
            'initiatorContext' => [
                [
                    'key' => 'ip',
                    'value' => '1.1.1.1',
                ],
            ],
            'occurredAt' => '2020-11-19T10:38:34+00:00',
        ]);
    }
}
