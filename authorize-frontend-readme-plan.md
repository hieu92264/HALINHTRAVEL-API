# Plan: Frontend Authorization API README

**Generated**: 2026-09-30  
**Estimated Complexity**: Low

## Overview

Tạo tài liệu Markdown tiếng Việt tại `docs/marrkdown/authorize.md` để frontend xây các trang quản lý Users, Roles, Permissions và phân quyền. Tài liệu chỉ phản ánh API Auth đang tồn tại, không mô tả Login/Refresh/Logout/`/me`, và không thiết kế giao diện.

README sẽ mô tả endpoint, quyền cần có, request body, thành công response wrapper, cấu trúc `metadata`, phân trang và workflow phân quyền. Nó sẽ giúp frontend phân biệt rõ:

- `roles`: role đang gán trực tiếp cho user;
- `direct_permissions`: quyền ngoại lệ gán trực tiếp cho user;
- `permissions`: tập quyền hiệu lực, gồm quyền trực tiếp và quyền kế thừa từ role.

Đặc biệt, tài liệu sẽ phản ánh chính xác behaviour hiện có: endpoint quyền trực tiếp của user là `PUT /api/auth/users/{user}/permissions` và **sync/thay thế toàn bộ** danh sách direct permissions. Tài liệu không được hứa hẹn thao tác add/remove từng quyền, vì route/API đó chưa tồn tại.

## Prerequisites

- Source of truth là routes, Form Requests, controllers, services và feature tests trong `app/Modules/Auth`.
- Đường dẫn tài liệu được giữ nguyên theo yêu cầu: `docs/marrkdown/authorize.md` (bao gồm cách viết `marrkdown`). Thư mục hiện chưa tồn tại và sẽ được tạo khi thực hiện.
- Các endpoint quản trị dùng JWT Bearer token với guard `api` và Spatie permission middleware.
- Không thay đổi routes, controller, validation, database/migration hoặc package dependencies trong scope tài liệu này.

## API Contract to Capture

| Resource / action | Endpoint hiện có | Permission middleware |
| --- | --- | --- |
| List/show user | `GET /api/auth/users`, `GET /api/auth/users/{user}` | `users.view` |
| Create/update/deactivate user | `POST /api/auth/users`, `PUT /api/auth/users/{user}`, `DELETE /api/auth/users/{user}` | `users.manage` |
| Replace user roles | `PUT /api/auth/users/{user}/roles` | `users.manage` |
| Replace direct user permissions | `PUT /api/auth/users/{user}/permissions` | `users.manage` |
| List/show/create/update/delete role | `/api/auth/roles[/{role}]` | `roles.manage` |
| Replace role permissions | `PUT /api/auth/roles/{role}/permissions` | `roles.manage` |
| List/show permission | `GET /api/auth/permissions[/{permission}]` | `permissions.view` |
| Create/update/delete permission | `POST|PUT|DELETE /api/auth/permissions[/{permission}]` | `permissions.manage` |

## Sprint 1: Build an evidence-backed API inventory

**Goal**: Chốt mọi route, authorization rule, validation input và response field trước khi viết tài liệu.

**Demo/Validation**:

- Có một inventory nội bộ khớp với `php artisan route:list --path=auth --json`.
- Không có endpoint Auth/login được đưa vào phạm vi README.

### Task 1.1: Extract management routes and authorization prerequisites

- **Location**: `app/Modules/Auth/Routes/index.php`, `bootstrap/app.php`, `app/Shared/Enums/PermissionEnum.php`
- **Description**: Lập danh sách 17 routes quản trị Users/Roles/Permissions, HTTP method, URI, route parameter, `auth:api` và permission middleware. Chỉ giữ permissions liên quan (`users.view`, `users.manage`, `roles.manage`, `permissions.view`, `permissions.manage`).
- **Dependencies**: None.
- **Acceptance Criteria**:
  - README dùng đúng prefix `/api/auth`, không suy đoán base host.
  - Mỗi action nêu header `Authorization: Bearer <access_token>` và quyền caller cần có.
  - Không đưa Login, Refresh, Logout, Me vào mục endpoint.
- **Validation**: So sánh inventory với `php artisan route:list --path=auth --json`.

### Task 1.2: Extract request contracts and state-changing semantics

