# Xây dựng ASP.NET Core Modular Boilerplate (.NET 10)

Tài liệu này là giáo trình để bạn xây một backend mới bằng **ASP.NET Core Web API .NET 10**, theo kiểu **modular monolith**. Nó lấy cảm hứng từ cách dự án Laravel hiện tại tách `app/Modules/<Domain>` thành controller, service, interface, model, request và route; tuy nhiên đây **không phải** tài liệu chuyển đổi hay sao chép nghiệp vụ Laravel sang C#.

Mục tiêu là có một nền tảng nhất quán để bạn có thể nhờ ChatGPT dạy từng bước và sau đó thêm module nghiệp vụ một cách an toàn.

## 1. Đích kiến trúc

### Nguyên tắc

- Một deployable ASP.NET Core Web API, một cơ sở dữ liệu MySQL, nhiều module nghiệp vụ độc lập tương đối.
- Module sở hữu controller, DTO, validation, service, entity và cấu hình dữ liệu của chính nó.
- `Shared` chỉ chứa các khái niệm kỹ thuật/thật sự dùng chung; không đưa nghiệp vụ của một module vào `Shared`.
- Controller mỏng: nhận HTTP, gọi service, trả DTO. Business rule nằm trong service.
- Entity EF Core là persistence model; không trả trực tiếp entity ra API.
- Không thêm CQRS, MediatR, microservice, event sourcing, refresh token, cache hay background job trong base v1.

### Cây thư mục mục tiêu

```text
AspNetModularBoilerplate.sln
src/
  WebApi/
    Controllers/
    Extensions/
    Middleware/
    Program.cs
    appsettings.json
  Shared/
    Contracts/
    Exceptions/
    Persistence/
    Responses/
    Security/
  Modules/
    Identity/
      Controllers/
      Contracts/
      DTOs/
      Entities/
      Services/
      Validators/
      Data/
      Mapping/
      IdentityModuleServiceCollectionExtensions.cs
    SampleCatalog/
      Controllers/
      Contracts/
      DTOs/
      Entities/
      Services/
      Validators/
      Data/
      Mapping/
      SampleCatalogModuleServiceCollectionExtensions.cs
tests/
  WebApi.IntegrationTests/
  Modules.UnitTests/
```

`WebApi` là composition root: nó khởi động app, đăng ký middleware, infrastructure và gọi `AddIdentityModule()` / `AddSampleCatalogModule()`. Nó không chứa logic nghiệp vụ của module.

> Bạn có thể để các project này trong một project C# duy nhất lúc học. Khi codebase đủ lớn, tách `WebApi`, `Shared`, từng module thành các class library mà không đổi ranh giới namespace hay public contract.

### Quy ước namespace và dependency

| Thành phần | Namespace ví dụ | Được tham chiếu | Không được làm |
| --- | --- | --- | --- |
| Shared | `Boilerplate.Shared.*` | Tất cả | Phụ thuộc vào module nghiệp vụ |
| Module | `Boilerplate.Modules.SampleCatalog.*` | Shared; public contract của module khác khi thật cần | Import `WebApi` hoặc truy cập DbSet/entity nội bộ module khác tùy tiện |
| Web API | `Boilerplate.WebApi.*` | Shared và modules | Chứa business rule |
| Tests | `Boilerplate.Tests.*` | Thành phần đang kiểm thử | Dùng database thật có dữ liệu |

Một module chỉ expose DTO/service contract tối thiểu cần thiết. Khi module A cần thao tác qua module B, inject contract của B; không gọi controller, không dùng HTTP vòng nội bộ và không sửa entity B trực tiếp.

## 2. Đối chiếu Laravel và ASP.NET

