<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Rakit\Validation\Validator;

use App\Lib\Database;
use App\Lib\Tokenize;
use App\Lib\Valkey;
use App\Lib\CloudflareR2;
use App\Lib\Cloudinary;
use App\Lib\GoogleCloudStorage;
use App\Lib\GoogleAnalytics;
use App\Lib\GoogleApps;
use App\Lib\GeminiClient;
use App\Lib\OpenAIClient;
use App\Lib\TavilyClient;
use App\Lib\Mailer;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;
use App\Helpers\General;

return function (ContainerBuilder $builder) {
  $services = require __DIR__ . '/services.php';

  $builder->addDefinitions([
    //* SETTINGS
    'settings' => [
      'displayErrorDetails' => ($_ENV['ENVIRONMENT'] ?? '') !== 'PRODUCTION',
    ],

    //* SERVICE CONFIG (accessible from container if needed)
    'services.config' => $services,

    //* LOGGER
    LoggerInterface::class => function (ContainerInterface $c) {
      $logger = new Logger('app');
      $logChannel = $_ENV['LOG_CHANNEL'] ?? 'file';

      if ($logChannel === 'stderr') {
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
      } else {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
          @mkdir($logDir, 0777, true);
        }
        $logger->pushHandler(new StreamHandler($logDir . '/app.log', Logger::DEBUG));
      }

      return $logger;
    },

    //* VALIDATOR
    Validator::class => \DI\create(Validator::class),

    //* INFRASTRUCTURE
    Database::class => function () use ($services) {
      return new Database($services['database']);
    },

    Valkey::class => function () use ($services) {
      return new Valkey($services['valkey']);
    },

    Tokenize::class => function () use ($services) {
      return new Tokenize($services['tokenize']);
    },

    //* STORAGE PROVIDERS
    CloudflareR2::class => function () use ($services) {
      return new CloudflareR2($services['cloudflare_r2']);
    },

    Cloudinary::class => function () use ($services) {
      return new Cloudinary($services['cloudinary']);
    },

    GoogleCloudStorage::class => function () use ($services) {
      return new GoogleCloudStorage($services['google_cloud_storage']);
    },

    //* GOOGLE SERVICES
    GoogleAnalytics::class => function () use ($services) {
      return new GoogleAnalytics($services['google_analytics']);
    },

    GoogleApps::class => function () use ($services) {
      return new GoogleApps($services['google_apps']);
    },

    //* AI CLIENTS
    GeminiClient::class => function () use ($services) {
      $cfg = $services['gemini'];
      if (empty($cfg['api_key'])) throw new Exception("Missing GEMINI_API_KEY in .env");
      return new GeminiClient($cfg['api_key']);
    },

    TavilyClient::class => function () use ($services) {
      $cfg = $services['tavily'];
      if (empty($cfg['api_key'])) throw new Exception("Missing TAVILY_API_KEY in .env");
      return new TavilyClient($cfg['api_key']);
    },

    OpenAIClient::class => function () use ($services) {
      $cfg = $services['openai'];
      if (empty($cfg['api_key'])) throw new Exception("Missing OPENAI_API_KEY in .env");
      return new OpenAIClient($cfg['api_key']);
    },

    //* COMMUNICATION
    Mailer::class => function () use ($services) {
      return new Mailer($services['mailer']);
    },

    Cronhooks::class => function () use ($services) {
      return new Cronhooks($services['cronhooks']);
    },

    NextRevalidator::class => function () use ($services) {
      return new NextRevalidator($services['next_revalidator']);
    },

    //* HELPERS
    General::class => function (ContainerInterface $c) {
      return new General($c->get(Valkey::class));
    },
  ]);
};
