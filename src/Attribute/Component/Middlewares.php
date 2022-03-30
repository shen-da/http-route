<?php

namespace Loner\Http\Route\Attribute\Component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middlewares
{
    public function __construct(public readonly array $middlewares)
    {
    }
}
