# AI Recommendation — Đặc tả chức năng V1

**Trạng thái:** Capability nội bộ; public form interface đã được thay thế  
**Baseline liên quan:** `FR-AI-*`, `BR-AI-*`, `FR-CART-*`, `BR-CART-*`, `FR-MENU-*`, `BR-PRODUCT-*`, `UC-CUS-08`, `SF-07`, `SYS-DEC-07`, `DEC-12`

## Superseded interface decision — AI Chat Support

Public form recommendation và `POST /recommendations` không còn là interface được hỗ trợ. Các trường `party_size`, `budget`, `preferences` và `note` trong tài liệu này là structured input nội bộ do AI Chat Support tương lai trích xuất từ hội thoại, không phải form/API contract cho browser hoặc client gọi trực tiếp.

`RecommendationService`, rule-based fallback và Gemini provider được giữ làm internal capability. Khách vẫn phải xác nhận rõ ràng trước mọi thay đổi Cart; `POST /cart/items/batch` tiếp tục là action thêm toàn bộ set với validation hiện tại. Mục này chỉ thay thế quyết định interface, chưa phải đặc tả đầy đủ cho chatbot.

## 1. Mục tiêu

- Hỗ trợ khách chọn nhanh một set món phù hợp với quy mô nhóm, ngân sách và nhu cầu đã cung cấp.
- Hỗ trợ cross-sell và upsell hợp lý mà không thay quyền quyết định của khách hàng.
- Recommendation là capability hỗ trợ; không phải bước bắt buộc của Menu, Cart hay quy trình tạo Order.

## 2. Người sử dụng

- Guest và khách đã đăng nhập đều có thể yêu cầu recommendation (`FR-AI-01`).
- Khách đã đăng nhập có thể nhận cá nhân hóa bổ sung từ lịch sử gọi món khi có dữ liệu phù hợp (`FR-AI-06`).
- Việc tạo recommendation không yêu cầu đăng nhập. Các hành động Cart/Order sau đó vẫn tuân theo context và validation hiện có.

## 3. Input

Input có cấu trúc do AI Chat Support tương lai trích xuất từ nhu cầu khách cung cấp trong hội thoại:

| Trường | Bắt buộc | Ý nghĩa |
|---|---|---|
| `party_size` | Có | Số người dùng chung set món. |
| `budget` | Không | Tổng ngân sách cho cả nhóm. |
| `preferences` | Không | Danh sách sở thích hoặc nhu cầu; taxonomy cụ thể chưa thuộc khâu này. |
| `note` | Không | Ghi chú ngắn bổ sung cho yêu cầu. |

Hệ thống có thể bổ sung thời gian hiện tại (`FR-AI-02`), Cart hiện tại (`FR-AI-05`), độ phổ biến sản phẩm (`FR-AI-04`) và lịch sử gọi món phù hợp của khách đăng nhập (`FR-AI-06`). Weather là context tùy chọn (`FR-AI-03`); thiếu hoặc lỗi Weather không làm yêu cầu thất bại.

## 4. Output

Một kết quả recommendation gồm:

- Một set mục tiêu khoảng 3–6 Product hợp lệ.
- Với mỗi Product: định danh Product đã được backend xác thực, số lượng đề xuất và lý do ngắn gọn.
- Mô tả chung ngắn cho set món.
- Tổng tiền dự kiến, được tính lại từ giá Product hiện tại của hệ thống và số lượng đề xuất.
- Trạng thái cho biết kết quả có phù hợp `budget` hay không; khi không có `budget`, trạng thái thể hiện không áp dụng.
- Nguồn kết quả: `AI` hoặc `fallback`.

Khách có thể xem chi tiết Product, chủ động thêm từng món hoặc chủ động thêm toàn bộ set vào Cart. “Thêm toàn bộ” vẫn là các thao tác thêm Product hiện có, không tạo Product combo.

## 5. Luồng nghiệp vụ cấp cao

1. Khách chủ động yêu cầu gợi ý trong hội thoại; lớp Chat Support trích xuất input có cấu trúc cho internal recommendation tool.
2. Hệ thống lấy danh sách Product đủ điều kiện và bổ sung các context khả dụng.
3. Khi AI provider khả dụng, hệ thống có thể yêu cầu AI tạo đề xuất; nếu không, chuyển sang fallback.
4. Backend không tin dữ liệu nghiệp vụ từ AI: kiểm tra lại Product ID, điều kiện phục vụ, giá và số lượng; loại kết quả không hợp lệ.
5. Backend hoàn thiện set hợp lệ, tính tổng tiền và trạng thái ngân sách từ dữ liệu hiện tại rồi trả kết quả cùng nguồn.
6. Khách xem kết quả và tự quyết định xem chi tiết, thêm từng món hoặc thêm toàn bộ set vào Cart (`FR-AI-09`, `FR-AI-12`).
7. Mọi Add-to-Cart đi qua cùng validation của Cart hiện tại; AI không thực hiện hành động thay khách.

## 6. Business boundaries

