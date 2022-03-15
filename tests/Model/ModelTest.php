<?php

declare(strict_types=1);

namespace tests\Model;

use Framework\Database\Connection\Connection;
use Framework\Model\BaseModel;
use Framework\Model\Relation\RelationTypes\BelongsTo;
use Framework\Model\Relation\RelationTypes\HasOne;
use PHPUnit\Framework\TestCase;

class Category extends BaseModel
{
}
class User extends BaseModel
{
    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }
}
class UserAccount extends BaseModel
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class ModelTest extends TestCase
{
    public function test_get_table_name_from_model()
    {
        $model = new User;

        $this->assertEquals('users', $model->getTable());

        $model = new Category;

        $this->assertEquals('categories', $model->getTable());

        $model = new UserAccount;

        $this->assertEquals('user_accounts', $model->getTable());
    }

    public function test_get_relations()
    {
        $model = new User();

        $relations = $model->getRelations();

        $this->assertCount(1, $relations);
        $this->assertArrayHasKey('account', $relations);

        // test HasOne relationship
        $this->assertInstanceOf(HasOne::class, $relations['account']);
        $this->assertInstanceOf(User::class, $relations['account']->getFromModel());
        $this->assertEquals('user_id', $relations['account']->foreignKey);
        $this->assertEquals('id', $relations['account']->primaryKey);
    }
}
