# AI Recommendation V1 — Backend Design

**Trạng thái:** Internal recommendation capability; public form interface đã superseded  
**Nguồn nghiệp vụ:** `docs/02-business-requirements/ai-recommendation-v1-functional-spec.md`

## Superseded interface decision — AI Chat Support

Thiết kế public `POST /recommendations`, `RecommendationController`, `StoreRecommendationRequest` và named limiter `recommendations` bên dưới không còn hiệu lực. Không có replacement endpoint tạm thời; browser/client không gọi trực tiếp recommendation service.

`RecommendationInput` là DTO nội bộ chứa dữ liệu có cấu trúc do chat application service tương lai cung cấp. `RecommendationService` nhận DTO này cùng optional authenticated `User` và Cart snapshot tối thiểu, không phụ thuộc HTTP Request. Engine, context builder, hydrator, provider contract, Gemini adapter và fallback vẫn là internal tool. `POST /cart/items/batch` vẫn được giữ như action có xác nhận rõ ràng của khách. Mục này là quyết định thay thế có thẩm quyền cho interface V1 ban đầu.

## 1. Design goals

- Rule-based recommendation chạy độc lập, xác định được kết quả ổn định với cùng input/context.
- Gemini là adapter tùy chọn sau contract; timeout hoặc output lỗi phải chuyển sang rule-based.
- Database và Cart hiện tại là source of truth cho Product, giá, availability và quantity.
- Guest dùng được; lịch sử chỉ bổ sung khi account có Customer hợp lệ.
- Tận dụng Laravel service/Form Request/provider pattern hiện có, không tạo `app/Modules` hoặc nhiều tầng kiến trúc.

## 2. Internal input contract

`RecommendationInput` nhận dữ liệu đã được application service gọi nó chuẩn hóa; đây không phải JSON/form contract công khai:

| Field | Rule V1 | Ghi chú |
|---|---|---|
| `party_size` | required, integer, min `1`, max `50` | Số người trong nhóm. |
| `budget` | nullable, integer, min `10_000`, max `100_000_000` | Tổng ngân sách VND; không dùng số thập phân. |
| `preferences` | nullable, array, max `5`, distinct | Mỗi phần tử là string thuộc allowlist V1. |
| `note` | nullable, string, max `300` ký tự | Chỉ là nhu cầu ngắn; không được xem là dữ liệu dinh dưỡng/dị ứng đáng tin cậy. |

Allowlist preference V1: `popular`, `beer`, `non_alcoholic`, `appetizer`, `grilled`, `seafood`, `hotpot`. Các token này map bằng config sang Category slug; không suy diễn dị ứng, thành phần hoặc sức khỏe từ token/note. Token ngoài allowlist bị trả validation error, không âm thầm bỏ qua.

Các field bị `prohibited`: `price`, `unit_price`, `estimated_total`, `budget_status`, `source`, `customer_id`, `user_id`, `product`, `products`, `product_data`, `availability`, `is_available`. Form Request chỉ trả bốn field đã validate; field lạ khác cũng bị từ chối để tránh contract trôi.

## 3. Internal result contract

Internal tool trả `RecommendationResult`:

```text
items[]
  product_id: integer
  name: string                  # hydrate từ DB
  image_url: string|null        # hydrate từ DB
  category: {id, name, slug}    # hydrate từ DB
  price: integer                # hydrate từ DB
  availability: true            # kết quả cuối chỉ chứa món hợp lệ
  quantity: integer
  reason: string
summary: string
estimated_total: integer
budget: integer|null
budget_status: not_provided|within_budget|over_budget
source: ai|rule_based|popular
```

