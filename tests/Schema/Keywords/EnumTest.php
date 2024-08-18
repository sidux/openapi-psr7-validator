<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema\Keywords;

use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\SchemaValidator;
use League\OpenAPIValidation\Tests\Schema\SchemaValidatorTest;

final class EnumTest extends SchemaValidatorTest
{
    public function testItValidatesEnumGreen(): void
    {
        $spec = <<<SPEC
schema:
  type: string
  enum:
  - a
  - b
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = 'a';

        (new SchemaValidator())->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testItValidatesEnumRed(): void
    {
        $spec = <<<SPEC
schema:
  type: string
  enum: 
  - a
  - b
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = 'c';

        $e = $this->expectMismatch(KeywordMismatch::class, fn () => (new SchemaValidator())->validate($data, $schema));
        $this->assertEquals('enum', $e->keyword());
    }
}
