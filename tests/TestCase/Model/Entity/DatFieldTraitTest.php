<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Entity;

use Cake\TestSuite\TestCase;

class DatFieldTraitTest extends TestCase
{
    public function testGet(): void
    {
        $e = new DatFieldEntity(['data' => ['test' => 'test']]);

        $this->assertEquals('test', $e->data['test']);
        $this->assertEquals('test', $e->get('test@data'));
        $this->assertEquals('test', $e->{'test@data'});
        $this->assertEquals('test', $e->get('data->test'));
        $this->assertEquals('test', $e->{'data->test'});
        $this->assertEquals('test', $e['data->test']);

        // Checks that reference is kept
        $v1 = &$e->get('data')['test'];
        $v2 = &$e->get('test@data');
        $v1 = 'updated';
        $this->assertEquals('updated', $v2);
        $this->assertEquals('updated', $e->{'data->test'});

        $this->assertNull($e->get('data->silly'));
    }

    public function testSet(): void
    {
        $e = new DatFieldEntity(['data' => ['test' => 'test']]);

        $e->clean();
        $this->assertFalse($e->isDirty());
        $this->assertSame(['test' => 'test'], $e->getOriginal('data'));

        $e->set('data->test', 'updated');
        $this->assertEquals('updated', $e->{'data->test'});

        $this->assertTrue($e->isDirty());
        $this->assertTrue($e->isDirty('data'));
        $this->assertTrue($e->isDirty('data->test'));
        $this->assertSame(['test' => 'test'], $e->getOriginal('data'));

        $e->clean();
        $this->assertFalse($e->isDirty());
        $this->assertSame(['test' => 'updated'], $e->getOriginal('data'));

        $e->set('data->new', 'created');
        $this->assertEquals('updated', $e->{'data->test'});
        $this->assertEquals('created', $e->{'data->new'});
        $this->assertTrue($e->isDirty());
        $this->assertTrue($e->isDirty('data'));
        $this->assertTrue($e->isDirty('data->new'));
        $this->assertFalse($e->isDirty('data->test'));
        $this->assertSame(['test' => 'updated'], $e->getOriginal('data'));
    }

    public function testHas(): void
    {
        $object = new DatFieldEntity(['attributes' => ['test' => true, 'test2' => null]]);

        $this->assertTrue($object->has('attributes'));
        $this->assertTrue($object->has('attributes->test'));
        $this->assertFalse($object->has('attributes->test2'));
        $this->assertFalse($object->has('attributes->test3 '));
    }

    public function testDelete(): void
    {
        $object = new DatFieldEntity(['attributes' => ['test' => true, 'test2' => null]]);
        $object->clean();

        $this->assertFalse($object->isDirty('attributes'));
        $object->delete('attributes->test3');
        $this->assertFalse($object->isDirty('attributes'));
        $object->delete('attributes->test2');
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertSame(['test' => true], $object->attributes);
    }

    public function testUnset(): void
    {
        $object = new DatFieldEntity(['attributes' => ['test' => true, 'test2' => null]]);
        $object->clean();

        $this->assertFalse($object->isDirty('attributes'));
        $object->unset('attributes->test3');
        $this->assertFalse($object->isDirty('attributes'));
        $object->unset('attributes->test2');
        $this->assertSame(['test' => true], $object->attributes);
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->has('attributes->test2'));
    }

    public function testDirtinessHandling(): void
    {
        $object = new DatFieldEntity(['attributes' => ['test' => true, 'test2' => true]]);

        $this->assertTrue($object->isDirty());
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->clean();
        $this->assertFalse($object->isDirty());
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->set('attributes->test', false);
        $this->assertTrue($object->isDirty());
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertTrue($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertTrue($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->setDirty('attributes->test', false);
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->set('attributes->test', false);
        $object->setDirty('attributes', false);
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));
    }

    public function testAccessibility(): void
    {
        $object = new DatFieldEntity(['attributes' => ['test' => true]]);

        $object->setAccess('*', true);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));

        $object->setAccess('*', false);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));
        $object->set('attributes->test', false, ['guard' => true]);
        $this->assertTrue($object->get('attributes->test'));

        $object->setAccess('attributes', true);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));

        $object->setAccess('attributes->test', false);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));

        $object->setAccess('attributes', false);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));

        $object->setAccess('attributes->test', true);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));
    }
}
