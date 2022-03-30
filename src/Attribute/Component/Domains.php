<?php

namespace Loner\Http\Route\Attribute\Component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Domains
{
    public function __construct(public readonly array $domains)
    {
    }
}