- **Location**: `app/Modules/Auth/Requests/StoreUserRequest.php`, `UpdateUserRequest.php`, `StoreRoleRequest.php`, `UpdateRoleRequest.php`, `StorePermissionRequest.php`, `UpdatePermissionRequest.php`, `SyncRolesRequest.php`, `SyncPermissionsRequest.php`; `app/Modules/Auth/Services/AccessManagementService.php`
- **Description**: Ghi field bắt buộc/tùy chọn, kiểu JSON và rule quan trọng cho create/update/sync. Nêu rõ create User chấp nhận optional `role_ids`; update User chỉ hỗ trợ `email`, `password`, `is_active`; `user_name` không cập nhật được.
- **Dependencies**: Task 1.1.
- **Acceptance Criteria**:
  - `role_ids` và `permission_ids` được mô tả là `array<number>` không trùng lặp.
  - Hai API `PUT .../roles` và `PUT .../permissions` ghi rõ “replace all”, kể cả mảng rỗng hợp lệ.
  - Role/permission name regex được viết thành quy tắc dễ đọc: role có dot optional; permission bắt buộc có ít nhất một dot.
  - Delete User được mô tả đúng là deactivate (`is_active=false`), không hard-delete.
- **Validation**: Đối chiếu Form Requests và các test `tests/Feature/Auth/AccessManagementTest.php`.

### Task 1.3: Extract response schemas and status codes

- **Location**: `vendor/hieu-dev-92264/laravel-modules/src/Traits/ApiResponse.php`, `app/Modules/Auth/Services/AccessManagementService.php`, các controllers Auth quản trị
- **Description**: Lập schema chung successful wrapper (`message`, `status_code`, `metadata`, `path`, `timestamp`), paginator metadata và resource shape `User`, `Role`, `Permission`. Viết một ví dụ JSON ngắn cho mỗi shape thay vì lặp toàn bộ response cho từng endpoint.
- **Dependencies**: Tasks 1.1-1.2.
- **Acceptance Criteria**:
  - User shape có `roles`, `direct_permissions`, `permissions`; giải thích nguồn và ý nghĩa từng trường.
  - Role shape có `permissions`, `is_system`; Permission shape có `is_system`.
  - List response mô tả paginator Laravel trong `metadata` (`data`, `current_page`, `per_page`, `total`, ...).
  - Phân biệt status thực tế: create trả HTTP/status_code `201`; các successful update/delete/sync/list/show trả `200`.
- **Validation**: Đối chiếu `ApiResponse` trait, controller status codes và feature tests.

## Sprint 2: Write the frontend-facing README

**Goal**: Có một tài liệu duy nhất để frontend gọi đúng API và hiểu quyền hiệu lực mà không phải đọc PHP source.

**Demo/Validation**:

- Một frontend developer có thể thực hiện luồng CRUD Users/Roles/Permissions và gán role/quyền chỉ dựa vào `authorize.md`.
- Tài liệu không hứa hẹn endpoint/functionality chưa có.

### Task 2.1: Create the README structure and common conventions

- **Location**: `docs/marrkdown/authorize.md` (new)
- **Description**: Tạo folder `docs/marrkdown` và file. Viết phần phạm vi, auth header, base-path `/api/auth`, JSON header, common success wrapper, pagination query (`page`, `per_page`, range 1..100; default 15) và quy ước ID.
- **Dependencies**: Sprint 1.
- **Acceptance Criteria**:
  - Tiếng Việt rõ ràng, code examples copyable, tên fields giữ nguyên snake_case API.
  - Chỉ mô tả success response theo yêu cầu; không thêm section 401/403/422/error payload.
  - Không đưa base host cố định vì môi trường frontend có thể chạy Laragon hoặc Docker.
- **Validation**: Markdown render check; request sample khớp route list.

### Task 2.2: Document Users CRUD and role assignment

- **Location**: `docs/marrkdown/authorize.md`
- **Description**: Viết sections list/detail/create/update/deactivate User và `PUT /users/{user}/roles`. Mỗi endpoint có method, path, permission, request fields, response metadata type và một sample thành công đại diện.
- **Dependencies**: Tasks 1.2-1.3, Task 2.1.
- **Acceptance Criteria**:
  - Tài liệu nêu User list/show cần `users.view`; mutation cần `users.manage`.
  - Hiển thị initial role selection qua `POST /users` và replace role selection qua `PUT /users/{user}/roles`.
  - Ghi đúng guard `api`, chỉ role active mới chấp nhận, và không mô tả `user_name` là field update.
- **Validation**: So sánh mẫu payload với Form Request and `AccessManagementTest`.

### Task 2.3: Document Roles, Permissions and role-permission assignment

- **Location**: `docs/marrkdown/authorize.md`
- **Description**: Viết CRUD Role, CRUD Permission và `PUT /roles/{role}/permissions`. Mô tả nghĩa `is_system` để frontend khoá/ẩn action sửa-xoá theo dữ liệu trả về (system role/permission bị backend từ chối mutation).
- **Dependencies**: Tasks 1.2-1.3, Task 2.1.
- **Acceptance Criteria**:
  - Role endpoints yêu cầu `roles.manage`; permission list/detail yêu cầu `permissions.view`; permission mutation yêu cầu `permissions.manage`.
  - Gán permission cho role được ghi là replace all permission IDs, không phải append.
  - Sample role/permission response khớp keys từ service.
