<?php

namespace Butler\Audit\Tests\Concerns;

use Butler\Audit\Concerns\IsAuditable;
use Butler\Audit\Contracts\Auditable;
use Butler\Audit\Tests\AbstractTestCase;
use Illuminate\Database\Eloquent\Model;

class IsAuditableTest extends AbstractTestCase
{
    private function makeAuditable(): Auditable
    {
        return new class implements Auditable
        {
            use IsAuditable;
        };
    }

    public function test_auditorType()
    {
        $this->assertStringContainsString(
            'isAuditableTest',
            $this->makeAuditable()->auditorType()
        );
    }

    public function test_auditorIdentifier_on_Auditable_model_return_its_id()
    {
        $model = new class extends Model implements Auditable
        {
            use IsAuditable;

            public $attributes = ['id' => 123];
        };

        $this->assertEquals(123, $model->auditorIdentifier());
    }

    public function test_auditorIdentifier_on_Auditable_without_its_own_auditorIdentifier_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Auditor entity identifier not found.');

        $this->makeAuditable()->auditorIdentifier();
    }
}
