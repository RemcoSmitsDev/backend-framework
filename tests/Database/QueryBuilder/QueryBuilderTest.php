<?php

namespace Framework\tests\Database\QueryBuilder;

use Framework\Database\Connection\Connection;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\QueryBuilder\SubQuery\SubQuery;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection(databaseName: 'db_test');
    }

    public function test_set_table()
    {
        $this->assertInstanceOf(QueryBuilder::class, $builder = QueryBuilder::new($this->connection)->table('users'));

        $this->assertEquals('users', $builder->from);
    }

    public function test_set_columns()
    {
        $this->assertInstanceOf(QueryBuilder::class, $builder = QueryBuilder::new($this->connection)->table('users', ['id', 'username']));

        $this->assertEquals(['`id`', '`username`'], $builder->columns);

        $builder = QueryBuilder::new($this->connection)->table('users', 'email')->select('id', 'username');

        $this->assertEquals(['`email`', '`id`', '`username`'], $builder->columns);
    }

    public function test_set_sub_select_column()
    {
        $this->assertInstanceOf(
            QueryBuilder::class,
            $builder = QueryBuilder::new($this->connection)->select(['post_count' => function ($query) {
                $query->table('posts', 'count(*)')->whereColumn('posts.user_id', '=', 'users.id');
            }])
        );

        $this->assertCount(1, $builder->columns);

        $this->assertInstanceOf(SubQuery::class, $builder->columns[0]);

        $this->assertEquals(
            '(SELECT count(*) FROM `posts` WHERE `posts`.`user_id` = `users`.`id`) as post_count',
            (string) $builder->columns[0]
        );
    }
}
