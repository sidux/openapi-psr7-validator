<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema\Keywords;

use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\SchemaValidator;
use League\OpenAPIValidation\Tests\Schema\SchemaValidatorTest;

final class MinPropertiesTest extends SchemaValidatorTest
{
    public function testItValidatesMinPropertiesGreen(): void
    {
        $spec = <<<SPEC
schema:
  type: object
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = ['a' => 1, 'b' => 2];

        (new SchemaValidator())->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testItValidatesMinPropertiesRed(): void
    {
        $spec = <<<SPEC
schema:
  type: object
  minProperties: 2
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = ['a' => 1];

        $e = $this->expectMismatch(KeywordMismatch::class, fn () => (new SchemaValidator())->validate($data, $schema));
        $this->assertEquals('minProperties', $e->keyword());
    }
}
