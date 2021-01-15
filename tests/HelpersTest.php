<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Audit;

class HelpersTest extends AbstractTestCase
{
    public function test_audit()
    {
        $audit = audit('foo', 'bar');

        $this->assertInstanceOf(Audit::class, $audit);

        $this->assertEquals([['type' => 'foo', 'identifier' => 'bar']], $audit['entities']);
    }
}
