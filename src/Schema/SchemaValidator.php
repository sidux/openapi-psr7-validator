<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Schema;

use cebe\openapi\spec\Schema as CebeSchema;
use cebe\openapi\spec\Type as CebeType;
use League\OpenAPIValidation\Foundation\ArrayHelper;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use League\OpenAPIValidation\Schema\Keywords\AllOf;
use League\OpenAPIValidation\Schema\Keywords\AnyOf;
use League\OpenAPIValidation\Schema\Keywords\Enum;
use League\OpenAPIValidation\Schema\Keywords\Items;
use League\OpenAPIValidation\Schema\Keywords\Maximum;
use League\OpenAPIValidation\Schema\Keywords\MaxItems;
use League\OpenAPIValidation\Schema\Keywords\MaxLength;
use League\OpenAPIValidation\Schema\Keywords\MaxProperties;
use League\OpenAPIValidation\Schema\Keywords\Minimum;
use League\OpenAPIValidation\Schema\Keywords\MinItems;
use League\OpenAPIValidation\Schema\Keywords\MinLength;
use League\OpenAPIValidation\Schema\Keywords\MinProperties;
use League\OpenAPIValidation\Schema\Keywords\MultipleOf;
use League\OpenAPIValidation\Schema\Keywords\Not;
use League\OpenAPIValidation\Schema\Keywords\Nullable;
use League\OpenAPIValidation\Schema\Keywords\OneOf;
use League\OpenAPIValidation\Schema\Keywords\Pattern;
use League\OpenAPIValidation\Schema\Keywords\Properties;
use League\OpenAPIValidation\Schema\Keywords\Required;
use League\OpenAPIValidation\Schema\Keywords\Type;
use League\OpenAPIValidation\Schema\Keywords\UniqueItems;

use function count;
use function is_array;

// This will load a whole schema and data to validate if one matches another
final class SchemaValidator implements Validator
{
    // How to treat the data (affects writeOnly/readOnly keywords)
    public const VALIDATE_AS_REQUEST  = 0;
    public const VALIDATE_AS_RESPONSE = 1;

    /** @var int strategy of validation - Request or Response (affected by writeOnly/readOnly keywords) */
    private $validationStrategy;

    public function __construct(int $validationStrategy = self::VALIDATE_AS_RESPONSE)
    {
        \Respect\Validation\Validator::in([self::VALIDATE_AS_REQUEST, self::VALIDATE_AS_RESPONSE])->assert($validationStrategy);

        $this->validationStrategy = $validationStrategy;
    }

    /** {@inheritdoc} */
    public function validate($data, CebeSchema $schema, ?BreadCrumb $breadCrumb = null): void
    {
        $breadCrumb = $breadCrumb ?? new BreadCrumb();

        $errors = [];
        try {
            (new Nullable($schema))->validate($data, $schema->nullable ?? true);
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        // We don't want to validate any more if the value is a valid Null
        if ($data === null) {
            if (count($errors) > 0) {
                throw new SchemaMismatch(childMismatches: $errors);
            }
            return;
        }


        try {
            // The following properties are taken from the JSON Schema definition but their definitions were adjusted to the OpenAPI Specification.
            if (isset($schema->type)) {
                (new Type($schema))->validate($data, $schema->type, $schema->format);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            // This keywords come directly from JSON Schema Validation, they are the same as in JSON schema
            // https://tools.ietf.org/html/draft-wright-json-schema-validation-00#section-5
            if (isset($schema->multipleOf)) {
                (new MultipleOf($schema))->validate($data, $schema->multipleOf);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->maximum)) {
                $exclusiveMaximum = (bool) ($schema->exclusiveMaximum ?? false);
                (new Maximum($schema))->validate($data, $schema->maximum, $exclusiveMaximum);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->minimum)) {
                $exclusiveMinimum = (bool) ($schema->exclusiveMinimum ?? false);
                (new Minimum($schema))->validate($data, $schema->minimum, $exclusiveMinimum);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }


        try {
            if (isset($schema->maxLength)) {
                (new MaxLength($schema))->validate($data, $schema->maxLength);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->minLength)) {
                (new MinLength($schema))->validate($data, $schema->minLength);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->pattern)) {
                (new Pattern($schema))->validate($data, $schema->pattern);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }


        try {
            if (isset($schema->maxItems)) {
                (new MaxItems($schema))->validate($data, $schema->maxItems);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->minItems)) {
                (new MinItems($schema))->validate($data, $schema->minItems);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->uniqueItems)) {
                (new UniqueItems($schema))->validate($data, $schema->uniqueItems);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->maxProperties)) {
                (new MaxProperties($schema))->validate($data, $schema->maxProperties);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->minProperties)) {
                (new MinProperties($schema))->validate($data, $schema->minProperties);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->required)) {
                (new Required($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->required);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->enum)) {
                (new Enum($schema))->validate($data, $schema->enum);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->items)) {
                (new Items($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->items);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (
                $schema->type === CebeType::OBJECT
                || (isset($schema->properties) && is_array($data) && ArrayHelper::isAssoc($data))
            ) {
                $additionalProperties = $schema->additionalProperties ?? null; // defaults to true
                if ((isset($schema->properties) && count($schema->properties)) || $additionalProperties) {
                    (new Properties($schema, $this->validationStrategy, $breadCrumb))->validate(
                        $data,
                        $schema->properties,
                        $additionalProperties,
                    );
                }
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->allOf) && count($schema->allOf)) {
                (new AllOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->allOf);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->oneOf) && count($schema->oneOf)) {
                (new OneOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->oneOf);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->anyOf) && count($schema->anyOf)) {
                (new AnyOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->anyOf);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }

        try {
            if (isset($schema->not)) {
                (new Not($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->not);
            }
        } catch (SchemaMismatch $e) {
            $errors[] = $e;
        }
        //   ✓  ok, all checks are done

        if (count($errors) > 0) {
            $allMismatches = [];
            foreach ($errors as $error) {
                $allMismatches[] = $this->deepExtractErrors($error);
            }
            throw new SchemaMismatch(
                childMismatches: $this->uniquePathMistmatches(array_merge(...$allMismatches)),
                dataBreadCrumb: $breadCrumb,
                data: $data,
            );
        }
    }


    private function deepExtractErrors(SchemaMismatch $e): array
    {
        $errors = [[$e]];
        foreach ($e->getChildMistmatches() as $child) {
            if ($child instanceof SchemaMismatch) {
                $errors[] = $this->deepExtractErrors($child);
            }
        }

        return array_merge(...$errors);
    }

    /**
     * @param  SchemaMismatch[]  $mismatches
     * @return SchemaMismatch[]
     */
    public function uniquePathMistmatches(array $mismatches): array
    {
        $unique = [];
        foreach ($mismatches as $mismatch) {
            if (!$mismatch->getMessage()) {
                continue;
            }
//            $path = $mismatch->dataBreadCrumb()?->getFullPath();
//            if (!$path) {
//                continue;
//            }
            $unique[] = $mismatch;
        }

        return $unique;

    }
}
