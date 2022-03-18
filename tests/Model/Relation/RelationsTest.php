<?php

namespace tests\Model\Relation;

use Exception;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;
use Framework\Model\Relation\RelationTypes\BelongsTo;
use Framework\Model\Relation\RelationTypes\BelongsToMany;
use Framework\Model\Relation\RelationTypes\HasMany;
use Framework\Model\Relation\RelationTypes\HasOne;
use PDO;
use PHPUnit\Framework\TestCase;

class RelationsTest extends TestCase
{
    public function test_get_relations()
    {
        $model = new User();

        $relations = $model->getRelations();

        $this->assertCount(2, $relations);
        $this->assertArrayHasKey('account', $relations);
        $this->assertArrayHasKey('categories', $relations);
    }

    public function test_get_single_relation()
    {
        $model = new User;

        $this->expectException(Exception::class);

        $model->getRelation('posts');

        $this->assertInstanceOf(HasOne::class, $model->getRelation('account'));
        $this->assertInstanceOf(BaseRelation::class, $model->getRelation('account'));
    }

    public function test_has_one_relation()
    {
        $model = new User;

        $this->assertInstanceOf(BaseRelation::class, $model->getRelation('account'));
        $this->assertInstanceOf(HasOne::class, $model->getRelation('account'));
        $this->assertInstanceOf(User::class, $model->getRelation('account')->getFromModel());
        $this->assertEquals(UserAccount::class, $model->getRelation('account')->relation);
        $this->assertEquals('user_id', $model->getRelation('account')->foreignKey);
        $this->assertEquals('id', $model->getRelation('account')->primaryKey);
    }

    public function test_belongs_to_relation()
    {
        $model = new UserAccount;

        $this->assertInstanceOf(BaseRelation::class, $model->getRelation('user'));
        $this->assertInstanceOf(BelongsTo::class, $model->getRelation('user'));
        $this->assertInstanceOf(UserAccount::class, $model->getRelation('user')->getFromModel());
        $this->assertEquals(User::class, $model->getRelation('user')->relation);
        $this->assertEquals('user_id', $model->getRelation('user')->foreignKey);
        $this->assertEquals('id', $model->getRelation('user')->primaryKey);
    }

    public function test_belongs_to_many_relation()
    {
        $model = new User;

        // WIP
        $this->assertInstanceOf(BaseRelation::class, $model->getRelation('categories'));
        $this->assertInstanceOf(HasMany::class, $model->getRelation('categories'));
        $this->assertInstanceOf(User::class, $model->getRelation('categories')->getFromModel());
        $this->assertEquals(Category::class, $model->getRelation('categories')->relation);
        $this->assertEquals('user_id', $model->getRelation('categories')->foreignKey);
        $this->assertEquals('id', $model->getRelation('categories')->primaryKey);
    }
}

class Category extends BaseModel
{
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'test-table');
    }
}

class User extends BaseModel
{
    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}

class UserAccount extends BaseModel
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
