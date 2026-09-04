<?php

namespace App\Lib;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;

class GoogleAnalytics
{
  private $client;
  private $propertyId;

  public function __construct(array $config)
  {
    $this->propertyId = $config['property_id'];
    $this->client = new BetaAnalyticsDataClient([
      'credentials' => $config['credentials']
    ]);
  }

  public function getViewsBySlug(string $slug, string $startDate = '30daysAgo'): int
  {
    try {
      $request = new RunReportRequest([
        'property' => 'properties/' . $this->propertyId,
        'date_ranges' => [
          new DateRange([
            'start_date' => $startDate,
            'end_date'   => 'today',
          ]),
        ],
        // 'dimensions' => [
        //   new Dimension(['name' => 'pagePath']),
        // ],
        'metrics' => [
          new Metric(['name' => 'screenPageViews']),
        ],
        'dimension_filter' => new FilterExpression([
          'filter' => new Filter([
            'field_name'    => 'pagePath',
            'string_filter' => new StringFilter([
              'match_type'     => MatchType::CONTAINS,
              'value'          => $slug,
              'case_sensitive' => false
            ])
          ])
        ]),
        // 'limit' => 1
      ]);
      $response = $this->client->runReport($request);

      $totalViews = 0;
      foreach ($response->getRows() as $row) {
        $totalViews += (int) $row->getMetricValues()[0]->getValue();
      }

      return $totalViews;
    } 
    catch (\Exception $e) {
      throw new \Exception("GA4 API Error: " . $e->getMessage());
    }
  }

  public function getActiveUsers(string $startDate = '30daysAgo'): int
  {
    try {
      $request = new RunReportRequest([
        'property' => 'properties/' . $this->propertyId,
        'date_ranges' => [
          new DateRange([
            'start_date' => $startDate,
            'end_date' => 'today',
          ]),
        ],
        'metrics' => [
          new Metric(['name' => 'activeUsers']),
        ],
      ]);
      $response = $this->client->runReport($request);

      $totalActiveUsers = 0;
      foreach ($response->getRows() as $row) {
        $totalActiveUsers += (int) $row->getMetricValues()[0]->getValue();
      }

      return $totalActiveUsers;
    } 
    catch (\Exception $e) {
      throw new \Exception("GA4 API Error: " . $e->getMessage());
    }
  }

  public function getNewUsers(string $startDate = '30daysAgo'): int
  {
    try {
      $request = new RunReportRequest([
        'property'    => 'properties/' . $this->propertyId,
        'date_ranges' => [
          new DateRange([
            'start_date' => $startDate,
            'end_date'   => 'today',
          ]),
        ],
        'metrics' => [
          new Metric(['name' => 'newUsers']), 
        ]
      ]);
      $response = $this->client->runReport($request);

      $totalNewUsers = 0;
      foreach ($response->getRows() as $row) {
        $totalNewUsers += (int) $row->getMetricValues()[0]->getValue();
      }

      return $totalNewUsers;

    } 
    catch (\Exception $e) {
      throw new \Exception("GA4 API Error: " . $e->getMessage());
    }
  }

  /**
   * Mengambil daftar Path terpopuler beserta Views, Active Users, dan Waktu Kunjungan.
   * * @param string $startDate Rentang waktu (default 30daysAgo)
   * @param int $limit Batas jumlah data yang ditampilkan (default 15)
   */
  public function getTopPaths(string $startDate = '30daysAgo', int $limit = 15): array
  {
    $data = [];

    try {
      $request = new RunReportRequest([
        'property'    => 'properties/' . $this->propertyId,
        'date_ranges' => [
          new DateRange([
            'start_date' => $startDate,
            'end_date'   => 'today',
          ]),
        ],
        'dimensions' => [
          new Dimension(['name' => 'pagePath']),
        ],
        'metrics' => [
          new Metric(['name' => 'screenPageViews']),        // Views
          new Metric(['name' => 'activeUsers']),            // Active Users
          new Metric(['name' => 'userEngagementDuration']), // Total durasi kunjungan (detik)
        ],
        'order_bys' => [
          new OrderBy([
            'metric' => new MetricOrderBy([
              'metric_name' => 'screenPageViews',
            ]),
            'desc' => true,
          ]),
        ],
        'limit' => $limit
      ]);

      $response = $this->client->runReport($request);

      foreach ($response->getRows() as $row) {
        $path               = $row->getDimensionValues()[0]->getValue();
        $views              = (int) $row->getMetricValues()[0]->getValue();
        $activeUsers        = (int) $row->getMetricValues()[1]->getValue();
        $totalEngagementSec = (int) $row->getMetricValues()[2]->getValue();

        // Hitung Rata-rata Engagement Time per Active User
        $avgEngagementSeconds = $activeUsers > 0 ? round($totalEngagementSec / $activeUsers) : 0;
        
        // Format detik menjadi "00m 00s" seperti di dashboard GA4
        $minutes       = floor($avgEngagementSeconds / 60);
        $seconds       = $avgEngagementSeconds % 60;
        $formattedTime = sprintf('%02dm %02ds', $minutes, $seconds);

        $data[] = [
          'path'                   => $path,
          'views'                  => $views,
          'active_users'           => $activeUsers,
          'avg_engagement_time'    => $formattedTime, 
          'avg_engagement_seconds' => $avgEngagementSeconds
        ];
      }

      return $data;
    } 
    catch (\Exception $e) {
      error_log("GA4 Error (Top Paths): " . $e->getMessage());
      return [];
    }
  }

  /**
   * Mengambil Bounce Rate (Persentase orang yang langsung keluar)
   */
  public function getBounceRate(string $startDate = '30daysAgo'): float
  {
    try {
      $request = new RunReportRequest([
        'property'    => 'properties/' . $this->propertyId,
        'date_ranges' => [new DateRange(['start_date' => $startDate, 'end_date' => 'today'])],
        'metrics'     => [new Metric(['name' => 'bounceRate'])],
      ]);

      $response = $this->client->runReport($request);
      $value = 0;
      foreach ($response->getRows() as $row) {
        // Nilai dikali 100 agar jadi persentase (misal 0.65 jadi 65)
        $value = (float) $row->getMetricValues()[0]->getValue() * 100;
      }
      return round($value, 2);
    } 
    catch (\Exception $e) {
      error_log("GA4 Error (Bounce Rate): " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Mengambil Engagement Rate (Persentase orang yang benar-benar berinteraksi)
   */
  public function getEngagementRate(string $startDate = '30daysAgo'): float
  {
    try {
      $request = new RunReportRequest([
        'property'    => 'properties/' . $this->propertyId,
        'date_ranges' => [new DateRange(['start_date' => $startDate, 'end_date' => 'today'])],
        'metrics'     => [new Metric(['name' => 'engagementRate'])],
      ]);

      $response = $this->client->runReport($request);
      $value = 0;
      foreach ($response->getRows() as $row) {
          $value = (float) $row->getMetricValues()[0]->getValue() * 100;
      }
      return round($value, 2);
    } 
    catch (\Exception $e) {
      error_log("GA4 Error (Engagement Rate): " . $e->getMessage());
      return 0;
    }
  }
}