- Mục tiêu là `3..6` item; kết quả hợp lệ có thể chỉ có `1..2` khi catalog không đủ món đạt điều kiện. Không bao giờ vượt `6`.
- Quantity mỗi item là `1..50`. Tổng quantity sau khi cộng Cart vẫn chịu giới hạn `1..1000`/Product của `CustomerCartService`.
- `estimated_total = Σ(database price × normalized quantity)`, dùng integer VND và kiểm tra overflow. `budget_status` là `not_provided` nếu budget null, ngược lại so sánh total để trả `within_budget` hoặc `over_budget`.
- Không có recommendation được biểu diễn bằng HTTP `200`, `items: []`, `summary` thân thiện hướng về Menu, `estimated_total: 0`, budget echo, budget status theo quy tắc trên và `source: popular` vì đây là fallback cuối. Không trả lỗi provider.
- Tên, ảnh, Category, giá và availability luôn hydrate lại sau khi kiểm tra Product ID. Provider không được quyết định hoặc ghi đè các field này.

## 4. Context sources

`RecommendationContextBuilder` tạo context tạm trong một request:

- **Eligible catalog:** `Product` chưa soft-delete, `status=active`, `is_available=true`; Category tồn tại, chưa soft-delete và `status=active`. Eager-load Category và media.
- **Time:** thời gian theo `config('app.timezone')`, quy về bucket `morning`, `lunch`, `afternoon`, `evening`, `late_night`.
- **Current Cart:** snapshot chỉ gồm `product_id`, quantity và Category của Product hợp lệ; không gửi Cart note cho provider.
- **Popularity:** từ `order_items` của Dining Order trong 90 ngày gần nhất, bỏ item `cancelled`, chỉ tính Dining Session completed/Bill paid; group theo `product_id`, `SUM(quantity)`, rồi chuẩn hóa theo rank thành điểm `0..25`. Product không có giao dịch nhận `0`.
- **Customer history:** chỉ khi optional `User` truyền vào có Customer profile. Dùng Dining Session của Customer đã completed/Bill paid trong 180 ngày gần nhất, bỏ item cancelled, group theo Product ID với `COUNT(DISTINCT order_id)` và `SUM(quantity)`, lấy tối đa 20 Product có quantity cao nhất. Không dùng `created_by_customer_id` vì lịch sử tại bàn có thể gồm Order do Staff tạo.
- **Weather:** để extension point, không truy vấn hoặc gọi provider trong lát cắt đầu tiên.

Context gửi Gemini chỉ gồm input đã validate, time bucket, catalog cần thiết (`product_id`, Category token, mô tả ngắn, current price), Cart Product ID/quantity, popularity/history aggregate không định danh. Không gửi Customer/User ID, tên, email, phone, address, order/session code, notes từ Cart/Order hoặc ghi chú hồ sơ.

## 5. Rule-based algorithm

### 5.1 Lọc và chấm điểm

1. Lấy eligible catalog trước khi xếp hạng.
2. Tính điểm integer cho từng Product:
   - preference khớp Category mapping: `+40`/token, tối đa `+80`;
   - popularity rank: `0..25`;
   - time/category affinity từ config: `0..10`;
   - history rank của Customer: `0..15`;
   - bổ trợ Category cho Cart hiện tại: `+15`;
   - Product đã có trong Cart: `-100` để chỉ được chọn khi catalog không đủ lựa chọn khác.
3. Sort cố định theo `score DESC`, `category.sort_order ASC`, `product.id ASC`; không random.

Mapping complement V1 để cross-sell: beer → appetizer/grilled/seafood; grilled/seafood/hotpot → beverage; hotpot → appetizer/beverage; Cart không có mapping thì không cộng điểm. Mapping preference, complement và time affinity đặt trong `config/recommendation.php`, dùng token nghiệp vụ thay vì hard-code trong engine.

### 5.2 Chọn set và quantity

