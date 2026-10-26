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
use App\Controllers\Admin\AdminArticleAgentController;
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

//* JOB
use App\Controllers\JobController;

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

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(AuthMiddleware::admin('article:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(AuthMiddleware::admin('article:crud'));
      $group->post('/add_picture', [$controller, 'addPicture'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/status_change', [$controller, 'statusChange'])->add(AuthMiddleware::admin('article:crud'));
      $group->post('/publish_change', [$controller, 'publishChange'])->add(AuthMiddleware::admin('article:crud'));
    })->add(TokenMiddleware::class);

    //! Article Agent
    $groupAdmin->group('/article_agent', function(RouteCollectorProxy $group) {
      $controller = AdminArticleAgentController::class;

      $group->post('/step1_input_topic', [$controller, 'step1_inputTopic'])->add(AuthMiddleware::admin('article_agent:crud'));
      $group->post('/step1_input_selection', [$controller, 'step1_inputSelection'])->add(AuthMiddleware::admin('article_agent:crud'));
      $group->post('/step2_do_research', [$controller, 'step2_doResearch'])->add(AuthMiddleware::admin('article_agent:crud'));
      $group->post('/step3_do_write', [$controller, 'step3_doWrite'])->add(AuthMiddleware::admin('article_agent:crud'))->add(GzipDecoderMiddleware::class);
      $group->get('/detail', [$controller, 'detail'])->add(AuthMiddleware::admin('article_agent:crud'));
    })->add(TokenMiddleware::class);

    //! Article - Category
    $groupAdmin->group('/article_category', function(RouteCollectorProxy $group) {
      $controller = AdminArticleCategoryController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('article:view'));
      $group->get('/detail', [$controller, 'detail'])->add(AuthMiddleware::admin('article:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('article:crud'));
      $group->post('/check_slug', [$controller, 'checkSlug'])->add(AuthMiddleware::admin('article:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Gallery
    $groupAdmin->group('/content_gallery', function(RouteCollectorProxy $group) {
      $controller = AdminContentGalleryController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('content_gallery:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('content_gallery:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('content_gallery:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('content_gallery:crud'));
    })->add(TokenMiddleware::class);

    //! Content - FAQ
    $groupAdmin->group('/content_faq', function(RouteCollectorProxy $group) {
      $controller = AdminContentFAQController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('content_faq:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('content_faq:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('content_faq:crud'));
      $group->post('/sort', [$controller, 'sort'])->add(AuthMiddleware::admin('content_faq:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('content_faq:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Contact
    $groupAdmin->group('/content_contact', function(RouteCollectorProxy $group) {
      $controller = AdminContentContactController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('content_contact:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('content_contact:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('content_contact:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('content_contact:crud'));
    })->add(TokenMiddleware::class);

    //! Content - Team
    $groupAdmin->group('/content_team', function(RouteCollectorProxy $group) {
      $controller = AdminContentTeamController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('content_team:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('content_team:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('content_team:crud'))->add(GzipDecoderMiddleware::class);
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('content_team:crud'));
    })->add(TokenMiddleware::class);

    //! Setting - User
    $groupAdmin->group('/setting_user', function(RouteCollectorProxy $group) {
      $controller = AdminSettingUserController::class;

      $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('setting_user:view'));
      $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->post('/edit', [$controller, 'edit'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->post('/pass', [$controller, 'pass'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->post('/drop', [$controller, 'drop'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->get('/roles', [$controller, 'roles'])->add(AuthMiddleware::admin('setting_user:view'));
      $group->post('/roles_add', [$controller, 'addRoles'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->post('/roles_edit', [$controller, 'editRoles'])->add(AuthMiddleware::admin('setting_user:crud'));
      $group->post('/roles_drop', [$controller, 'dropRoles'])->add(AuthMiddleware::admin('setting_user:crud'));
    })->add(TokenMiddleware::class);

    //! Stats
    $groupAdmin->group('/stats', function(RouteCollectorProxy $group) {
      $controller = AdminStatsController::class;

      $group->get('/log', [$controller, 'listLog'])->add(AuthMiddleware::admin('dashboard:view'));
      $group->post('/log_drop', [$controller, 'dropLog'])->add(AuthMiddleware::admin('dashboard:crud'));
      $group->get('/count_article', [$controller, 'articleCount'])->add(AuthMiddleware::admin('dashboard:view'));
      $group->get('/cron', [$controller, 'listCronArticle'])->add(AuthMiddleware::admin('dashboard:view'));
      $group->get('/article_visits', [$controller, 'listArticleVisits'])->add(AuthMiddleware::admin('dashboard:view'));
      $group->get('/article_visits_by_slug', [$controller, 'listArticleVisitsBySlug'])->add(AuthMiddleware::admin('dashboard:view'));
    })->add(TokenMiddleware::class);

  })
  ->add(GzipEncoder::class)
  ->add(RateLimitMiddleware::admin());

  //* CLIENT
  $app->group('/client', function(RouteCollectorProxy $groupAdmin) {

    //! Article
    $groupAdmin->group('/article', function(RouteCollectorProxy $group) {
      $controller = ClientArticleController::class;

      $group->get('/list', [$controller, 'list']);
      $group->get('/similar', [$controller, 'similar']);
      $group->get('/detail', [$controller, 'detail']);
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
  ->add(GzipEncoder::class)
  ->add(RateLimitMiddleware::client());

  //* JOB
  $app->group('/job', function(RouteCollectorProxy $groupJob) {
    $controller = JobController::class;

    $groupJob->post('/article_publish', [$controller, 'articlePublish']);
    $groupJob->post('/article_sync_stats', [$controller, 'articleSyncStats']);
    $groupJob->post('/article_track_stats', [$controller, 'articleTrackStats']);
    $groupJob->post('/database_backup', [$controller, 'databaseBackup']);
  })
  ->add(TokenMiddleware::class)
  ->add(GzipEncoder::class)
  ->add(RateLimitMiddleware::client());
};