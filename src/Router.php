<?php

declare(strict_types=1);

namespace Loner\Http\Route;

use Loner\Http\Route\Attribute\Component\{Cache, Domains, Middlewares, Where};
use Loner\Http\Route\Attribute\{Controller, Map, Resource};
use ReflectionClass;
use ReflectionMethod;

/**
 * 路由器
 *
 * @package Loner\Http\Route
 */
class Router
{
    /**
     * 路由开关数组
     *
     * @var Route[][][] [$domain => [$method => [$rule => Route]]]
     */
    private static array $routes = [];

    /**
     * 注册类记录
     *
     * @var true[] [$classname => true]
     */
    private static array $classes = [];

    /**
     * 类路由组件注解信息
     *
     * @var array[]
     */
    private static array $classComponents = [];

    /**
     * 类方法路由组件注解信息
     *
     * @var array[][]
     */
    private static array $methodComponents = [];

    /**
     * 检索路由
     *
     * @param string $path
     * @param string $method
     * @param string $domain
     * @return array|null
     */
    public static function search(string $path, string $method = '*', string $domain = '*'): ?array
    {
        $path = ltrim($path, '/');

        $methods = $method === '*' ? ['*'] : [$method, '*'];
        $domains = $domain === '*' ? ['*'] : [$domain, '*'];

        foreach ($domains as $domain) {
            foreach ($methods as $method) {
                if (isset(self::$routes[$domain][$method])) {
                    foreach (self::$routes[$domain][$method] as $route) {
                        if (null !== $arguments = $route->match($path)) {
                            return compact('route', 'arguments');
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * 获取路由数组
     *
     * @return Route[][][]
     */
    public static function routes(): array
    {
        return self::$routes;
    }

    /**
     * 清空路由数组
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$classes = [];
        self::$classComponents = [];
        self::$methodComponents = [];
    }

    /**
     * 设置路由
     *
     * @param Route $route
     * @param string[]|null $methods
     * @param string[]|null $domains
     */
    public static function set(Route $route, array $methods = null, array $domains = null): void
    {
        if ($methods === null) {
            $methods = ['*'];
        }

        if ($domains === null) {
            $domains = ['*'];
        }

        foreach ($domains as $domain) {
            foreach ($methods as $method) {
                self::$routes[$domain][$method][$route->rule] = $route;
            }
        }
    }

    /**
     * 加载注解路由
     *
     * @param ReflectionClass $class
     */
    public static function load(ReflectionClass $class): void
    {
        if (!isset(self::$classes[$class->name])) {
            self::$classes[$class->name] = true;

            $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

            // 安装资源路由
            self::loadGroup(Resource::class, $class, $methods, self::loadResourceMethod(...));

            // 加载控制器路由
            self::loadGroup(Controller::class, $class, $methods, self::loadControllerMethod(...));

            unset(self::$classComponents[$class->name], self::$methodComponents[$class->name]);
        }
    }

    /**
     * 加载群组路由
     *
     * @param string $attribute
     * @param ReflectionClass $class
     * @param array $methods
     * @param callable $loader
     * @return void
     */
    private static function loadGroup(string $attribute, ReflectionClass $class, array $methods, callable $loader): void
    {
        $attributes = $class->getAttributes($attribute);
        if ($attributes) {
            /** @var Controller $controller */
            $controller = $attributes[0]->newInstance();
            $controller->init($class->name, ...self::getClassComponents($class));
            foreach ($methods as $method) {
                $loader($controller, $method);
            }
            $controller->loading();
        }
    }

    /**
     * 提取类路由组件注解信息
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    private static function getClassComponents(ReflectionClass $reflection): array
    {
        return self::$classComponents[$reflection->name] ??= self::getComponents($reflection);
    }

    /**
     * 提取类方法路由组件注解信息
     *
     * @param ReflectionMethod $reflection
     * @return array
     */
    private static function getMethodComponents(ReflectionMethod $reflection): array
    {
        return self::$methodComponents[$reflection->class][$reflection->name] ??= self::getComponents($reflection);
    }

    /**
     * 获取组件信息
     *
     * @param ReflectionClass|ReflectionMethod $reflection
     * @return array
     */
    private static function getComponents(ReflectionClass|ReflectionMethod $reflection): array
    {
        $domains = self::getComponentValue($reflection, Domains::class, []);
        $middlewares = self::getComponentValue($reflection, Middlewares::class, []);
        $where = self::getComponentValue($reflection, Where::class, []);
        $cacheDuration = self::getComponentValue($reflection, Cache::class, 0);
        return [$domains, $middlewares, $where, $cacheDuration];
    }

    /**
     * 提取组件数据
     *
     * @param ReflectionClass|ReflectionMethod $reflection
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private static function getComponentValue(ReflectionClass|ReflectionMethod $reflection, string $name, mixed $default): mixed
    {
        $attributes = $reflection->getAttributes($name);
        return $attributes ? $attributes[0]->getArguments()[0] : $default;
    }

    /**
     * 加载资源方法路由
     *
     * @param Resource $resource
     * @param ReflectionMethod $method
     * @return void
     */
    private static function loadResourceMethod(Resource $resource, ReflectionMethod $method)
    {
        $resource->register($method->name, ...self::getMethodComponents($method));
    }

    /**
     * 加载控制器方法路由
     *
     * @param Controller $controller
     * @param ReflectionMethod $method
     */
    private static function loadControllerMethod(Controller $controller, ReflectionMethod $method): void
    {
        $mapAttributes = $method->getAttributes(Map::class);
        if ($mapAttributes) {
            foreach ($mapAttributes as $mapAttribute) {
                /** @var Map $map */
                $map = $mapAttribute->newInstance();
                $controller->action($method->name, $map, ...self::getMethodComponents($method));
            }
        } else {
            $controller->action($method->name, new Map($method->name), ...self::getMethodComponents($method));
        }
    }
}