- Target item count: party `1..2 → 3`, `3..5 → 4`, `6..10 → 5`, `11..50 → 6`.
- Chọn theo vòng qua các Category đã xếp hạng, tối đa 2 Product/Category; ưu tiên có ít nhất một món ăn và một đồ uống khi catalog tương ứng tồn tại. Sau vòng cân bằng, lấp chỗ trống theo ranking chung.
- Quantity: beverage `min(50, party_size)`; hotpot `min(50, ceil(party_size/6))`; nhóm thức ăn khác `min(50, ceil(party_size/4))`. Category group lấy từ config; không nhận classification từ client/provider.
- Có budget: thử set cân bằng theo ranking, sau đó thay Product bằng lựa chọn rẻ hơn cùng nhóm và giảm item count nhưng không dưới 3 khi catalog có đủ. Không giảm quantity dưới công thức trên chỉ để làm đẹp trạng thái budget.
- Nếu mọi set 3 món hợp lệ đều vượt budget, trả set 3 món hợp lệ rẻ nhất vẫn giữ cân bằng tối đa và `budget_status=over_budget`. Budget thấp không làm kết quả rỗng.
- Nếu không có đủ 3 Product hợp lệ, trả `1..2`; nếu không có Product hợp lệ, dùng empty result ở mục 3.

Nguồn là `rule_based`. Nếu rule-based không tạo được item nhưng catalog còn Product, fallback cuối lấy tối đa target count Product phổ biến theo `popularity DESC`, `category.sort_order`, `product.id`; nguồn là `popular`.

## 6. Provider và fallback flow

Các trách nhiệm tách vừa đủ:

- `RecommendationService`: orchestration duy nhất cho use case.
- `RecommendationContextBuilder`: đọc catalog/Cart/popularity/history và tạo context.
- `RuleBasedRecommendationEngine`: scoring, balance, quantity và budget fitting.
- `RecommendationProvider` contract: nhận context không định danh, trả provider draft hoặc unavailable.
- `GeminiRecommendationProvider`: gọi Gemini, giới hạn timeout và chuyển lỗi thành unavailable.
- `NullRecommendationProvider`: luôn unavailable; là binding mặc định khi AI tắt/misconfigured.
- `ProviderOutputNormalizer`: parse và kiểm tra cấu trúc draft, không hydrate business data.
- `RecommendationResultHydrator`: load lại Product và dựng response cuối.

Flow:

```text
Nhận structured internal input
→ Build context
→ AI enabled? gọi provider : unavailable
→ Normalize/validate provider draft
→ Invalid/unavailable? RuleBasedRecommendationEngine
→ Rule không có item? Popular fallback
→ Hydrate lại toàn bộ Product từ DB
→ Final validation active/category active/available + quantity
→ Tính lại price/estimated_total/budget_status
→ Normalized response
```

Timeout Gemini khởi đầu `2.5s`; không retry trong một lần gọi để giữ latency và tránh gọi lặp/rate amplification. Gemini chưa cấu hình không ngăn rule-based hoạt động. Chat rate limiter thuộc thiết kế Chat Support sau này.

Fallback khi provider timeout/network/HTTP error; output không parse được; schema sai; items rỗng hoặc ngoài `1..6`; ID/quantity không phải integer; ID trùng, không có trong eligible catalog hoặc Product hết hợp lệ khi hydrate; quantity ngoài `1..50`; reason/summary thiếu hoặc vượt giới hạn; output chứa field nghiệp vụ cấm như price/availability/total/customer data. Toàn bộ AI draft bị bỏ, không trộn draft hỏng với fallback.

## 7. Output validation

- Provider draft chỉ được phép có `items[{product_id, quantity, reason}]` và `summary`; reason tối đa 160 ký tự, summary tối đa 500 ký tự.
- Hydrator query tất cả ID một lần với Category/media, so khớp đủ ID và áp dụng đúng predicate eligible catalog.
- Quantity được clamp/recompute theo rule V1; không tin quantity quá giới hạn từ provider.
- Provider-supplied name, price, image, category, availability, total, budget status hoặc source bị coi là vi phạm contract và kích hoạt fallback.
- Final validator kiểm tra unique Product ID, `0..6` item, quantity, integer multiplication không overflow, tổng tiền và budget status. Nếu AI fail final validation, chạy lại rule-based bằng context hiện tại; nếu fallback cũng không hợp lệ, trả popular/empty result.
- `source=ai` chỉ khi AI draft vượt toàn bộ validation và hydration; client không được gửi/chọn source.

