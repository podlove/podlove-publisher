<?php

use Podlove\Model\Job;
use Podlove\Modules\Contributors\Model\ContributorRole;

class BaseModelMassAssignmentTestModel extends \Podlove\Model\Base
{
    public function save()
    {
        return true;
    }
}

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

    public function testDeprecatedUpdateAttributesRemainsCompatible(): void
    {
        $model = new BaseModelMassAssignmentTestModel();
        $model->title = 'Before';
        $this->setExpectedDeprecated('Podlove\Model\Base::update_attributes');

        $result = $model->update_attributes([
            'title' => 'After',
        ]);

        $this->assertTrue($result);
        $this->assertSame('After', $model->title);
    }

    public function testDeprecatedCreateRemainsCompatible(): void
    {
        $this->setExpectedDeprecated('Podlove\Model\Base::create');

        $model = BaseModelMassAssignmentTestModel::create(['title' => 'Created']);

        $this->assertSame('Created', $model->title);
    }

    public function testJobToArrayRejectsSerializedObjects(): void
    {
        $job = new Job();
        $job->args = serialize((object) ['batch' => 10]);

        $data = $job->to_array();

        $this->assertNull($data['args']);
    }

    public function testCheckboxPostsExplicitBooleanValueWithoutGlobalMetadata(): void
    {
        $model = new BaseModelMassAssignmentTestModel();
        $model->enabled = 1;
        $builder = new \Podlove\Form\Input\Builder($model, 'settings');

        ob_start();
        $builder->checkbox('enabled', []);
        $html = ob_get_clean();

        $this->assertStringContainsString('name="settings[enabled]" value="0"', $html);
        $this->assertStringContainsString('name="settings[enabled]"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringNotContainsString('checkboxes[]', $html);
    }
}