- **BR-AI-01:** AI chỉ tư vấn; không tự thêm/sửa Cart, tạo Order hoặc thay đổi Product, giá, voucher, payment, stock hay trạng thái nghiệp vụ.
- **BR-AI-02:** Chỉ trả Product chưa bị xóa, đang active, thuộc Category active và đang available (`FR-AI-07`, `FR-MENU-05`, `BR-PRODUCT-04`).
- **BR-AI-03:** Context được phép gồm time, Cart, popularity, lịch sử gọi món phù hợp và Weather tùy chọn; chỉ dùng dữ liệu cần thiết cho recommendation.
- **BR-AI-04:** Lỗi Gemini/AI provider, Weather hoặc recommendation không được làm gián đoạn Menu, Cart, Reservation, Order hoặc Payment (`FR-AI-10`).
- **BR-AI-05:** Product ID do AI trả về phải được backend xác thực. Tên, giá, availability và tổng tiền chỉ lấy hoặc tính từ dữ liệu hiện tại của hệ thống; output AI không phải source of truth.
- **BR-AI-06:** Mọi Add-to-Cart, kể cả thêm toàn bộ set, cần hành động rõ ràng của khách và dùng cùng validation với Cart hiện tại (`BR-CART-04`, `FR-CART-02`, `FR-CART-05`).
- **BR-AI-07:** Không gửi tên, email, số điện thoại, địa chỉ hoặc thông tin định danh khác cho AI provider. Nếu dùng lịch sử gọi món, context gửi đi phải được tối thiểu hóa và không định danh.
- **BR-AI-08:** V1 không cam kết xử lý dị ứng, thành phần, dinh dưỡng hoặc tư vấn sức khỏe.
- **BR-AI-09:** Recommendation không phải Core Database dependency; set món là kết quả tạm thời, không phải Product/combo mới và không phải transaction source of truth.

## 7. Fallback behavior

1. Nếu AI không khả dụng hoặc output không vượt qua validation, dùng recommendation rule-based/context-based (`FR-AI-11`).
2. Nếu vẫn không tạo được set phù hợp, có thể trả danh sách món phổ biến hợp lệ hoặc hướng khách về Menu bình thường.
3. Không hiển thị lỗi kỹ thuật của provider cho khách. Menu phải luôn sử dụng được; các nghiệp vụ Core không thay đổi trạng thái vì lỗi recommendation.

Không đặc tả thuật toán fallback trong tài liệu này.

## 8. In-scope

- Nhận input nghiệp vụ và context tùy chọn đã nêu.
- Sinh một set món tạm thời cho Guest hoặc khách đăng nhập.
- Cá nhân hóa bổ sung bằng lịch sử gọi món khi phù hợp và không lộ danh tính.
- Kiểm tra lại Product, tính tổng tiền hiện tại, đánh dấu mức phù hợp ngân sách và nguồn kết quả.
- Điều hướng xem Product và hành động rõ ràng để thêm từng món/toàn bộ set qua Cart hiện có.
- Fallback có kiểm soát khi AI, Weather hoặc recommendation lỗi.

## 9. Out-of-scope

- Huấn luyện model riêng, recommendation tự học dài hạn, AI analytics nâng cao hoặc dashboard quản trị chuyên sâu.
- Tự động thay đổi Cart/Order, tự tạo Order hoặc tự áp dụng voucher.
- Tư vấn dinh dưỡng, dị ứng, thành phần hoặc sức khỏe.
- Tạo/quản lý combo như một Product độc lập.
- Bắt buộc Weather hoặc làm provider thành dependency của Core.
- Lưu toàn bộ prompt/response chứa dữ liệu khách hàng.
- Đặc tả UI, route, API, class, database, provider, Gemini JSON hoặc thuật toán fallback.

## 10. Giả định đã chốt

- `budget` là tổng ngân sách của cả nhóm, không phải ngân sách trên mỗi người.
- Giá trị tiền và tổng dự kiến dùng cùng đơn vị tiền tệ/quy tắc làm tròn hiện hành của Menu/Cart.
- Recommendation phản ánh dữ liệu tại thời điểm tạo; Add-to-Cart và submit Order vẫn phải validate lại dữ liệu hiện tại.
- Không có `budget` không ngăn tạo recommendation; trạng thái ngân sách khi đó là không áp dụng.
- Có thể trả kết quả ít hơn mục tiêu 3–6 món khi dữ liệu hợp lệ không đủ; không được chèn Product không đủ điều kiện chỉ để đạt số lượng.
- Lịch sử gọi món chỉ là tín hiệu tùy chọn; không có hoặc không phù hợp thì trải nghiệm tương đương Guest.
- Thay đổi so với diễn đạt cũ “rule-based/context-based trước”: V1 cho phép dùng AI khi provider khả dụng và dùng rule/context làm fallback. Context V1 dùng Cart hiện tại, không phụ thuộc Current Order.

## 11. Vấn đề chuyển sang thiết kế backend

1. Chốt validation và giới hạn hợp lệ cho `party_size`, `budget`, số phần tử `preferences`, độ dài `note` và quantity đề xuất.
2. Chốt vocabulary/contract cho trạng thái ngân sách, bao gồm trường hợp không có budget và set vượt budget.
3. Chốt cách lấy, tối thiểu hóa và giới hạn phạm vi lịch sử gọi món trước khi đưa vào recommendation context.
4. Chốt timeout, retry/circuit-breaker và tiêu chí xác định output AI không hợp lệ để chuyển fallback.
5. Chốt xử lý nguyên tử/partial failure khi khách thêm toàn bộ set nhưng một hoặc nhiều Product không còn hợp lệ tại thời điểm Add-to-Cart.