| Laravel hiện tại | ASP.NET Core mục tiêu | Ghi chú |
| --- | --- | --- |
| `app/Modules/<Domain>` | `src/Modules/<Domain>` | Giữ domain boundary dễ nhận biết |
| `Routes/index.php` | Attribute routing trên controller + module registration | API prefix toàn cục là `/api/v1` |
| Controller | `ControllerBase` + `[ApiController]` | Controller chỉ điều phối HTTP |
| Form Request | Request DTO + FluentValidation validator | Validation chạy trước service |
| Eloquent Model | EF Core entity + `IEntityTypeConfiguration<T>` | Mapping/table/index ở `Data` |
| Service + Interface | Service + contract interface | Đăng ký qua `Add<Module>()` |
| Service provider | `IServiceCollection` extension | Đây là nơi bind DI của module |
| Migration | EF Core migration | Tạo migration; không tự chạy trên DB có dữ liệu |
| Middleware / exception response | ASP.NET middleware + exception handler | Chuẩn hóa mọi lỗi API |
| PHP enum | C# enum, lưu string khi cần đọc dữ liệu dễ hơn | Chỉ dùng enum cho tập giá trị đóng |
| `DB::transaction()` | `await db.Database.BeginTransactionAsync()` | Transaction thuộc service/use case |
| API Resource / response trait | Response DTO + `ApiResponse<T>` | Không serialize entity trực tiếp |
| Feature test | xUnit + `WebApplicationFactory` integration test | Test HTTP, auth, validation và database isolation |

## 3. Hợp đồng kỹ thuật chung

### API và response

- Controllers dùng `[ApiController]`, `[Route("api/v1/[controller]")]`, async `Task<ActionResult<...>>` và cancellation token.
- URL phải là resource-oriented. Action workflow được phép rõ nghĩa, ví dụ `POST /api/v1/orders/{id}/approve`.
- Request DTO và response DTO tách riêng. `CreateProductRequest`, `UpdateProductRequest`, `ProductResponse` là các tên ví dụ.
- Tất cả response thành công theo một envelope, ví dụ:

```json
{
  "success": true,
  "message": "Product created.",
  "data": { "id": 42, "name": "Example" },
  "traceId": "..."
}
```

- Lỗi dùng Problem Details hoặc envelope lỗi thống nhất; chọn một format duy nhất từ đầu. Khuyến nghị dùng `application/problem+json`, có `status`, `title`, `detail`, `errors` (khi validation) và `traceId`.
- Quy ước status code: 201 tạo mới, 200 đọc/cập nhật/action thành công, 204 xóa thành công, 400 request sai, 401 chưa đăng nhập, 403 không đủ quyền, 404 không có resource, 409 vi phạm business state/unique conflict, 422 validation nếu đội ngũ muốn tách khỏi 400. Chọn **400 hoặc 422 cho validation một lần** và ghi trong README API; guide này dùng 400 theo mặc định ASP.NET.

### Persistence, transaction và migration

- `AppDbContext` ở `Shared/Persistence`; các module cung cấp class `IEntityTypeConfiguration<T>` qua `Data`.
- MySQL dùng EF Core provider tương thích với **EF Core 10/.NET 10**. Chọn Pomelo hoặc Oracle provider một lần cho solution; khuyến nghị Pomelo khi bắt đầu mới vì tài liệu cộng đồng phổ biến.
- Entity có `Id`, `CreatedAtUtc`, `UpdatedAtUtc`, `IsActive` khi module cần soft deactivation. Không áp dụng soft delete một cách máy móc cho mọi bảng.
- Service chịu trách nhiệm bắt đầu transaction khi một use case ghi nhiều aggregate/bảng hoặc cần atomicity. Không mở transaction cho một cập nhật đơn giản chỉ có một `SaveChangesAsync`.
- Migration được review vào source control. Tuyệt đối không chạy `database update` tự động lên database có dữ liệu quan trọng; backup và phê duyệt thay đổi schema trước.

### Authentication và authorization

- Dùng JWT bearer authentication.
- `Identity` module sở hữu user, role, permission, password hash và token issue/login.
- Role là nhóm quyền; permission là claim/policy cho hành động cụ thể, ví dụ `products.read`, `products.manage`.
- Controller/action dùng `[Authorize]` và `[Authorize(Policy = Permissions.ProductsManage)]`. Service vẫn kiểm tra business ownership khi dữ liệu phụ thuộc người dùng/tenant.
- Không đặt authorization bằng chuỗi rải rác: đưa permission name vào constants hoặc static class dùng chung.

### DI, mapping, validation, logging

