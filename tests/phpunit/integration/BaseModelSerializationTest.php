<?php

use Podlove\Model\Job;
use Podlove\Modules\Contributors\Model\ContributorRole;

/**
 * @internal
 *
 * @coversNothing
 */
class BaseModelSerializationTest extends WP_UnitTestCase
{
    public function testBaseToArrayReturnsSerializedStringsUnchanged(): void
    {
        $payload = serialize(['message' => 'hello']);
        $role = new ContributorRole();
        $role->title = $payload;

        $data = $role->to_array();

        $this->assertSame($payload, $data['title']);
    }

    public function testJobToArrayDecodesSerializedArgsAndState(): void
    {
        $job = new Job();
        $job->args = serialize(['batch' => 10]);
        $job->state = serialize(['offset' => 20]);

        $data = $job->to_array();

        $this->assertSame(['batch' => 10], $data['args']);
        $this->assertSame(['offset' => 20], $data['state']);
    }
}
