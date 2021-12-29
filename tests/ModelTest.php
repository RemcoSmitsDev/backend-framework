<?php

declare(strict_types=1);

namespace Framework\Tests;

use Framework\Database\Connection\Connection;
use Framework\Model\BaseModel;
use PHPUnit\Framework\TestCase;


app(new Connection());

class User extends BaseModel
{
}

class ModelTest extends TestCase
{
	/* 
	* @test
	*/
	public function testGetCorrectClaasName()
	{
		$user = new User();

		// get table base on model name
		$table = str_replace(
			['controller', 'model'],
			'',
			strtolower(getClassName(get_class($user)))
		);

		// when ending with y replace with ie for the plural
		if (str_ends_with($table, 'y')) {
			$table = substr($table, 0, -1) . 'ie';
		}

		$table = $table . 's';

		$this->assertEquals('users', $table, 'Het uitzoeken van de database table');
	}
}