- Mỗi module có extension `Add<ModuleName>Module(this IServiceCollection services)` để đăng ký services, validators và mapping profile thuộc module.
- Service có lifetime `Scoped`; `DbContext` mặc định scoped. Tránh singleton phụ thuộc scoped service.
- FluentValidation validate request DTO; business validation cần database/state ở service.
- AutoMapper chỉ thực hiện mapping DTO/entity đơn giản. Không giấu business rule trong mapping profile.
- Structured logging dùng `ILogger<T>`; middleware gắn `traceId` cho request/log/response. Không log password, JWT hay connection string.

## 4. Giáo trình xây boilerplate

Mỗi chặng phải hoàn thành checkpoint trước khi sang chặng sau. Các lệnh dưới đây được viết cho .NET SDK 10; hãy dùng phiên bản package tương thích EF Core 10 và kiểm tra release notes của provider MySQL trước khi chốt version.

### Chặng 1 — Tạo solution và package nền tảng

**Mục tiêu:** Có solution chạy được với cấu trúc source/test rõ ràng.

**Liên hệ Laravel:** tương đương tạo skeleton Laravel, composer dependencies và cấu hình app.

```powershell
dotnet new sln --name AspNetModularBoilerplate
dotnet new webapi --name WebApi --output src/WebApi --framework net10.0
dotnet new classlib --name Shared --output src/Shared --framework net10.0
dotnet new xunit --name WebApi.IntegrationTests --output tests/WebApi.IntegrationTests --framework net10.0
dotnet new xunit --name Modules.UnitTests --output tests/Modules.UnitTests --framework net10.0
dotnet sln add src/WebApi/WebApi.csproj src/Shared/Shared.csproj tests/WebApi.IntegrationTests/WebApi.IntegrationTests.csproj tests/Modules.UnitTests/Modules.UnitTests.csproj
dotnet add src/WebApi/WebApi.csproj reference src/Shared/Shared.csproj
dotnet add tests/WebApi.IntegrationTests/WebApi.IntegrationTests.csproj reference src/WebApi/WebApi.csproj
dotnet add tests/Modules.UnitTests/Modules.UnitTests.csproj reference src/Shared/Shared.csproj
```

Sau khi tạo các project module (chặng 4 và 5), thêm project/reference của chúng vào solution thay vì để `WebApi` tự biết file trong module.

**Package cần có:** EF Core Design/Tools, MySQL EF Core provider, FluentValidation ASP.NET integration, AutoMapper, JWT bearer authentication, Swagger/OpenAPI, `Microsoft.AspNetCore.Mvc.Testing`, test database provider phù hợp. Chỉ thêm package khi nó phục vụ chức năng trong chặng hiện tại.

**Checkpoint:** `dotnet build` và `dotnet test` chạy thành công; source không còn endpoint WeatherForecast mặc định.

**Lỗi thường gặp:** chọn package EF Core major version khác .NET/EF Core 10; quên reference `WebApi` từ integration test.

**Prompt gửi ChatGPT:**

```text
Tôi đang học ASP.NET Core .NET 10. Hãy hướng dẫn riêng Chặng 1 của modular boilerplate:
- tạo solution gồm WebApi, Shared, WebApi.IntegrationTests, Modules.UnitTests;
- dùng cấu trúc src/ và tests/;
- giải thích đối chiếu từng bước với Laravel;
- chỉ dùng lệnh PowerShell an toàn, không chạy migration/database update;
- chờ tôi xác nhận dotnet build và dotnet test pass trước khi sang chặng 2.
Không tạo CQRS, MediatR, microservice hay mã nghiệp vụ.
```

### Chặng 2 — Shared và Web API composition root

**Mục tiêu:** Thiết lập cấu hình, health endpoint, response contract, error handler, correlation/trace ID và nơi lắp ghép module.

**Liên hệ Laravel:** tương ứng `bootstrap/app.php`, exception response và `AppServiceProvider`.

**Cần làm:**

1. Tạo `ApiResponse<T>`, các exception nghiệp vụ (`NotFoundException`, `ConflictException`, `ForbiddenException`) và global exception middleware/handler trong `Shared` + `WebApi/Middleware`.
2. Trong `Program.cs`: đọc cấu hình, thêm controllers, ProblemDetails, OpenAPI/Swagger, health checks, logging, middleware exception và `MapControllers()`.
3. Tạo extension trong `WebApi/Extensions` để `Program.cs` chỉ còn các lời gọi mức cao như `AddApiServices`, `UseApiPipeline`, `AddPersistence`, `AddIdentityModule`.
4. Chuẩn hóa UTC: lưu timestamp bằng UTC và truyền/hiển thị timezone tại layer client khi cần.

