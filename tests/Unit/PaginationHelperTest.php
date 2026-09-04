<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\ApiResponse;
use App\Helpers\General;
use App\Lib\Valkey;
use Slim\Psr7\Factory\ResponseFactory;

class PaginationHelperTest extends TestCase
{
  private ApiResponse $apiResponse;
  private General $general;

  protected function setUp(): void
  {
    parent::setUp();
    $this->apiResponse = new ApiResponse();

    $mockValkey = $this->createMock(Valkey::class);
    $this->general = new General($mockValkey);
  }

  //* =================== 1. ApiResponse::paginated ===================

  public function test_api_response_paginated_structure()
  {
    $responseFactory = new ResponseFactory();
    $response = $responseFactory->createResponse();

    $items = [
      ['id' => 1, 'name' => 'Item 1'],
      ['id' => 2, 'name' => 'Item 2'],
    ];

    $result = $this->apiResponse->paginated(
      response: $response,
      items: $items,
      total: 25,
      page: 2,
      pageSize: 10,
      message: 'List fetched'
    );

    $this->assertEquals(200, $result->getStatusCode());
    $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

    $body = $this->parseJson($result);
    $this->assertEquals('success', $body['status']);
    $this->assertEquals('List fetched', $body['message']);
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);

    $this->assertCount(2, $body['data']['items']);
    $this->assertEquals(2, $body['data']['pagination']['page']);
    $this->assertEquals(10, $body['data']['pagination']['pageSize']);
    $this->assertEquals(25, $body['data']['pagination']['total']);
    $this->assertEquals(3, $body['data']['pagination']['totalPages']);
  }

  public function test_api_response_paginated_zero_total()
  {
    $responseFactory = new ResponseFactory();
    $response = $responseFactory->createResponse();

    $result = $this->apiResponse->paginated(
      response: $response,
      items: [],
      total: 0,
      page: 1,
      pageSize: 10
    );

    $body = $this->parseJson($result);
    $this->assertEquals(0, $body['data']['pagination']['total']);
    $this->assertEquals(1, $body['data']['pagination']['totalPages']);
    $this->assertEquals(1, $body['data']['pagination']['page']);
    $this->assertEmpty($body['data']['items']);
  }

  public function test_api_response_paginated_edge_case_params()
  {
    $responseFactory = new ResponseFactory();
    $response = $responseFactory->createResponse();

    $result = $this->apiResponse->paginated(
      response: $response,
      items: [],
      total: 15,
      page: -5,
      pageSize: 0
    );

    $body = $this->parseJson($result);
    $this->assertEquals(1, $body['data']['pagination']['page']);
    $this->assertEquals(1, $body['data']['pagination']['pageSize']);
    $this->assertEquals(15, $body['data']['pagination']['totalPages']);
  }

  //* =================== 2. General::normalizePagination ===================

  public function test_normalize_pagination_defaults()
  {
    $normalized = $this->general->normalizePagination(null, null);

    $this->assertEquals(1, $normalized['page']);
    $this->assertEquals(10, $normalized['pageSize']);
    $this->assertEquals(0, $normalized['offset']);
  }

  public function test_normalize_pagination_custom_values()
  {
    $normalized = $this->general->normalizePagination(page: 3, pageSize: 20);

    $this->assertEquals(3, $normalized['page']);
    $this->assertEquals(20, $normalized['pageSize']);
    $this->assertEquals(40, $normalized['offset']);
  }

  public function test_normalize_pagination_caps_max_page_size()
  {
    $normalized = $this->general->normalizePagination(page: 1, pageSize: 500);

    $this->assertEquals(1, $normalized['page']);
    $this->assertEquals(100, $normalized['pageSize']);
  }

  public function test_normalize_pagination_handles_negative_values()
  {
    $normalized = $this->general->normalizePagination(page: -10, pageSize: -5);

    $this->assertEquals(1, $normalized['page']);
    $this->assertEquals(1, $normalized['pageSize']);
    $this->assertEquals(0, $normalized['offset']);
  }

  //* =================== 3. General::paginateArray ===================

  public function test_paginate_array_slices_properly()
  {
    $dataset = range(1, 45); // 45 items: 1..45

    $result = $this->general->paginateArray($dataset, page: 2, pageSize: 10);

    $this->assertEquals(45, $result['pagination']['total']);
    $this->assertEquals(5, $result['pagination']['totalPages']);
    $this->assertEquals(2, $result['pagination']['page']);
    $this->assertEquals(10, $result['pagination']['pageSize']);

    $this->assertCount(10, $result['items']);
    $this->assertEquals(11, $result['items'][0]);
    $this->assertEquals(20, $result['items'][9]);
  }

  public function test_paginate_array_last_page_partial_items()
  {
    $dataset = range(1, 23);

    $result = $this->general->paginateArray($dataset, page: 3, pageSize: 10);

    $this->assertEquals(23, $result['pagination']['total']);
    $this->assertEquals(3, $result['pagination']['totalPages']);
    $this->assertEquals(3, $result['pagination']['page']);
    $this->assertCount(3, $result['items']);
    $this->assertEquals([21, 22, 23], $result['items']);
  }

  public function test_paginate_array_empty_dataset()
  {
    $result = $this->general->paginateArray([], page: 1, pageSize: 10);

    $this->assertEquals(0, $result['pagination']['total']);
    $this->assertEquals(1, $result['pagination']['totalPages']);
    $this->assertEmpty($result['items']);
  }
}
