<?php

declare(strict_types=1);

namespace Loner\Http\Route;

use Closure;

/**
 * 路由
 */
class Route
{
    /**
     * 路由正则
     *
     * @var string
     */
    private string $pattern;

    /**
     * 可选参数前缀分隔符对照表
     *
     * @var array [$name => $valuePrefix]
     */
    private array $prefixes = [];

    /**
     * 定义路由：GET 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function get(string $rule, Closure|string $point): Tap
    {
        return new Tap(['GET'], $rule, $point);
    }

    /**
     * 定义路由：POST 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function post(string $rule, Closure|string $point): Tap
    {
        return new Tap(['POST'], $rule, $point);
    }

    /**
     * 定义路由：PUT 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function put(string $rule, Closure|string $point): Tap
    {
        return new Tap(['PUT'], $rule, $point);
    }

    /**
     * 定义路由：PATCH 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function patch(string $rule, Closure|string $point): Tap
    {
        return new Tap(['PATCH'], $rule, $point);
    }

    /**
     * 定义路由：DELETE 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function delete(string $rule, Closure|string $point): Tap
    {
        return new Tap(['DELETE'], $rule, $point);
    }

    /**
     * 定义路由：不限方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function any(string $rule, Closure|string $point): Tap
    {
        return new Tap(['*'], $rule, $point);
    }

    /**
     * 定义路由：方法组
     *
     * @param string[] $methods
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function many(array $methods, string $rule, Closure|string $point): Tap
    {
        return new Tap(array_map('strtoupper', $methods), $rule, $point);
    }

    /**
     * 构造路由
     *
     * @param string $rule
     * @param Closure|string $point
     * @param array $where
     * @param array $middlewares
     * @param int $cacheDuration
     */
    public function __construct(
        public readonly string $rule,
        public readonly Closure|string $point,
        public readonly array $where = [],
        public readonly array $middlewares = [],
        public readonly int $cacheDuration = 0
    )
    {
        $this->makePattern();
    }

    /**
     * 路由匹配则返回参数列表
     *
     * @param string $path
     * @return array|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->pattern, $path, $matches)) {
            $args = [];
            foreach ($matches as $name => $value) {
                if (is_string($name)) {
                    $args[$name] = $this->value($value, $name);
                }
            }
            return $args;
        }

        return null;
    }

    /**
     * 处理匹配参数的值
     *
     * @param string $value
     * @param string $name
     * @return string
     */
    private function value(string $value, string $name): string
    {
        return isset($this->prefixes[$name]) ? ltrim($value, $this->prefixes[$name]) : $value;
    }

    /**
     * 生成路由正则
     */
    private function makePattern(): void
    {
        $this->pattern = str_replace(['/', '-', '.'], ['\/', '\-', '\.'], $this->rule);

        foreach ($this->where as $name => $preg) {
            $this->replace($name, $preg);
        }

        if (preg_match_all('/{(\w+)\??}/', $this->pattern, $match)) {
            array_map($this->replace(...), $match[1]);
        }
        var_dump($this->pattern);

        $this->pattern = "/^{$this->pattern}$/";
    }

    /**
     * 参数处理
     *
     * @param string $name
     * @param string $preg
     */
    private function replace(string $name, string $preg = '\w+'): void
    {
        if (str_contains($this->pattern, '\/{' . $name . '?}')) {
            $this->pattern = str_replace('\/{' . $name . '?}', '(?<' . $name . '>(\/' . $preg . ')?)', $this->pattern);
            $this->prefixes[$name] = '/';
        } elseif (str_contains($this->pattern, '\-{' . $name . '?}')) {
            $this->pattern = str_replace('\-{' . $name . '?}', '(?<' . $name . '>(\-' . $preg . ')?)', $this->pattern);
            $this->prefixes[$name] = '-';
        } elseif (str_contains($this->pattern, '\.{' . $name . '?}')) {
            $this->pattern = str_replace('\.{' . $name . '?}', '(?<' . $name . '>(\.' . $preg . ')?)', $this->pattern);
            $this->prefixes[$name] = '.';
        } elseif (str_contains($this->pattern, '{' . $name . '?}')) {
            $this->pattern = str_replace('{' . $name . '?}', '(?<' . $name . '>(' . $preg . ')?)', $this->pattern);
        } else {
            $this->pattern = str_replace('{' . $name . '}', '(?<' . $name . '>' . $preg . ')', $this->pattern);
        }
    }
}
