<?php

declare(strict_types=1);

namespace Loner\Http\Route\Attribute;

use Attribute;
use Loner\Http\Route\{Route, Tap};

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Map
{
    /**
     * 请求方法组
     *
     * @var string[]
     */
    private array $methods;

    /**
     * 初始化路由规则、开放方法组
     *
     * @param string|null $rule
     * @param string[] $methods
     */
    public function __construct(private ?string $rule = null,  array|string $methods = ['GET', 'POST'])
    {
        $this->methods = (array)$methods;
    }

    /**
     * 获取路由开关
     *
     * @param string $name
     * @param string $point
     * @param array $domains
     * @param array $middlewares
     * @param array $where
     * @param int $cacheDuration
     * @return Tap
     */
    public function tap(string $name, string $point, array $domains, array $middlewares, array $where, int $cacheDuration): Tap
    {
        $tap = Route::many($this->methods, $this->rule ?? $name, $point);

        if ($domains) {
            $tap->domains(...$domains);
        }

        if ($middlewares) {
            $tap->middlewares($middlewares);
        }

        if ($where) {
            $tap->where($where);
        }

        if ($cacheDuration > 0) {
            $tap->cache($cacheDuration);
        }

        return $tap;
    }
}
