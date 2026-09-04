<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminSettingUserService;
use Exception;

class AdminSettingUserTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/setting_user/list');
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_list_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['id' => 1, 'fullname' => 'User 1', 'username' => 'user1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/setting_user/list');
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);
  }

  public function test_list_with_pagination_params_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->expects($this->once())
      ->method('list')
      ->with($this->callback(function ($input) {
        return $input['page'] === '2' && $input['pageSize'] === '5';
      }))
      ->willReturn([
        'items' => [],
        'pagination' => [
          'page'       => 2,
          'pageSize'   => 5,
          'total'      => 8,
          'totalPages' => 2,
        ],
      ]);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/setting_user/list')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 2. ADD ===================
  public function test_add_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/add');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_add_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('add')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/add');
    $request = $request->withParsedBody([
      'fullname' => 'Test User',
      'username' => 'testuser',
      'password' => 'password123',
      'status'   => 'active',
      'role'     => 'admin'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_add_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('add')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/add');
    $request = $request->withParsedBody([
      'fullname' => 'Test User',
      'username' => 'testuser',
      'password' => 'password123',
      'status'   => 'active',
      'role'     => 'admin'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. EDIT ===================
  public function test_edit_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/edit');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_edit_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('edit')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/edit');
    $request = $request->withParsedBody([
      'id'       => 1,
      'fullname' => 'Test User Updated',
      'username' => 'testuser',
      'status'   => 'active',
      'role'     => 'admin'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_edit_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('edit')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/edit');
    $request = $request->withParsedBody([
      'id'       => 1,
      'fullname' => 'Test User Updated',
      'username' => 'testuser',
      'status'   => 'active',
      'role'     => 'admin'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 4. PASS ===================
  public function test_pass_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/pass');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_pass_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('pass')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/pass');
    $request = $request->withParsedBody([
      'id'       => 1,
      'password' => 'newpassword'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_pass_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('pass')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/pass');
    $request = $request->withParsedBody([
      'id'       => 1,
      'password' => 'newpassword'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 5. DROP ===================
  public function test_drop_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_drop_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('drop')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_drop_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('drop')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 6. ROLES ===================
  public function test_roles_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('roles')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/setting_user/roles');
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_roles_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('roles')->willReturn(['data' => []]);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/setting_user/roles');
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 7. ROLES ADD ===================
  public function test_rolesAdd_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/roles_add');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_rolesAdd_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('addRoles')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_add');
    $request = $request->withParsedBody([
      'label' => 'Editor',
      'role'  => 'editor'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_rolesAdd_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('addRoles')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_add');
    $request = $request->withParsedBody([
      'label' => 'Editor',
      'role'  => 'editor'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 8. ROLES EDIT ===================
  public function test_rolesEdit_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/roles_edit');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_rolesEdit_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('editRoles')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_edit');
    $request = $request->withParsedBody([
      'id'    => 1,
      'label' => 'Editor',
      'role'  => 'editor'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_rolesEdit_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('editRoles')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_edit');
    $request = $request->withParsedBody([
      'id'    => 1,
      'label' => 'Editor',
      'role'  => 'editor'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 9. ROLES DROP ===================
  public function test_rolesDrop_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/setting_user/roles_drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_rolesDrop_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('dropRoles')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_rolesDrop_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminSettingUserService::class);
    $mockService->method('dropRoles')->willReturn(true);
    $container->set(AdminSettingUserService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/setting_user/roles_drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
