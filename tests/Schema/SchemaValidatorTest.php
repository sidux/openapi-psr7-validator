<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema;

use cebe\openapi\Reader;
use cebe\openapi\ReferenceContext;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PHPUnit\Framework\TestCase;

abstract class SchemaValidatorTest extends TestCase
{
    protected function loadRawSchema(string $rawSchema): Schema
    {
        $spec = Reader::readFromYaml($rawSchema, Parameter::class);
        $spec->resolveReferences(new ReferenceContext($spec, '/'));

        return $spec->schema;
    }

    /**
     * @template T of SchemaMismatch
     * @param  class-string<T>  $type
     * @return T
     */
    protected function expectMismatch(string $type, callable $callback): ?SchemaMismatch
    {
        $errors = [];
        try {
            $callback();
            $this->fail('Validation did not expected to pass');
        } catch (SchemaMismatch $e) {
            $errors = $e->getChildMistmatches($type);
            $this->assertCount(1, $errors);
        }

        return $errors[0] ?? null;
    }
}
