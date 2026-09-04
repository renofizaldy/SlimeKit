<?php

use Slim\Routing\RouteCollectorProxy;

//* MIDDLEWARE
use App\Middlewares\TokenMiddleware;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\GzipDecoderMiddleware;
use Middlewares\GzipEncoder;

//* ADMIN
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminArticleController;
use App\Controllers\Admin\AdminArticleCategoryController;
use App\Controllers\Admin\AdminContentGalleryController;
use App\Controllers\Admin\AdminContentFAQController;
use App\Controllers\Admin\AdminContentContactController;
use App\Controllers\Admin\AdminContentTeamController;
use App\Controllers\Admin\AdminSettingUserController;
use App\Controllers\Admin\AdminStatsController;

//* CLIENT
use App\Controllers\Client\ClientArticleController;
use App\Controllers\Client\ClientArticleCategoryController;
use App\Controllers\Client\ClientContentContactController;
use App\Controllers\Client\ClientContentFAQController;
use App\Controllers\Client\ClientContentGalleryController;
use App\Controllers\Client\ClientContentTeamController;

return function ($app) {
  //* ADMIN
  $app->group('/admin', function(RouteCollectorProxy $groupAdmin) {

    //! Auth
    $groupAdmin->group('/auth', function(RouteCollectorProxy $group) {
      $controller = AdminAuthController::class;

      $group->get('/verify', [$controller, 'verify'])->add(TokenMiddleware::class);
      $group->post('/login', [$controller, 'login']);
    });

    //! Article
    $groupAdmin->group('/article', function(RouteCollectorProxy $group) {
      $controller = AdminArticleController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/add_picture', [$controller, 'addPicture'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
    })->add(TokenMiddleware::class);

    //! Article - Category
    $groupAdmin->group('/article_category', function(RouteCollectorProxy $group) {
      $controller = AdminArticleCategoryController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(new AuthMiddleware('admin', 'article:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Gallery
    $groupAdmin->group('/content_gallery', function(RouteCollectorProxy $group) {
      $controller = AdminContentGalleryController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_gallery:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_gallery:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_gallery:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_gallery:crud'));
    })->add(TokenMiddleware::class);

    //! Content - FAQ
    $groupAdmin->group('/content_faq', function(RouteCollectorProxy $group) {
      $controller = AdminContentFAQController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_faq:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/sort', [$controller, 'sort'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Contact
    $groupAdmin->group('/content_contact', function(RouteCollectorProxy $group) {
      $controller = AdminContentContactController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_contact:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Team
    $groupAdmin->group('/content_team', function(RouteCollectorProxy $group) {
      $controller = AdminContentTeamController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_team:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_team:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_team:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_team:crud'));
    })->add(TokenMiddleware::class);

    //! Setting - User
    $groupAdmin->group('/setting_user', function(RouteCollectorProxy $group) {
      $controller = AdminSettingUserController::class;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'setting_user:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/pass', [$controller, 'pass'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->get('/roles', [$controller, 'roles'])->add(new AuthMiddleware('admin', 'setting_user:view'));
      $group->post('/roles_add', [$controller, 'addRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/roles_edit', [$controller, 'editRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/roles_drop', [$controller, 'dropRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
    })->add(TokenMiddleware::class);

    //! Stats
    $groupAdmin->group('/stats', function(RouteCollectorProxy $group) {
      $controller = AdminStatsController::class;

      $group->get('/log', [$controller, 'listLog'])->add(new AuthMiddleware('admin', 'dashboard:view'));
      $group->get('/cron', [$controller, 'listCronArticle'])->add(new AuthMiddleware('admin', 'dashboard:view'));
    })->add(TokenMiddleware::class);

  })
  ->add(new GzipEncoder())
  ->add(new RateLimitMiddleware(100, 60));

  //* CLIENT
  $app->group('/client', function(RouteCollectorProxy $groupAdmin) {

    //! Article
    $groupAdmin->group('/article', function(RouteCollectorProxy $group) {
      $controller = ClientArticleController::class;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
      $group->post('/publish', [$controller, 'publish']);
    });

    //! Article Category
    $groupAdmin->group('/article_category', function(RouteCollectorProxy $group) {
      $controller = ClientArticleCategoryController::class;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
    });

    //! Content - Gallery
    $groupAdmin->group('/content_gallery', function(RouteCollectorProxy $group) {
      $controller = ClientContentGalleryController::class;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - Team
    $groupAdmin->group('/content_team', function(RouteCollectorProxy $group) {
      $controller = ClientContentTeamController::class;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - FAQ
    $groupAdmin->group('/content_faq', function(RouteCollectorProxy $group) {
      $controller = ClientContentFAQController::class;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - Contact
    $groupAdmin->group('/content_contact', function(RouteCollectorProxy $group) {
      $controller = ClientContentContactController::class;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
    });

  })
  ->add(new GzipEncoder())
  ->add(new RateLimitMiddleware(60, 60));
};