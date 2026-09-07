<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;

use App\Models\Article;
use App\Models\ArticleStats;

class JobService
{
  public function __construct(
    private General $helper,
    private Valkey $valkey,
    private Cronhooks $cronhooks,
    private NextRevalidator $nextRevalidator
  ) {}

  private function checkArticleExist(array $input)
  {
    $check = Article::where([
      'slug' => $input['slug'],
      'site' => $input['site']
    ])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function articlePublish(array $input, array $user)
  {
    //? CHECK ARTICLE
    $check = $this->checkArticleExist($input);
    $cacheKey = 'article';

    DB::beginTransaction();
    try {
      //? UPDATE STATUS
        Article::where('id', $check->id)
          ->update([
            'status' => 'active',
          ]);
      //? UPDATE STATUS

      //? LOG Record
        $this->helper->addLog($user, 'tb_article', $check->id, 'UPDATE', $this->helper->logUpdateChanges($check, [
          'status' => 'active',
        ]));
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:list:site=%s", $input['site']));
        $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:admin"));
      //? DELETE CACHE

      DB::commit();

      $this->nextRevalidator->revalidateArticle($check->site, $check->slug);

      //? CHANGE CRONJOB
        $this->helper->reCron($this->cronhooks);
      //? CHANGE CRONJOB

      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function articleSyncStats()
  {
    if (!$this->valkey->isConnected()) {
      throw new Exception("Redis is not connected.", 500);
    }

    $queueKeys      = $this->valkey->getKeysByPrefix('queue:article:visit:*');
    $totalProcessed = 0;
    $chunkSize      = 1000;
    $processedSites = [];

    foreach ($queueKeys as $queueKey) {
      $batchData = [];
      $keyParts  = explode(':', $queueKey);
      $site      = end($keyParts);
      if (!in_array($site, $processedSites)) {
        $processedSites[] = $site;
      }

      //* Kuras isi antrean menggunakan RPOP
      while ($item = $this->valkey->rpop($queueKey)) {
        $data = json_decode($item, true);
        if (!isset($data['slug'])) continue;

        $ipAddress = $data['ip_address'] ?? null;
        $geoIp     = $this->helper->getGeoIp($ipAddress);

        $batchData[] = [
          'site'         => $site,
          'slug'         => $data['slug'],
          'ip_address'   => $ipAddress,
          'user_agent'   => $data['user_agent'] ?? null,
          'country'      => $geoIp ? $geoIp['country'] : null,
          'country_code' => $geoIp ? $geoIp['countryCode'] : ($data['country'] ?? null),
          'region_code'  => $geoIp ? $geoIp['region'] : null,
          'region'       => $geoIp ? $geoIp['regionName'] : null,
          'city'         => $geoIp ? $geoIp['city'] : null,
          'zip'          => $geoIp ? (int) $geoIp['zip'] : null,
          'latitude'     => $geoIp ? (float) $geoIp['lat'] : null,
          'longitude'    => $geoIp ? (float) $geoIp['lon'] : null,
          'timezone'     => $geoIp ? $geoIp['timezone'] : null,
          'isp'          => $geoIp ? $geoIp['isp'] : null,
          'asn'          => $geoIp ? $geoIp['as'] : null,
          'created_at'   => $data['visited_at'],
          'updated_at'   => $data['visited_at'],
        ];

        //* Eksekusi Batch Insert jika sudah mencapai 1000 data
        if (count($batchData) >= $chunkSize) {
          ArticleStats::insert($batchData);
          $totalProcessed += count($batchData);
          $batchData = []; 
        }
      }

      //* Eksekusi sisa data (jika jumlahnya tidak bulat kelipatan 1000)
      if (!empty($batchData)) {
        ArticleStats::insert($batchData);
        $totalProcessed += count($batchData);
      }
    }

    return [
      'processed_sites' => $processedSites,
      'total_inserted'  => $totalProcessed
    ];
  }

  public function articleTrackStats(array $input, array $user)
  {
    $slug = $input['slug'] ?? null;
    $site = $input['site'] ?? null;
    if (!$slug || !$site) {
      throw new Exception('Invalid input.', 400);
    }

    //! 1. Menangkap IP Asli dari Cloudflare
    $ipAddress = $user['ip_address'];
    if (strpos($ipAddress, ',') !== false) {
      $ipList    = explode(',', $ipAddress);
      $ipAddress = trim($ipList[0]);
    }

    $timestamp = date('Y-m-d H:i:s');
    $visitData = [
      'slug'       => $slug,
      'ip_address' => $ipAddress,
      'user_agent' => $user['user_agent'],
      'country'    => $user['country'],
      'visited_at' => $timestamp
    ];

    if ($this->valkey->isConnected()) {
      $this->valkey->lpush("queue:article:visit:{$site}", json_encode($visitData));
    }

    return true;
  }
}
