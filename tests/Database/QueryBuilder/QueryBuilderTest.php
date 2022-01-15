<?php

namespace Framework\tests\Database\QueryBuilder;

use Framework\Database\Connection\Connection;
use Framework\Database\Database;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\QueryBuilder\SubQuery\SubQuery;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
	protected $db;

	protected function setup(): void
	{
		parent::setup();

		$this->db = new Database(new Connection(databaseName: 'db_test'));
	}

	public function testSetTable()
	{
		$this->assertInstanceOf(QueryBuilder::class, $this->db->table('users'));

		$this->assertEquals('users', $this->db->from);
	}

	public function testSetColumns()
	{
		$this->setup();

		$this->assertInstanceOf(QueryBuilder::class, $this->db->select('id', 'username'));

		$this->assertEquals(['`id`', '`username`'], $this->db->columns);
	}

	public function testSetSubSelectColumn()
	{
		$this->setup();

		$this->assertInstanceOf(
			QueryBuilder::class,
			$this->db->select(['post_count' => function ($query) {
				$query->table('posts', 'count(*)')->whereColumn('posts.user_id', '=', 'users.id');
			}])
		);

		$this->assertCount(1, $this->db->columns);

		$this->assertInstanceOf(SubQuery::class, $this->db->columns[0]);

		$this->assertEquals(
			'(SELECT count(*) FROM `posts` WHERE `posts`.`user_id` = `users`.`id`) as post_count',
			(string) $this->db->columns[0]
		);
	}
}
