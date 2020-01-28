<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

class AssociationsTest extends TestCase
{
    public $Profiles;
    public $Users;
    public $fixtures = [
      'Lqdt\OrmJson\Test\Fixture\ProfilesFixture',
      'Lqdt\OrmJson\Test\Fixture\RolesFixture',
      'Lqdt\OrmJson\Test\Fixture\UsersFixture',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Profiles = TableRegistry::get('Profiles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'profiles'
        ]);
        $this->Roles = TableRegistry::get('Roles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'roles'
        ]);
        $this->Users = TableRegistry::get('Users', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'users'
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Profiles);
        unset($this->Roles);
        unset($this->Users);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testBelongsToWithLeftJoin()
    {
        $this->Users->belongsTo('Roles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Roles,
          'foreignKey' => 'role_id@attributes'
        ]);

        $query = $this->Users->find()->contain('Roles');
        $users = $query->all();
        $this->assertEquals(3, count($users));
        foreach ($users as $user) {
            $role_id = $user->jsonGet('role_id@attributes');
            if (is_null($role_id)) {
                $this->assertTrue(is_null($user->role));
            } else {
                $this->assertEquals($role_id, $user->role->id);
            }
        }
    }

    public function testBelongsToWithInnerJoin()
    {
        $this->Users->belongsTo('Roles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Roles,
          'foreignKey' => 'role_id@attributes',
          'joinType' => 'INNER'
        ]);

        $query = $this->Users->find()->contain('Roles');
        $users = $query->all();
        $this->assertEquals(2, count($users));
        foreach ($users as $user) {
            $role_id = $user->jsonGet('role_id@attributes');
            $this->assertEquals($role_id, $user->role->id);
        }
    }

    public function testHasOneWithLeftJoin()
    {
        $this->Users->hasOne('Profiles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Profiles,
          'foreignKey' => 'user_id@attributes'
        ]);

        $query = $this->Users->find()->contain('Profiles');
        $users = $query->all();
        $this->assertEquals(3, count($users));
        foreach ($users as $user) {
            if ($user->has('profile')) {
                $this->assertEquals($user->id, $user->profile->jsonGet('user_id@attributes'));
            }
        }
    }

    public function testHasOneWithInnerJoin()
    {
        $this->Users->hasOne('Profiles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Profiles,
          'foreignKey' => 'user_id@attributes',
          'joinType' => 'INNER'
        ]);

        $query = $this->Users->find()->contain('Profiles');
        $users = $query->all();
        $this->assertEquals(2, count($users));
        foreach ($users as $user) {
            $this->assertEquals($user->id, $user->profile->jsonGet('user_id@attributes'));
        }
    }

    public function testHasOneCascadeDeletion()
    {
        $this->Users->hasOne('Profiles', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Profiles,
          'foreignKey' => 'user_id@attributes',
          'joinType' => 'INNER',
          'dependent' => true
        ]);

        $user = $this->Users->find()->contain('Profiles')->first();
        $profile_id = $user->profile->id;
        $this->Users->deleteOrFail($user);
        $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
        $this->Profiles->get($profile_id);
    }

    public function testHasManyWithSelect()
    {
        $this->Roles->hasMany('Users', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Users,
          'foreignKey' => 'role_id@Users.attributes'
        ]);

        $this->Roles->Users->registerForeignKey('role_id@Users.attributes');

        $query = $this->Roles->find()->contain('Users');
        $roles = $query->all();
        foreach ($roles as $role) {
            $this->assertTrue(is_array($role->users));
            foreach ($role->users as $user) {
                $this->assertEquals($role->id, $user->jsonGet('role_id@Users.attributes'));
            }
        }
    }

    public function testHasManyCascadeDeletion()
    {
        $this->Roles->hasMany('Users', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'targetTable' => $this->Users,
          'foreignKey' => 'role_id@Users.attributes',
          'dependent' => true
        ]);

        $this->Roles->Users->registerForeignKey('role_id@Users.attributes');

        $role = $this->Roles->find()->contain('Users')->first();
        $users = $role->users;
        $this->Roles->deleteOrFail($role);
        foreach ($users as $user) {
            $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
            $this->Users->get($user->id);
        }
    }
}
