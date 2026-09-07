<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Middlewares\TokenMiddleware;

class RoutesTest extends TestCase
{
  public function testAllRegisteredRoutesAreResolvableByContainer()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();
    $this->assertNotNull($container, 'DI container must be present');

    $routes = $app->getRouteCollector()->getRoutes();
    $this->assertNotEmpty($routes, 'Routes collector should not be empty');

    $testedControllers = [];

    foreach ($routes as $route) {
      $callable = $route->getCallable();

      if (is_array($callable) && count($callable) === 2) {
        [$class, $method] = $callable;

        $this->assertTrue(
          class_exists($class),
          "Route {$route->getPattern()} references non-existent class: {$class}"
        );

        $this->assertTrue(
          method_exists($class, $method),
          "Route {$route->getPattern()} references non-existent method: {$class}::{$method}"
        );

        // Test DI container resolution for each unique controller class
        if (!isset($testedControllers[$class])) {
          $instance = $container->get($class);
          $this->assertInstanceOf($class, $instance, "Container should resolve instance of {$class}");
          $testedControllers[$class] = true;
        }
      }
    }

    $this->assertGreaterThan(10, count($testedControllers), 'Multiple controllers should be registered and resolved');
  }

  public function testRequiredRouteGroupsExist()
  {
    $app = $this->getAppInstance();
    $routes = $app->getRouteCollector()->getRoutes();

    $patterns = [];
    foreach ($routes as $route) {
      $patterns[] = $route->getPattern();
    }

    // 1. Article Agent routes
    $this->assertContains('/admin/article_agent/step1_input_topic', $patterns);
    $this->assertContains('/admin/article_agent/step1_input_selection', $patterns);
    $this->assertContains('/admin/article_agent/step2_do_research', $patterns);
    $this->assertContains('/admin/article_agent/step3_do_write', $patterns);
    $this->assertContains('/admin/article_agent/detail', $patterns);

    // 2. Job routes
    $this->assertContains('/job/article_publish', $patterns);
    $this->assertContains('/job/article_sync_stats', $patterns);
    $this->assertContains('/job/article_track_stats', $patterns);

    // 3. Content Team routes
    $this->assertContains('/admin/content_team/list', $patterns);
    $this->assertContains('/admin/content_team/add', $patterns);
    $this->assertContains('/admin/content_team/edit', $patterns);
    $this->assertContains('/admin/content_team/drop', $patterns);
    $this->assertContains('/client/content_team/list', $patterns);

    // 4. Modernized Stats routes
    $this->assertContains('/admin/stats/log_drop', $patterns);
    $this->assertContains('/admin/stats/count_article', $patterns);
    $this->assertContains('/admin/stats/article_visits', $patterns);
    $this->assertContains('/admin/stats/article_visits_by_slug', $patterns);

    // 5. Client Article similar route
    $this->assertContains('/client/article/similar', $patterns);
  }

  public function testTokenMiddlewareIsResolvableByContainer()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $middleware = $container->get(TokenMiddleware::class);
    $this->assertInstanceOf(TokenMiddleware::class, $middleware);
  }
}
