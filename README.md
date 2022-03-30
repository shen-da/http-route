## HTTP 请求注解路由器

该组件主要功能：定义路由、注册路由、检索路由。

实现方式：定义路由开关并注册到路由器，然后通过路由器检索符合条件（请求路径、请求方法、请求域名）的路由。路由器是纯静态的。

路由的定义和注册有两种方式：

1. 常规：创建路由开关对象，进行相关配置，执行注册方法
2. 注解：给控制器类及方法添加相关注解，将其反射载入到路由器；是常规方式的注解化包装

### 运行环境

- PHP >= 8.1

### 安装

```
composer require loner/http-route
```

### 使用说明

#### 一、检索路由

```php
<?php

use Loner\Http\Route\{Route, Router};

/** @var string $path   请求路径 */
/** @var string $method 请求方法；默认 *，只检索不限方法的路由 */
/** @var string $domain 请求域名；默认 *，只检索不限域名的路由 */
if (null !== $search = Router::search($path, $method, $domain)) {
    /** @var Route $route 匹配路由 */
    /** @var array $arguments [路由参数名 => 路由参数值] */
    ['route' => $route, 'arguments' => $arguments] = $search;
}
```

#### 二、常规方式定义注册路由

1. 创建路由开关

    ```php
    <?php
    
    use App\Controller\Users;
    use Loner\Http\Route\Route;
    
    # 常规方法：get、post、put、patch、delete
    # Route::常规方法(路径规则, 闭包/函数名/类名::方法名);
    $tap = Route::get('', fn() => 'hello world');
    $tap = Route::get('pages-{page?}', fn(int $page = 1) => sprintf('这是第 %d 页', $page));
    $tap = Route::post('users', Users::class . '::store');
    $tap = Route::put('users/{id}', Users::class . '::update');
    $tap = Route::patch('users/{id}', Users::class . '::update');
    $tap = Route::delete('users/{id}', Users::class . '::destroy');
    
    # 不限方法
    # Route::any(路径规则, 闭包/函数名/类名::方法名);
    $tap = Route::any('welcome', fn() => 'welcome to here');
    
    # 多个方法
    # Route::many([常规方法1, 常规方法2, ...], 路径规则, 闭包/函数名/类名::方法名);
    $tap = Route::many(['PUT', 'PATCH'], 'users/{id}', Users::class . '::update');
    ```

2. 路由开关配置

    ```php
    <?php
    
    use App\Middleware\{Test1, Test2};
    use Loner\Http\Route\Tap;
    
    /** @var Tap $tap */
   
    // 设置路径规则前缀，当路径规则以 / 开头时，前缀设置不生效
    $tap->prefix('api/v1');
    // $tap->prefix('api')->prefix('v1');
    
    // 设置开放域名，可多个；默认不限制
    $tap->domains('api.test.com', 'admin.test.com');
    
    // 设置中间件
    $tap->middlewares([
        Test1::class => ['a', 2, 'x'],      // 有提供参数：索引（按参数位置）数组或关联（参数名为键）数组
        Test2::class                        // 不提供参数
    ]);
    
    // 设置路由匹配条件：参数名 => 正则
    $tap->where([
        'id' => '[1-9]\d*'
    ]);
    
    // 设置路由缓存时间（秒），默认为 0
    $tap->cache(86400);
    ```

3. 注册路由

    ```php
    <?php
    
    use Loner\Http\Route\Tap;
    
    /** @var Tap $tap */
    $tap->install();
    ```

4. 路径规则说明
    ```php
    <?php
    
    // 以 / 开头时，路径前缀设置不生效
    $rule = '/';    // 相当于域名入口
   
    // 可以携带扩展名
    $rule = '/favicon.ico';  // 相当于 favicon.ico 文件
   
    // 必要参数格式: {参数名}
    // 参数正则通过 $tap->where() 设置
    $rule = 'users/{id}/edit';
   
    // 可选参数格式: {参数名?}
    // 参数正则通过 $tap->where() 设置
    // 注意：/{参数名?}、-{参数名?}、.{参数名?}这三种形式为整体，不提供参数时，匹配路由忽略前置符
    $rule = 'pages.{page?}.html';   // pages.html pages.2.html
    ```

