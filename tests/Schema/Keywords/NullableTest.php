<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema\Keywords;

use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\SchemaValidator;
use League\OpenAPIValidation\Tests\Schema\SchemaValidatorTest;

final class NullableTest extends SchemaValidatorTest
{
    public function testItValidatesNullableGreen(): void
    {
        $spec = <<<SPEC
schema:
    type: string
    nullable: true
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = null;

        (new SchemaValidator())->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testItValidatesNullableRed(): void
    {
        $spec = <<<SPEC
schema:
    type: string
SPEC;

        $schema = $this->loadRawSchema($spec);
        $data   = null;

        $e = $this->expectMismatch(KeywordMismatch::class, fn () => (new SchemaValidator())->validate($data, $schema));
        $this->assertEquals('nullable', $e->keyword());
    }
}