**Checkpoint:** `/health` trả 200; endpoint thử nghiệm ném exception trả JSON nhất quán, không lộ stack trace khi production; Swagger chỉ hiển thị bên development.

**Lỗi thường gặp:** catch exception ngay trong controller; đổi format response tùy endpoint; ghi exception detail nhạy cảm ra production.

**Prompt gửi ChatGPT:**

```text
Hướng dẫn Chặng 2 cho ASP.NET Core .NET 10 modular monolith của tôi. Hãy tạo composition root, ApiResponse<T>, ProblemDetails/error middleware, traceId, health endpoint và Swagger development-only. Giải thích tương ứng Laravel bootstrap/app.php, ApiResponse trait và service provider. Chỉ cho mã tối thiểu theo từng file, rồi đưa checklist chạy thử; dừng sau checkpoint.
```

### Chặng 3 — MySQL, EF Core và migration

**Mục tiêu:** Cấu hình `AppDbContext`, conventions audit tối thiểu, migration workflow và cách cô lập database trong test.

**Liên hệ Laravel:** tương ứng `config/database.php`, Eloquent base model và migrations.

**Cần làm:**

1. Khai báo connection string MySQL bằng User Secrets/local environment, không commit password vào `appsettings.json`.
2. Đăng ký `AppDbContext` và MySQL provider trong extension persistence; tách options/configuration khỏi `Program.cs`.
3. Tạo base entity/audit interceptor chỉ khi tất cả entity thật sự dùng nó.
4. Cho DbContext discover configurations từ module assemblies; mỗi entity mapping nằm gần module sở hữu nó.
5. Thiết lập migration command rõ project startup/target. Review migration generated trước khi áp dụng.

```powershell
# Ví dụ sau khi có DbContext và chọn provider/version tương thích EF Core 10
dotnet ef migrations add InitialCreate --project src/WebApi --startup-project src/WebApi
dotnet ef migrations script --idempotent --project src/WebApi --startup-project src/WebApi
```

**Checkpoint:** app khởi động được khi có connection string hợp lệ; `migrations script` sinh script; không chạy `dotnet ef database update` lên database có dữ liệu khi chưa có approval/backup.

**Lỗi thường gặp:** để secrets trong Git, để entity config trong controller, hay dùng InMemory provider để khẳng định hành vi relational MySQL.

**Prompt gửi ChatGPT:**

```text
Hướng dẫn Chặng 3: cấu hình EF Core 10 với MySQL trong modular ASP.NET Web API. Tôi cần AppDbContext dùng chung, IEntityTypeConfiguration đặt trong module, audit timestamps UTC, migration command và test isolation. Không chạy database update. Nêu rõ khác biệt với Eloquent migration/model của Laravel và chờ checkpoint.
```

### Chặng 4 — Module mẫu `SampleCatalog`

**Mục tiêu:** Học một module hoàn chỉnh, trung tính về nghiệp vụ.

`SampleCatalog` quản lý `Category` và `Product` để chứng minh CRUD, quan hệ 1-n, enum, pagination/filter, validation, deactivation và response DTO.

Tạo project module trước khi thêm code, rồi reference nó từ `WebApi` và test project tương ứng:

```powershell
dotnet new classlib --name SampleCatalog --output src/Modules/SampleCatalog --framework net10.0
dotnet sln add src/Modules/SampleCatalog/SampleCatalog.csproj
dotnet add src/Modules/SampleCatalog/SampleCatalog.csproj reference src/Shared/Shared.csproj
dotnet add src/WebApi/WebApi.csproj reference src/Modules/SampleCatalog/SampleCatalog.csproj
dotnet add tests/Modules.UnitTests/Modules.UnitTests.csproj reference src/Modules/SampleCatalog/SampleCatalog.csproj
```

