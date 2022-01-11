<?php

namespace Framework\tests\Database\Schema;

use Framework\Database\Schema\Schema;
use Framework\Database\Schema\SchemaBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{

	public function testMakeNewSchemaBuilderInstance()
	{
		$this->expectException(InvalidArgumentException::class);

		Schema::create('testen', function (SchemaBuilder $schema) {
			$this->assertInstanceOf(SchemaBuilder::class, $schema);
		});
	}

	public function testDestructClosureClassInstance()
	{
		Schema::create('testen', function (SchemaBuilder $schema) {
			$schema->bigInt('Id')->autoIncrement();
			$schema->string('string');
			$schema->float('float');
			$schema->bigInt('user_id')->index();
			$schema->timestamp('timestamp')->default('CURRENT_TIMESTAMP');
		});

		$this->assertEquals(true, true);
	}
}
