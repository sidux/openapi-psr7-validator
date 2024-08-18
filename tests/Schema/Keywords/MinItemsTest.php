<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema\Keywords;

use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\SchemaValidator;
use League\OpenAPIValidation\Tests\Schema\SchemaValidatorTest;

final class MinItemsTest extends SchemaValidatorTest
{
    public function testItValidatesMinItemsGreen(): void
    {
        $spec = <<<SPEC
schema:
  type: array
  minItems: 3
  items:
    type: number
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = [1, 2, 3];

        (new SchemaValidator())->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testItValidatesMinItemsRed(): void
    {
        $spec = <<<SPEC
schema:
  type: array
  minItems: 3
  items:
    type: number
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = [1, 2];

        $e = $this->expectMismatch(KeywordMismatch::class, fn () => (new SchemaValidator())->validate($data, $schema));
        $this->assertEquals('minItems', $e->keyword());
    }
}
