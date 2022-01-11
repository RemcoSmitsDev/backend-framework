<?php

namespace Framework\Database\Schema;

class SchemaColumn
{
	private string $nullable = 'NOT NULL';
	private string $default = '';
	private string $unique = '';

	private string $signed = '';
	private string $index = '';
	private string $autoIncrement = '';

	public function __construct(
		private string $column,
		private string $type,
		private int|string|null $length = null,
		private ?string $collatie,
		private array &$columns
	) {
		if ($this->length) {
			$this->length = "({$this->length})";
		} else {
			$this->length = '';
		}

		// format collatie
		$this->collatie($this->collatie);
	}

	public function nullable(): SchemaColumn
	{
		$this->nullable = 'NULL';

		$this->default('NULL');

		return $this;
	}

	public function default(string|int $default): SchemaColumn
	{
		$this->default = 'DEFAULT ' . $default;

		return $this;
	}

	/** FOR INT ONLY */
	public function unsigned(): SchemaColumn
	{
		$this->signed = 'unsigned';

		return $this;
	}

	public function signed(): SchemaColumn
	{
		$this->signed = 'signed';

		return $this;
	}

	public function autoIncrement(): SchemaColumn
	{
		$this->autoIncrement = 'AUTO_INCREMENT';

		return $this->unsigned();
	}

	public function index(?string $index = null, string $unique = '', string $method = 'BTREE'): SchemaColumn
	{
		$this->index = $index ?: $this->column;

		// format index
		$this->index = "{$unique} KEY `{$this->index}` (`{$this->column}`) USING {$method}";

		return $this;
	}

	public function unique(): SchemaColumn
	{
		$this->unique = 'UNIQUE';

		return $this;
	}

	public function collatie(?string $collatie = null): SchemaColumn
	{
		$this->collatie = $collatie ? 'COLLATE ' . $collatie : '';

		return $this;
	}

	public function isUnique(): bool
	{
		return !empty($this->unique);
	}

	public function getColumn(): string
	{
		return $this->column;
	}

	public function getIndex(): string
	{
		return $this->index;
	}

	public function isAutoIncrement(): bool
	{
		return !empty($this->autoIncrement);
	}

	public function toString(): string
	{
		return preg_replace('/\s+/', ' ', "`{$this->column}` {$this->type}{$this->length} {$this->collatie} {$this->signed} {$this->nullable} {$this->default} {$this->autoIncrement}");
	}

	public function __destruct()
	{
		$this->columns[] = $this;
	}
}
