<?php

namespace Loner\Http\Route\Attribute\Component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Cache
{
    public function __construct(public readonly int $duration)
    {
    }
}
