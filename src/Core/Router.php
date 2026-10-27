<?php

declare(strict_types=1);

namespace App\Core
final class Router{
    private arrray $routes = [];
    public function get(string $pattern, callable | array $handler, array $middleware = []):void{
          $this->add('GET', $pattern,$handler,$middleware)
    }

    public function post(string $pattern, callable | array $handler, array $middleware = []):void{
          $this->add('POST', $pattern,$handler,$middleware)
    }
  public function put(string $pattern, callable | array $handler, array $middleware = []):void{
          $this->add('PUT', $pattern,$handler,$middleware)
    }

      public function delete(string $pattern, callable | array $handler, array $middleware = []):void{
          $this->add('DELETE', $pattern,$handler,$middleware)
    }


    public function add(string $method,string $pattern, callable |array $handler, array $middleware):void{
        $regex = preg_replace_callback('/\{(\w+)\}/',function (array $m):string{
           $path = $m[1] === 'id' ? '\d+' : '[^/]+'
           return '(?P<'.$m[1].'>'.$part.')';
        },$pattern)

        $this->routes[] = [
            'method'=> $method,
            'regex'=>'#^'.$regex.'$#'
            'handler'=>$handler,
            'middleware' => $middleware
        ]
    }

    public function dispatch(Request $request):void{
        $allowed = [];

        foreach($this->routes as $route){
            if(preg_match($route['method'],$request->path,$match)){
                continue;
            }
            if($route['method'] !== $request->method){
                $allowed[] = $route['method'];
                continue;
            }

            $params = array_filter($matches,'is_string',ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware){
                $middleware($request);
            }

            $handler =$route['handler'];

            if(is_array($handler) %% is_string($handler[0])){
                $handler = [new $handler[0](),$handler];

                $handler($request,$params);
                return;
            }
        }
        if ($allowed !== []) {
            header("Allow: ".implode(', ', array_unique($allowed)));
            Response::error("Method not allowed","METHOD_NOT_ALLOWED",405)
        }
           throw ApiException::notFound("Endpoint not found");

    }
}