## 8. Add-entire-set transaction behavior

Endpoint `POST /cart/items/batch` nhận duy nhất:

```text
items: required array, min 1, max 6, distinct product_id
items.*.product_id: required integer
items.*.quantity: required integer, min 1, max 50
```

Mọi field giá, total, Product metadata, customer/source bị cấm. Backend không nhận recommendation token hoặc tin recommendation cũ.

Mở rộng `CustomerCartService` bằng operation batch dùng chung predicate/validation với `add()`:

1. Load toàn bộ Product bằng ID, kèm cả soft-deleted Product/Category để phát hiện invalid; thiếu ID cũng là invalid.
2. Validate tất cả Product active, Category active, available và chưa xóa.
3. Đọc Cart hiện tại, validate cấu trúc, tạo bản sao in-memory; cộng quantity hiện có và đảm bảo mỗi Product không vượt `1000`.
4. Chỉ sau khi toàn bộ batch hợp lệ mới gọi một lần `session()->put(SESSION_KEY, newCart)`; không gọi `add()` tuần tự và không ghi session giữa vòng lặp.
5. Route bật Laravel session blocking `->block(5, 5)` để tránh hai request cùng session ghi đè nhau. Không cần database transaction vì Cart là session state; single-write sau validate là ranh giới atomic của operation.

Bất kỳ lỗi nào giữ nguyên Cart và trả validation error thân thiện `recommendation_set_changed`, hướng khách tạo lại recommendation hoặc thêm riêng món còn hợp lệ. Response không tiết lộ lỗi provider. Voucher/fulfillment state hiện có không bị thay đổi bởi add batch.

## 9. Proposed Laravel components

- **Routes:** chỉ giữ `POST /cart/items/batch` → `Customer\CartController@storeBatch` với session blocking. Không có public recommendation route.
- **Form Requests:** `StoreCartBatchRequest` giữ allowlist/prohibited rules cho Cart action.
- **Controllers:** không có Recommendation Controller; Chat adapter/orchestrator sẽ được thiết kế ở khâu sau.
- **Services:** `Services/Recommendation/RecommendationService`, `RecommendationContextBuilder`, `RuleBasedRecommendationEngine`, `ProviderOutputNormalizer`, `RecommendationResultHydrator`.
- **Contract/adapters:** `Contracts/RecommendationProvider`; `Services/Recommendation/GeminiRecommendationProvider` và `NullRecommendationProvider`. Binding trong `AppServiceProvider` theo config, cùng cách cô lập như Translation provider.
- **DTO/value objects cần thiết:** `RecommendationInput`, `RecommendationContext`, `RecommendationDraft`, `RecommendationItem`, `RecommendationResult`. Dùng readonly DTO để giữ contract; không tạo repository/interface cho Eloquent query nội bộ V1.
- **Cart:** tái sử dụng `CustomerCartService`; gom eligibility validation thành một đường dùng chung cho `add()` và batch, bổ sung read-only Cart snapshot cho context và atomic `addBatch()`.

Không tạo domain module, event, queue hoặc cache layer trong V1.

## 10. Configuration và feature flag

Thêm `config/recommendation.php`:

- `enabled` / `FEATURE_RECOMMENDATION` — bật endpoint/capability tổng; mặc định `true` khi rollout đã sẵn sàng.
- `ai_enabled` / `RECOMMENDATION_AI_ENABLED` — bật riêng provider; mặc định `false`, rule-based vẫn chạy.
- `provider` — `null|gemini`; mặc định `null`.
- `gemini.api_key`, `gemini.model`, `gemini.timeout_seconds=2.5`.
- `limits` cho request/output; preference allowlist; Category slug groups; complement/time mappings; history/popularity windows.

