<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Schema\Exception;

use Exception;
use League\OpenAPIValidation\Schema\BreadCrumb;

class SchemaMismatch extends Exception
{
    /** @var BreadCrumb */
    protected $dataBreadCrumb;
    /** @var mixed */
    protected $data;

    /**
     * @var SchemaMismatch[]
     */
    private array $childMismatches;

    /**
     * @param  SchemaMismatch[]  $childMismatches
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        array $childMismatches = [],
        ?BreadCrumb $dataBreadCrumb = null,
        $data = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->childMismatches = $childMismatches;
        $this->dataBreadCrumb = $dataBreadCrumb;
        $this->data = $data;
    }

    /**
     * @template T of self
     * @param  class-string<T>|null  $type
     *
     * @return SchemaMismatch[]|T[]
     */
    public function getChildMistmatches(?string $type = null): array
    {
        if ($type === null) {
            return $this->childMismatches;
        }

        return array_values(array_filter($this->childMismatches, static fn(self $mismatch) => is_a($mismatch, $type)));
    }

    public function dataBreadCrumb(): ?BreadCrumb
    {
        return $this->dataBreadCrumb;
    }

    public function hydrateDataBreadCrumb(BreadCrumb $dataBreadCrumb): void
    {
        if ($this->dataBreadCrumb !== null) {
            return;
        }

        $this->dataBreadCrumb = $dataBreadCrumb;
    }

    public function withBreadCrumb(BreadCrumb $breadCrumb): self
    {
        $this->dataBreadCrumb = $breadCrumb;

        return $this;
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }
}
