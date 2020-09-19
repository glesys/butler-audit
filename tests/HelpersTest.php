<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Auditor;

class HelpersTest extends AbstractTestCase
{
    public function test_audit()
    {
        $auditor = audit('foo', 'bar');

        $this->assertInstanceOf(Auditor::class, $auditor);

        $this->assertEquals([['type' => 'foo', 'identifier' => 'bar']], $auditor['entities']);
    }
}