| Nhóm | Nội dung tối thiểu |
| --- | --- |
| Entities | `Category`, `Product`, `ProductStatus` enum; `Product` có FK `CategoryId` |
| DTOs | create/update request, filter query, list item, detail response, paged response |
| Validators | name required/length, SKU unique ở service/database, category hợp lệ, giá không âm |
| Data | entity mappings, unique index SKU, index cho filter thường dùng |
| Contracts/Services | CRUD async, `GetPagedAsync`, `SetActiveAsync`; business rule và transaction khi ghi nhiều bảng |
| Controllers | `CategoriesController`, `ProductsController`, `/api/v1/...`, không trả entity |
| Mapping | DTO/entity mappings đơn giản; computed/business field do service tạo |
| Registration | `AddSampleCatalogModule()` đăng ký service, validators, AutoMapper profile/configurations cần thiết |

**Quy tắc CRUD:**

- `GET /api/v1/products?page=1&pageSize=20&search=&categoryId=&status=` trả list phân trang có tổng số.
- `GET /api/v1/products/{id}` trả detail hoặc 404.
- `POST /api/v1/products` trả 201; SKU trùng trả 409.
- `PUT /api/v1/products/{id}` cập nhật toàn bộ contract đã công bố; `PATCH` chỉ dùng khi thực sự cần partial update.
- `DELETE` chỉ deactive nếu module chọn `IsActive`; list mặc định bỏ record inactive. Không vừa hard-delete vừa soft-delete cho cùng một resource mà không có rule rõ ràng.

**Checkpoint:** Swagger chạy được toàn bộ CRUD; invalid request nhận lỗi validation; SKU trùng nhận 409; category không tồn tại nhận 404/400 theo contract đã chọn; entity không xuất hiện trực tiếp trong JSON.

**Lỗi thường gặp:** để controller gọi DbContext; N+1 query; validation unique chỉ ở application mà không có unique database index; quên cancellation token.

**Prompt gửi ChatGPT:**

```text
Hãy dạy tôi Chặng 4: xây module SampleCatalog trong ASP.NET Core .NET 10 modular monolith. Giữ cấu trúc Modules/SampleCatalog/{Controllers,Contracts,DTOs,Entities,Services,Validators,Data,Mapping}. Module có Category và Product (1-n), CRUD, filter/phân trang, IsActive, ProductStatus enum, FluentValidation, EF Core configuration, unique SKU và response DTO. Controller phải mỏng; service sở hữu business rule. Hãy tạo theo từng nhóm file, giải thích đối chiếu Laravel Module/Model/FormRequest/Service/Route, rồi dừng để tôi test Swagger.
```

### Chặng 5 — Identity, JWT và permissions

**Mục tiêu:** Thêm identity foundation trước khi bảo vệ module nghiệp vụ.

**Cần làm:**

1. Tạo `Identity` module với `User`, `Role`, `Permission` và quan hệ cần thiết; dùng password hasher chuẩn thay vì tự hash password.
2. Cấu hình JWT options bằng configuration + secrets/environment variables; validate signing key, issuer, audience và expiration.
3. Tạo login endpoint trả access token. Refresh token không thuộc v1.
4. Seed development user/role/permission một cách rõ ràng, idempotent và không chứa credential production.
5. Định nghĩa permission policy provider hoặc policies đăng ký tập trung. Áp dụng `sample_catalog.read` và `sample_catalog.manage` cho controller mẫu.

**Checkpoint:** anonymous request tới protected endpoint nhận 401; valid JWT thiếu permission nhận 403; đúng permission gọi được endpoint tương ứng; password/JWT không nằm trong log hoặc API response.

**Lỗi thường gặp:** dùng role check ở mọi nơi thay vì permission; để JWT signing key trong Git; coi 401 và 403 là một lỗi.

**Prompt gửi ChatGPT:**

```text
Hướng dẫn Chặng 5 cho boilerplate .NET 10: Identity module, JWT bearer login, role/permission và ASP.NET authorization policies. Không dùng refresh token. Bảo vệ SampleCatalog bằng permissions read/manage. Giải thích tương ứng Laravel auth:api + Spatie permission; đưa test cases 401/403/200 và dừng sau checkpoint.
```

### Chặng 6 — Hoàn thiện API experience và observability

**Mục tiêu:** Một API dễ sử dụng và dễ chẩn đoán trước khi thêm nhiều module.