- **Validation**: Đối chiếu `AccessManagementService::role()`, `permission()` and related feature test.

### Task 2.4: Document effective and direct permissions for a User

- **Location**: `docs/marrkdown/authorize.md`
- **Description**: Tạo section “Quyền user” minh hoạ frontend lấy `GET /users/{user}` để hiển thị roles, `permissions` (effective) và `direct_permissions` (exception). Document `PUT /users/{user}/permissions` with its actual replace-all request/response.
- **Dependencies**: Tasks 2.2-2.3.
- **Acceptance Criteria**:
  - Định nghĩa rõ `direct_permissions` không gồm quyền cấp qua role; `permissions` gồm union trực tiếp và từ roles.
  - Có flow text: fetch User detail → hiển thị các nhóm → submit full direct permission ID selection only when using existing sync endpoint → reload from returned User metadata.
  - Nêu rõ API hiện tại không thể add/remove exactly one direct permission without sending replacement array; không hướng dẫn frontend gửi chỉ permission mới vì sẽ xoá các direct permissions còn lại.
- **Validation**: Đối chiếu `AccessManagementService::user()` và `syncUserPermissions()`.

## Sprint 3: Verify accuracy and publish

**Goal**: Đảm bảo docs không drift khỏi backend trong source hiện tại.

**Demo/Validation**:

- Reviewer có thể trace mọi endpoint/request/response từ README sang source code.
- Markdown render đúng và repository chỉ thay đổi documentation.

### Task 3.1: Perform route-to-document traceability review

- **Location**: `docs/marrkdown/authorize.md`, `app/Modules/Auth/Routes/index.php`, controllers/requests/services Auth
- **Description**: Review từng endpoint documented với route, middleware, FormRequest, success status and `metadata` shape. Check exclude-list to make sure auth token endpoints are absent.
- **Dependencies**: Sprint 2.
- **Acceptance Criteria**:
  - Không route quản trị nào thiếu hoặc có URI/method/permission sai.
  - Không field response nào nhầm `permissions` với `direct_permissions`.
  - Create endpoints correctly show 201, preventing frontend from assuming every success is 200.
- **Validation**: `php artisan route:list --path=auth --json`; manual JSON schema comparison.

### Task 3.2: Render/readability check and final repository review

- **Location**: `docs/marrkdown/authorize.md`
- **Description**: Render Markdown (IDE preview or GitHub-style renderer), ensure tables/code fences display, headings navigable, and samples valid JSON. Check git diff to confirm only documentation files were changed.
- **Dependencies**: Task 3.1.
- **Acceptance Criteria**:
  - `docs/marrkdown/authorize.md` is the sole product document created.
  - All examples use opaque placeholder IDs/tokens and no secrets.
- **Validation**: Markdown preview; `git diff --check`; `git status --short`.

## Testing Strategy

- Documentation traceability is validated against existing feature tests: `tests/Feature/Auth/AccessManagementTest.php` and `tests/Feature/Auth/SpatiePermissionTest.php`.
- Run `php artisan route:list --path=auth --json` to validate route/middleware claims.
- No PHP application code changes are planned, so PHPUnit/Pint are not required for the documentation-only change. If implementation diverges during work, run relevant Auth feature tests before publishing.

## Potential Risks & Gotchas

- **Conflict with individual direct-permission UX**: the requested UI behaviour is add/remove a single exception, but current `PUT /users/{user}/permissions` calls `syncPermissions()` and replaces the complete direct set. A README that says otherwise would cause data loss. The doc must state this limitation; a future additive API needs explicit backend scope.
- **HTTP 200-only expectation conflicts with existing API**: current POST create endpoints explicitly return HTTP `201`, while successful reads/updates/deletes/syncs return `200`. This document should report actual statuses, not normalize them to 200.
- System Roles and Permissions are returned with `is_system`; backend blocks edits/deletions. Frontend should use it to guide the UI, but server authorization remains source of truth.
- `DELETE /users/{user}` deactivates rather than deleting; show it as disable/deactivate in the frontend description.
- Permission names are a cross-module catalog (for example `customers.view`); this document should describe current name format but not invent labels or a screen taxonomy.

## Rollback Plan

- Documentation-only rollback: remove `docs/marrkdown/authorize.md` and its empty parent directory if it was created solely for this file.
- No routes, database records, migrations, configuration or package dependencies change.
