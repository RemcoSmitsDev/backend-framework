<?php

namespace tests\Database\QueryBuidler;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;
use PHPUnit\Framework\TestCase;

class User extends BaseModel
{
    protected string $primaryKey = 'user_id';
}
class Category extends BaseModel
{
    protected string $primaryKey = 'category_id';
}

class BaseModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_make_model()
    {
        $userModel = new User();
        $categoryModel = new Category();

        $this->assertInstanceOf(BaseModel::class, $userModel);
        $this->assertInstanceOf(BaseModel::class, $categoryModel);
    }

    public function test_generate_correct_table_name()
    {
        $userModel = new User();
        $categoryModel = new Category();

        $this->assertEquals('users', $userModel->getTable());
        $this->assertEquals('categories', $categoryModel->getTable());
    }

    public function test_overwrite_table_name()
    {
        $userModel = new User();
        $categoryModel = new Category();

        $userModel->setTable('test_users');
        $categoryModel->setTable('test_categories');

        $this->assertEquals('test_users', $userModel->getTable());
        $this->assertEquals('test_categories', $categoryModel->getTable());
    }

    public function test_get_primary_key_from_model()
    {
        $userModel = new User();
        $categoryModel = new Category();

        $this->assertEquals('user_id', $userModel->getPrimaryKey());
        $this->assertEquals('category_id', $categoryModel->getPrimaryKey());
    }

    public function test_set_and_get_value_on_model()
    {
        $userModel = new User();

        $userModel->name = 'test name';

        $this->assertEquals('test name', $userModel->name);
    }

    public function test_call_query_builder()
    {
        $userModel = new User();

        $this->assertInstanceOf(QueryBuilder::class, $userModel->table('users'));
    }
}