**Cần làm:**

- Hoàn thiện exception-to-ProblemDetails mapping: validation, not found, conflict, unauthorized, forbidden, unexpected errors.
- Thêm request logging có `traceId`, thời gian xử lý và status code; redact header/body nhạy cảm.
- Cấu hình Swagger JWT bearer security scheme và mô tả endpoint/response chuẩn.
- Rà pagination, ordering default, max page size và OpenAPI examples của module mẫu.

**Checkpoint:** Swagger authorize bằng bearer token; mỗi mã lỗi quan trọng hiển thị body nhất quán; một `traceId` tra được request/error trong log.

### Chặng 7 — Unit test, integration test và definition of done

**Mục tiêu:** Biến contract thành test tự động.

| Loại | Kiểm thử |
| --- | --- |
| Unit | Service rule, transition/rule, calculation không cần HTTP |
| Integration | Route, auth, permission, model binding, validation, error response, database persistence |
| Regression | SKU unique, inactive record, pagination/filter, 401/403/404/409 |

- Dùng xUnit, `WebApplicationFactory<Program>` và database test tách biệt. Với integration test database behavior quan trọng, dùng MySQL test container/DB riêng thay vì chỉ EF InMemory.
- Thêm `public partial class Program { }` ở cuối `WebApi/Program.cs` để test project truy cập được entry point của top-level app.
- Một test chỉ chuẩn bị dữ liệu cần thiết; không phụ thuộc dữ liệu phát sinh từ test trước.
- Ưu tiên tên test mô tả hành vi, ví dụ `CreateProduct_returns_conflict_when_sku_already_exists`.

**Checkpoint:** unit + integration tests pass, không gọi database development/production và bao phủ ít nhất happy path, validation, 401, 403, 404, 409 cho `SampleCatalog`.

**Prompt gửi ChatGPT:**

```text
Hãy dạy tôi Chặng 7: viết xUnit unit test và WebApplicationFactory integration test cho SampleCatalog modular boilerplate .NET 10. Bao phủ CRUD happy path, validation, duplicate SKU conflict, inactive record, 401/403/404 và pagination. Hãy cô lập database test, không đụng MySQL development/production, giải thích tương ứng Laravel Feature/Unit tests và đưa lệnh chạy test.
```

## 5. Master prompt dùng với ChatGPT

Sao chép prompt này vào một cuộc chat mới, sau đó bắt đầu bằng “Chặng 1”.

```text
Bạn là người hướng dẫn ASP.NET Core .NET 10 cho tôi, một backend developer đã quen Laravel modular architecture.

Mục tiêu: cùng tôi xây một ASP.NET Core Web API modular monolith mới, không clone nghiệp vụ hay dữ liệu Laravel. Stack: .NET 10, Controllers, EF Core 10 + MySQL, FluentValidation, AutoMapper, JWT bearer, role/permission policies, Swagger, xUnit + WebApplicationFactory.

Kiến trúc cố định:
- src/WebApi là composition root và API host.
- src/Shared chứa technical cross-cutting concerns, không chứa nghiệp vụ module.
- src/Modules/<Module> dùng Controllers, Contracts, DTOs, Entities, Services, Validators, Data, Mapping và extension Add<Module>Module().
- DTO request/response tách entity; controller mỏng; business rule/transaction trong service.
- API prefix /api/v1; response thành công có envelope; lỗi theo ProblemDetails với traceId.
- Auth là JWT; authorization dùng permission policies.
- Không đưa CQRS, MediatR, microservices, event sourcing, refresh tokens, caching hoặc background jobs vào bản v1.

Cách dạy bắt buộc:
1. Chỉ dạy một chặng tôi yêu cầu; không làm trước chặng sau.
2. Mở đầu bằng mục tiêu và bảng đối chiếu Laravel ↔ ASP.NET ngắn gọn.
3. Đề xuất file/lệnh theo từng nhóm nhỏ, giải thích vì sao chúng thuộc module hoặc Shared.
4. Không tự chạy migration/database update hay thao tác database có dữ liệu.
5. Không bịa version package; yêu cầu tôi xác nhận hoặc dùng version tương thích .NET/EF Core 10.
6. Cuối mỗi chặng, đưa lệnh verify, expected output, lỗi thường gặp và checkpoint. Dừng chờ tôi xác nhận.
7. Khi tôi gửi code/lỗi, ưu tiên sửa tối thiểu theo kiến trúc này và giải thích nguyên nhân.

Hãy xác nhận lại kiến trúc trong 8 gạch đầu dòng, rồi chờ tôi yêu cầu “Chặng 1”.
```

