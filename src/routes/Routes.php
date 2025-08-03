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
      $controller = new AdminAuthController;

      $group->get('/verify', [$controller, 'verify'])->add(new TokenMiddleware());
      $group->post('/login', [$controller, 'login']);
    });

    //! Article
    $groupAdmin->group('/article', function(RouteCollectorProxy $group) {
      $controller = new AdminArticleController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/add_picture', [$controller, 'addPicture'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
    })->add(new TokenMiddleware());

    //! Article - Category
    $groupAdmin->group('/article_category', function(RouteCollectorProxy $group) {
      $controller = new AdminArticleCategoryController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(new AuthMiddleware('admin', 'article:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(new AuthMiddleware('admin', 'article:crud'));
    })->add(new TokenMiddleware());

    //! Content - Gallery
    $groupAdmin->group('/content_gallery', function(RouteCollectorProxy $group) {
      $controller = new AdminContentGalleryController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_gallery:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_gallery:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_gallery:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_gallery:crud'));
    })->add(new TokenMiddleware());

    //! Content - FAQ
    $groupAdmin->group('/content_faq', function(RouteCollectorProxy $group) {
      $controller = new AdminContentFAQController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_faq:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/sort', [$controller, 'sort'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_faq:crud'));
    })->add(new TokenMiddleware());

    //! Content - Contact
    $groupAdmin->group('/content_contact', function(RouteCollectorProxy $group) {
      $controller = new AdminContentContactController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_contact:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_contact:crud'));
    })->add(new TokenMiddleware());

    //! Content - Team
    $groupAdmin->group('/content_team', function(RouteCollectorProxy $group) {
      $controller = new AdminContentTeamController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'content_team:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'content_team:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'content_team:crud'))->add(new GzipDecoderMiddleware());
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'content_team:crud'));
    })->add(new TokenMiddleware());

    //! Setting - User
    $groupAdmin->group('/setting_user', function(RouteCollectorProxy $group) {
      $controller = new AdminSettingUserController;

      $group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'setting_user:view'));
      $group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/pass', [$controller, 'pass'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->get('/roles', [$controller, 'roles'])->add(new AuthMiddleware('admin', 'setting_user:view'));
      $group->post('/roles_add', [$controller, 'addRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/roles_edit', [$controller, 'editRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
      $group->post('/roles_drop', [$controller, 'dropRoles'])->add(new AuthMiddleware('admin', 'setting_user:crud'));
    })->add(new TokenMiddleware());

    //! Stats
    $groupAdmin->group('/stats', function(RouteCollectorProxy $group) {
      $controller = new AdminStatsController;

      $group->get('/log', [$controller, 'listLog'])->add(new AuthMiddleware('admin', 'dashboard:view'));
      $group->get('/upcoming_event', [$controller, 'upcomingEvent'])->add(new AuthMiddleware('admin', 'dashboard:view'));
      $group->get('/upcoming_participant', [$controller, 'upcomingParticipant'])->add(new AuthMiddleware('admin', 'dashboard:view'));
      $group->get('/pending_participant', [$controller, 'pendingParticipant'])->add(new AuthMiddleware('admin', 'dashboard:view'));
      $group->get('/confirm_payment', [$controller, 'confirmPayment'])->add(new AuthMiddleware('admin', 'dashboard:view'));
    })->add(new TokenMiddleware());

  })
  ->add(new GzipEncoder())
  ->add(new RateLimitMiddleware(100, 60));

  //* CLIENT
  $app->group('/client', function(RouteCollectorProxy $groupAdmin) {

    //! Article
    $groupAdmin->group('/article', function(RouteCollectorProxy $group) {
      $controller = new ClientArticleController;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
    });

    //! Article Category
    $groupAdmin->group('/article_category', function(RouteCollectorProxy $group) {
      $controller = new ClientArticleCategoryController;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
    });

    //! Content - Gallery
    $groupAdmin->group('/content_gallery', function(RouteCollectorProxy $group) {
      $controller = new ClientContentGalleryController;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - Team
    $groupAdmin->group('/content_team', function(RouteCollectorProxy $group) {
      $controller = new ClientContentTeamController;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - FAQ
    $groupAdmin->group('/content_faq', function(RouteCollectorProxy $group) {
      $controller = new ClientContentFAQController;

      $group->get('/list', [$controller, 'list']);
    });

    //! Content - Contact
    $groupAdmin->group('/content_contact', function(RouteCollectorProxy $group) {
      $controller = new ClientContentContactController;

      $group->get('/list', [$controller, 'list']);
      $group->get('/detail', [$controller, 'detail']);
    });

  })
  ->add(new GzipEncoder())
  ->add(new RateLimitMiddleware(60, 60));
};