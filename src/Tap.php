<?php

declare(strict_types=1);

namespace Loner\Http\Route;

use Closure;

/**
 * 路由开关
 */
class Tap
{
    /**
     * 路径前缀列表
     *
     * @var string[] [... 前缀路径]
     */
    private array $prefixes = [];

    /**
     * 路由正则条件
     *
     * @var array [参数名 => 正则]
     */
    private array $where = [];

    /**
     * 开放域名：空为不限制
     *
     * @var string[]
     */
    private array $domains = [];

    /**
     * 中间件
     *
     * @var array
     */
    private array $middlewares = [];

    /**
     * 路由缓存时间
     *
     * @var int
     */
    private int $cacheDuration = 0;

    /**
     * 实例化开关：请求方法组、路径规则、回调结构
     *
     * @param string[] $methods
     * @param string $rule
     * @param Closure|string $point
     */
    public function __construct(public readonly array $methods, public readonly string $rule, public readonly Closure|string $point)
    {
    }

    /**
     * 添加路径前缀
     *
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): self
    {
        if (!str_starts_with($this->rule, '/') && '' !== $prefix = trim($prefix, '/')) {
            $this->prefixes[] = $prefix;
        }
        return $this;
    }

    /**
     * 添加路由规则
     *
     * @param array $where
     * @return $this
     */
    public function where(array $where): self
    {
        foreach ($where as $name => $preg) {
            $this->where[$name] = $preg;
        }
        return $this;
    }

    /**
     * 添加域名配置
     *
     * @param string ...$domains
     * @return $this
     */
    public function domains(string ...$domains): self
    {
        array_push($this->domains, ...$domains);
        return $this;
    }

    /**
     * 添加中间件配置
     *
     * @param array $middlewares
     * @return $this
     */
    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $key => $value) {
            if (is_numeric($key)) {
                $this->middlewares[$value] = [];
            } else {
                $this->middlewares[$key] = (array)$value;
            }
        }
        return $this;
    }

    /**
     * 指定路由缓存时间（秒）
     *
     * @param int $duration
     * @return $this
     */
    public function cache(int $duration): self
    {
        $this->cacheDuration = $duration;
        return $this;
    }

    /**
     * 注册路由开关
     */
    public function install(): void
    {
        $route = new Route($this->completeRule(), $this->point, $this->where, $this->middlewares, $this->cacheDuration);
        Router::set($route, $this->methods ?: null, $this->domains ?: null);
    }

    /**
     * 获取完整路径规则
     *
     * @return string
     */
    private function completeRule(): string
    {
        $rule = ltrim($this->rule, '/');

        return $this->prefixes
            ? $rule === ''
                ? join('/', $this->prefixes)
                : join('/', [...$this->prefixes, $rule])
            : $rule;
    }
}
