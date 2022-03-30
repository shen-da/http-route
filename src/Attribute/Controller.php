<?php

namespace Loner\Http\Route\Attribute;

use Attribute;
use Loner\Http\Route\Tap;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    /**
     * 路由开关数组
     *
     * @var Tap[][] [$method => [...Tap]]
     */
    protected array $taps = [];

    /**
     * 类名
     *
     * @var string
     */
    protected string $classname;

    /**
     * 公共前缀
     *
     * @var string
     */
    protected string $prefix;

    /**
     * 公共开放域名组
     *
     * @var string[]
     */
    protected array $domains = [];

    /**
     * 公共中间件
     *
     * @var array
     */
    protected array $middlewares = [];

    /**
     * 公共条件
     *
     * @var array
     */
    protected array $where = [];

    /**
     * 缓存时限
     *
     * @var int
     */
    protected int $cacheDuration = 0;

    /**
     * 初始化公共路由前缀
     *
     * @param string|int $prefixOrLevel
     */
    public function __construct(private string|int $prefixOrLevel = 1)
    {
    }

    /**
     * 初始化公共路由组件信息
     *
     * @param string $classname
     * @param array $domains
     * @param array $middlewares
     * @param array $where
     * @param int $cacheDuration
     */
    public function init(string $classname, array $domains, array $middlewares, array $where, int $cacheDuration)
    {
        $this->prefix = is_int($this->prefixOrLevel)
            ? join('/', array_map('lcfirst', array_slice(explode('\\', $classname), -$this->prefixOrLevel)))
            : $this->prefixOrLevel;
        $this->classname = $classname;
        $this->domains = $domains;
        $this->middlewares = $middlewares;
        $this->where = $where;
        $this->cacheDuration = $cacheDuration;
    }

    /**
     * 注册路由资源
     *
     * @param string $name
     * @param Map $map
     * @param string[] $domains
     * @param array $middlewares
     * @param array $where
     * @param int $cacheDuration
     * @return Tap
     */
    public function action(string $name, Map $map, array $domains, array $middlewares, array $where, int $cacheDuration): Tap
    {
        $point = $this->point($name);

        if (!$domains && $domains !== $this->domains) {
            $domains = $this->domains;
        }

        $middlewares += $this->middlewares;

        $where += $this->where;

        if ($cacheDuration === 0 && $cacheDuration !== $this->cacheDuration) {
            $cacheDuration = $this->cacheDuration;
        }

        $this->taps[$name][] = $tap = $map->tap($name, $point, $domains, $middlewares, $where, $cacheDuration)->prefix($this->prefix);

        return $tap;
    }

    /**
     * 加载路由资源
     */
    public function loading(): void
    {
        if ($this->taps) {
            foreach ($this->taps as $taps) {
                foreach ($taps as $tap) {
                    $tap->install();
                }
            }
            $this->taps = [];
        }
    }

    /**
     * 获取指向调用名
     *
     * @param string $method
     * @return string
     */
    private function point(string $method): string
    {
        return $this->classname . '::' . $method;
    }
}
