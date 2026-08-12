<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesSimpleGetRoute(): void
    {
        $router = new Router();
        $router->get('/test', function (Request $req): Response {
            return Response::json(['message' => 'success']);
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']);
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('success', $response->getContent());
    }

    public function testExtractsRouteParameters(): void
    {
        $router = new Router();
        $router->get('/classes/{class_id}/subjects/{subject_id}', function (Request $req, string $classId, string $subjectId): Response {
            return Response::json([
                'class_id' => $classId,
                'subject_id' => $subjectId,
            ]);
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/classes/15/subjects/42']);
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('15', $data['class_id']);
        $this->assertSame('42', $data['subject_id']);
    }

    public function testReturns404ForUnmatchedRoute(): void
    {
        $router = new Router();
        $router->get('/existing', function () {
            return Response::html('ok');
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/missing', 'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $response = $router->dispatch($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns405ForMethodMismatch(): void
    {
        $router = new Router();
        $router->post('/submit', function () {
            return Response::html('ok');
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/submit']);
        $response = $router->dispatch($request);

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testExecutesMiddlewarePipeline(): void
    {
        $router = new Router();

        $middleware = function (Request $req, callable $next): Response {
            $req->setAttribute('middleware_ran', true);
            return $next($req);
        };

        $router->get('/protected', function (Request $req): Response {
            return Response::json(['middleware' => $req->getAttribute('middleware_ran')]);
        }, [$middleware]);

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/protected']);
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['middleware']);
    }
}
