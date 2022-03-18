<?php

declare(strict_types=1);

namespace tests\Model;

use Framework\Database\Connection\Connection;
use Framework\Model\BaseModel;
use Framework\Model\Relation\RelationTypes\BelongsTo;
use Framework\Model\Relation\RelationTypes\HasOne;
use PHPUnit\Framework\TestCase;

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
}

class Category extends BaseModel
{
}
class User extends BaseModel
{
}
class UserAccount extends BaseModel
{
}