#### 三、注解方式定义注册路由

注解定义有两种方式：Controller + Map、Resource。

Controller 和 Resource 作用在类上，Map 作用在类方法上。

此外，还有组件注解，可作用在类和类方法上（两者冲突时，类方法优先），对路由进行补充。

组件注解相当于路由开关配置，参数也一致，分别为：Domains、Middlewares、Where、Cache。

1. 控制器类添加注解

   ```php
   # Controller + Map 模式
   <?php
   
   declare(strict_types=1);
   
   namespace App\Controller;
   
   use App\Middleware\{Test1, Test2};
   use Loner\Http\Route\Attribute\Component\{Cache, Middlewares};
   use Loner\Http\Route\Attribute\{Controller, Map};
   
   // #[Controller]         同 $tap->prefix('demo');
   // #[Controller(2)]      同 $tap->prefix('controller/demo');
   // #[Controller(3)]      同 $tap->prefix('app/controller/demo');
   // #[Controller('abc')]  同 $tap->prefix('abc');
   #[Controller]
   class Demo
   {
       // #[Map]                    同 Route::many(['GET', 'POST'], 'index', Demo::class . '::index')->prefix('demo');
       // #[Map(methods: '*')]      同 Route::any('index', Demo::class . '::index')->prefix('demo');
       // #[Map('test', 'GET')]     同 Route::get('test', Demo::class . '::index')->prefix('demo');
       #[Map, Middlewares([Test1::class => ['a','b'], Test2::class]), Cache(86400)]
       public function index(): string
       {
           return __METHOD__;
       }
   
       // 同 Route::many(['GET', 'POST'], 'home', Demo::class . '::welcome')
       #[Map('/home')]
       public function welcome(): string
       {
            return 'hello world!';
       }    
   }
   ```

   ```php
   # Resource 模式
   <?php
   
   declare(strict_types=1);
   
   namespace App\Controller;
   
   use Loner\Http\Route\Attribute\Resource;
   
   // #[Resource]         同 $tap->prefix('users');
   // #[Resource(2)]      同 $tap->prefix('controller/users');
   // #[Resource(3)]      同 $tap->prefix('app/controller/users');
   // #[Resource('abc')]  同 $tap->prefix('abc');
   #[Resource]
   class Users
   {
       // 同 Route::get('', Users::class . '::index')->prefix('users');
       public function index()
       {
           return '用户列表页面'
       }
   
       // 同 Route::get('create', Users::class . '::create')->prefix('users');
       public function create()
       {
           return '创建用户页面';
       }
   
       // 同 Route::post('', Users::class . '::store')->prefix('users');
       public function store()
       {
           return '创建用户提交';
       }
   
       // 同 Route::get('{id}', Users::class . '::show')->prefix('users');
       public function show(int $id)
       {
           return sprintf('ID 为 %d 的用户详情页', $id);
       }
   
       // 同 Route::get('{id}/edit', Users::class . '::edit')->prefix('users');
       public function edit(int $id)
       {
           return sprintf('ID 为 %d 的用户修改页', $id);
       }
   
       // 同 Route::many(['PUT', 'PATCH'], '{id}', Users::class . '::update')->prefix('users');
       public function update(int $id)
       {
           return sprintf('ID 为 %d 的用户修改提交', $id);
       }
   
       // 同 Route::delete('{id}', Users::class . '::destroy')->prefix('users');
       public function destroy(int $id)
       {
           return sprintf('ID 为 %d 的用户删除提交', $id);
       }
   }
   ```

2. 将控制器反射载入到路由器

   ```php
   <?php
   
   use App\Controller\{Demo, Users};
   use Loner\Http\Route\Router;

   Router::load(new ReflectionClass(Demo::class));
   Router::load(new ReflectionClass(Users::class));
   ```