API key chỉ từ environment/secret store, không từ request hoặc database output. Config lỗi hoặc credential thiếu bind `NullRecommendationProvider`, không làm application boot/Core flow lỗi.

## 11. Error handling và safe logging

- Input/batch không hợp lệ: HTTP `422` với validation message thân thiện.
- Throttle: HTTP `429`; provider timeout không trả `5xx`, mà fallback trong cùng request.
- Empty catalog/fallback: normalized empty result HTTP `200`; Menu vẫn hoạt động.
- Log warning có cấu trúc khi provider fallback: request/correlation ID, provider name, exception class hoặc HTTP status nhóm, latency, fallback reason code, candidate/result count và source cuối.
- Không log API key, prompt/response đầy đủ, request note, Cart/Order note, PII, Customer/User ID, order/session code hoặc provider exception message có thể chứa payload. Không persist prompt/response.
- Metric tối thiểu nếu logging stack hỗ trợ: số request theo source, provider timeout/invalid-output count và duration; không thêm analytics storage V1.

## 12. Database decision

Không cần migration cho V1.

- RecommendationResult không persist; không tạo `recommendations` hoặc `recommendation_logs`.
- Popularity/history derive từ `orders`, `order_items`, `dining_sessions` và `bills` hiện có.
- Catalog dùng `products`, `categories` và media hiện có.
- Context/draft/result chỉ sống trong request/response; Cart tiếp tục ở session.
- Không lưu prompt hoặc provider response. Chỉ cân nhắc aggregate/cache sau khi có số liệu chứng minh query hiện tại không đáp ứng, không thuộc V1.

## 13. Implementation sequence

1. **Contract slice:** internal input DTO, normalized result và service-level validation.
2. **Context slice:** eligible catalog, Cart snapshot, popularity và optional customer aggregate; kiểm chứng query fixture không lộ PII.
3. **Rule-based slice:** deterministic score/balance/quantity/budget/popular fallback; kiểm chứng bằng fixture cố định.
4. **Orchestration slice:** hydrator, final validator và `RecommendationService`; kiểm chứng Product/price/status luôn lấy lại từ DB.
5. **Interface slice:** public form endpoint đã bị loại bỏ; Chat Support interface chưa thuộc tài liệu này.
6. **Cart batch slice:** `addBatch()` single-write + session blocking; kiểm chứng success và all-or-nothing failure.
7. **Provider slice:** contract, null adapter, Gemini adapter và fallback; AI flag vẫn mặc định off.
8. **Operational slice:** config, feature flags, sanitized logging và timeout.
9. **Regression slice:** chạy các test trọng yếu bên dưới cùng Cart/Menu/Customer Ordering tests hiện có.

## 14. Test trọng yếu cho bước code sau

- Internal input normalization và preference allowlist được kiểm tra tại lớp Chat Support gọi tool trong khâu sau.
- Eligible Product/Category only; soft-deleted, inactive hoặc unavailable không bao giờ xuất hiện.
- Rule-based cho kết quả ổn định, cân bằng Category và không ưu tiên lại món trong Cart.
- Quantity, current DB price, estimated total và ba budget status chính xác, kể cả overflow boundary.
- Provider trả ID giả/trùng, quantity sai, metadata/giá giả hoặc schema lỗi đều fallback.
- Gemini timeout/error không retry, không lộ lỗi và trả rule-based/popular result.
- Guest không có history; authenticated Customer chỉ dùng aggregate của chính mình.
- Provider payload và log không chứa PII, order/session code hoặc note cá nhân.
- Add-entire-set thành công và cộng đúng quantity Cart hiện có.
- Một item batch invalid/quá quantity làm rollback toàn bộ; concurrent session request không gây partial Cart.
- Single-item Cart, Cart rows/checkout và Menu hiện tại không bị regression.