## 6. Prompt mẫu khi mở rộng boilerplate

### Tạo module mới

```text
Thiết kế module <ModuleName> cho modular monolith .NET 10 của tôi. Nghiệp vụ: <mô tả ngắn>. Hãy hỏi tối đa các câu hỏi thực sự quyết định entity, API, permission và workflow. Sau đó đề xuất: folder/file theo chuẩn module, DTOs, entities, EF Core mappings/indexes, validators, service contracts, API routes `/api/v1`, permissions, error cases và xUnit tests. Không tạo CQRS/MediatR hay truy cập trực tiếp entity nội bộ module khác.
```

### Thêm CRUD

```text
Trong module <ModuleName>, hãy hướng dẫn thêm CRUD cho <Resource>. Giữ controller mỏng, request/response DTO tách entity, FluentValidation, service scoped, AutoMapper chỉ mapping đơn giản, EF configuration/index phù hợp và ProblemDetails chuẩn. Trước khi code, hãy nêu API contract (route, request, response, status code, permission) và các test happy path/validation/404/409.
```

### Thêm workflow trạng thái

```text
Tôi cần workflow cho <Resource>: <các trạng thái và quy tắc đã biết>. Hãy lập transition table gồm trạng thái nguồn, đích, permission, điều kiện và lỗi 409 cho transition bất hợp lệ. Sau khi tôi xác nhận table, hướng dẫn triển khai enum, service methods, transaction nếu cần, action endpoints, authorization và tests. Không nhét workflow rule vào controller hoặc AutoMapper.
```

### Tạo migration an toàn

```text
Tôi cần thay đổi schema cho module <ModuleName>: <mô tả>. Hãy phân tích data impact, entity configuration, foreign key/index và backward compatibility. Đưa lệnh tạo migration + idempotent SQL script cho .NET 10/EF Core 10, nhưng không đưa lệnh database update trừ khi tôi xác nhận môi trường database test trống. Nêu rõ checklist backup/review/rollback.
```

### Thêm JWT/permission cho endpoint

```text
Hãy bảo vệ endpoint <METHOD PATH> trong ASP.NET modular boilerplate của tôi bằng permission <permission.name>. Đề xuất thay đổi tối thiểu ở Identity module, policy registration, controller attribute và integration tests cho 401, 403 và success. Không kiểm tra role bằng chuỗi rải rác khi permission policy là abstraction đã chọn.
```

## 7. Checklist hoàn thiện base v1

- [ ] Solution .NET 10 build và test được từ máy mới sau khi cấu hình secrets.
- [ ] Tree `WebApi` / `Shared` / `Modules` / `tests` được tạo; dependency direction không bị đảo.
- [ ] API dùng `/api/v1`, controllers, DTOs, response success và ProblemDetails lỗi nhất quán.
- [ ] MySQL/EF Core được cấu hình qua secrets/environment; migration được review và lưu source control.
- [ ] `SampleCatalog` chứng minh CRUD, relation, filter/paging, enum, unique index, validation và deactivate.
- [ ] `Identity` có JWT login, role/permission và policy enforcement; 401 khác 403.
- [ ] Swagger development-only có bearer authorization; logging có trace ID và không lộ secrets.
- [ ] Unit/integration tests cô lập database, pass cho happy path và các lỗi quan trọng.
- [ ] Không có code business-specific của dự án Laravel trong boilerplate.

## 8. Sau base v1

Chỉ cân nhắc bổ sung các khả năng sau khi có use case cụ thể và test nền tảng ổn định: refresh token/revocation, audit history đầy đủ, outbox/event integration, distributed cache, background jobs, file storage, multi-tenancy, rate limiting nâng cao và tách project module thành class library. Mỗi lựa chọn phải được đánh giá theo nhu cầu thực tế, không thêm chỉ vì “enterprise”.
