# 89 Beer Garden --- System Requirements

> **Document:** System Requirements\
> **Project:** 89 Beer Garden Website & Management System\
> **Version:** 1.0\
> **Status:** Final Baseline\
> **Date:** 2026-08-17\
> **Source:** Business Analysis v1.0\
> **Next document:** Use Case Analysis

------------------------------------------------------------------------

# 1. Purpose

Tài liệu này là **single source of truth cho System Requirements** của
dự án 89 Beer Garden.

Nó hợp nhất đầy đủ kết quả của các task:

1.  Functional Requirements.
2.  Non-functional Requirements.
3.  Integration & External Service Requirements.
4.  Role & Permission Requirements.
5.  Requirement Priority & Scope.
6.  Review & chốt System Requirements.

Tài liệu được thiết kế để con người và AI coding agents có thể dùng làm
context trước khi triển khai Use Case, Database, UI/UX, Development và
Testing.

## 1.1 Traceability model

`Business Problem`

→ `Business Need`

→ `Business Flow / Business Rule`

→ `Functional Requirement`

→ `Non-functional Requirement`

→ `Permission / Integration`

→ `Use Case`

→ `Database`

→ `Implementation`

→ `Test Case`

## 1.2 Priority convention

  -----------------------------------------------------------------------
  Priority                            Ý nghĩa
  ----------------------------------- -----------------------------------
  Must                                Core bắt buộc, hoặc requirement
                                      liên quan trực tiếp Business Rule /
                                      Security / Data Integrity

  Should                              Nên có trong phiên bản triển khai
                                      nhưng có thể đơn giản hóa

  Could                               Có giá trị nhưng không ảnh hưởng
                                      Core Business

  Future                              Đã xác định nhưng chủ động không
                                      triển khai trong Core
  -----------------------------------------------------------------------

------------------------------------------------------------------------

# 2. Functional Requirements

## 2.1 FR-AUTH --- Authentication & Account

  ---------------------------------------------------------------------------
  ID             Requirement    Actor           Source         Priority
  -------------- -------------- --------------- -------------- --------------
  FR-AUTH-01     Hệ thống cho   Customer        ACT-01         Should
                 phép Customer                                 
                 đăng ký tài                                   
                 khoản bằng                                    
                 thông tin cơ                                  
                 bản.                                          

  FR-AUTH-02     Hệ thống cho   Customer,       BR-USER        Must
                 phép người     Staff, Kitchen,                
                 dùng có tài    Admin                          
                 khoản đăng                                    
                 nhập bằng                                     
                 thông tin xác                                 
                 thực hợp lệ.                                  

  FR-AUTH-03     Hệ thống cho   Authenticated   BR-USER        Must
                 phép người     Users                          
                 dùng đăng xuất                                
                 khỏi phiên                                    
                 hiện tại.                                     

  FR-AUTH-04     Hệ thống phải  Internal Users  BR-USER-04     Must
                 từ chối đăng                                  
                 nhập đối với                                  
                 tài khoản bị                                  
                 Disabled.                                     

  FR-AUTH-05     Customer có    Customer        ACT-01         Must
                 thể xem Menu                                  
                 mà không cần                                  
                 đăng nhập.                                    

  FR-AUTH-06     Customer đã    Customer        ACT-01         Should
                 đăng nhập có                                  
                 thể xem và cập                                
                 nhật thông tin                                
                 cá nhân cơ                                    
                 bản.                                          

  FR-AUTH-07     Hệ thống hỗ    Users           ---            Should
                 trợ quy trình                                 
                 khôi phục mật                                 
                 khẩu cho tài                                  
                 khoản hợp lệ.                                 
  ---------------------------------------------------------------------------

**Decision:** Authentication không được biến website thành hệ thống bắt
buộc đăng nhập trước khi xem Menu.

------------------------------------------------------------------------

## 2.2 FR-MENU --- Menu & Product

  -----------------------------------------------------------------------------------
  ID             Requirement            Actor          Source          Priority
  -------------- ---------------------- -------------- --------------- --------------
  FR-MENU-01     Customer có thể xem    Customer       BF-03           Must
                 danh sách món với tên,                                
                 hình ảnh, giá, mô tả                                  
                 và trạng thái khả                                     
                 dụng.                                                 

  FR-MENU-02     Customer có thể xem    Customer       BR-PRODUCT      Must
                 món theo Category.                                    

  FR-MENU-03     Customer có thể tìm    Customer       ACT-01          Must
                 kiếm món.                                             

  FR-MENU-04     Customer có thể lọc    Customer       ACT-01          Must
                 món theo Category.                                    

  FR-MENU-05     Product                Customer,      BR-PRODUCT-04   Must
                 Unavailable/Inactive   Staff                          
                 không được thêm vào                                   
                 Order mới.                                            

  FR-MENU-06     Manager/Admin có thể   Admin          BF-06           Must
                 tạo, sửa và thay đổi                                  
                 trạng thái Category.                                  

  FR-MENU-07     Manager/Admin có thể   Admin          BF-06           Must
                 tạo và cập nhật                                       
                 Product.                                              

  FR-MENU-08     Manager/Admin có thể   Admin          BF-06           Must
                 cập nhật giá Product.                                 

  FR-MENU-09     Manager/Admin có thể   Admin          BF-06           Must
                 cập nhật hình ảnh, mô                                 
                 tả và trạng thái khả                                  
                 dụng Product.                                         

  FR-MENU-10     Product đã có lịch sử  Admin          BR-PRODUCT-03   Must
                 Order không được                                      
                 hard-delete; hệ thống                                 
                 phải cho phép chuyển                                  
                 sang Inactive.                                        
  -----------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.2A FR-CART --- Customer Cart

| ID | Requirement | Actor | Source | Priority |
|---|---|---|---|---|
| FR-CART-01 | Customer có thể thêm Product Available vào Cart từ Menu hoặc Product Detail. | Customer | BR-CART-01 | Must |
| FR-CART-02 | Customer có thể chủ động thêm Product từ Recommendation vào Cart; AI không được tự thêm. | Customer | BR-CART-04 | Should |
| FR-CART-03 | Customer có thể xem, đổi Quantity, thêm Note và xóa Item khỏi Cart trước khi submit. | Customer | Customer Ordering | Must |
| FR-CART-04 | Cart là dữ liệu tạm theo browser/session và không phải transaction source of truth. | System | BR-CART-01 | Must |
| FR-CART-05 | Khi submit, backend phải validate Dining Session, Product, availability, quantity và current price. | System | BR-CART-02..03 | Must |
| FR-CART-06 | Cart chỉ được chuyển thành Order trong Dining Session hợp lệ được xác định cho Customer/table context. | System | DEC-15 | Must |
| FR-CART-07 | Cart không snapshot historical price; `OrderItem.unit_price` chỉ được snapshot khi Order được tạo. | System | BR-CART-03 | Must |
| FR-CART-08 | Cart/input phải được giữ khi submit thất bại có thể phục hồi. | System | NFR-UX | Should |

**Decision:** Core Cart dùng session/application state; không yêu cầu
database table riêng. Takeaway/Delivery checkout không thuộc Core.

------------------------------------------------------------------------

## 2.3 FR-TABLE --- Table Management

  -----------------------------------------------------------------------------------------------------------
  ID             Requirement                                     Actor          Source         Priority
  -------------- ----------------------------------------------- -------------- -------------- --------------
  FR-TABLE-01    Hệ thống hiển thị danh sách bàn và trạng thái   Staff, Admin   BF-01          Must
                 hiện tại của từng bàn.                                                        

  FR-TABLE-02    Hệ thống hỗ trợ                                 Staff          BR-TABLE-01    Must
                 `Available / Reserved / Occupied / Cleaning`.                                 

  FR-TABLE-03    Staff có thể bố trí một bàn Available cho khách Staff          BF-01          Must
                 walk-in.                                                                      

  FR-TABLE-04    Hệ thống lưu sức chứa của từng bàn.             Staff, Admin   BR-TABLE-03    Must

  FR-TABLE-05    Hệ thống hỗ trợ Staff lựa chọn bàn phù hợp với  Staff          BR-TABLE-03    Must
                 số khách.                                                                     

  FR-TABLE-06    Hệ thống không cho phép một bàn có hai Dining   System         BR-TABLE-04    Must
                 Session active cùng lúc.                                                      

  FR-TABLE-07    Manager/Admin có thể tạo, sửa và thay đổi trạng Admin          Table Need     Must
                 thái hoạt động của bàn.                                                       

  FR-TABLE-08    Sau khi phiên phục vụ kết thúc, Staff có thể    Staff          BF-05          Must
                 chuyển bàn từ Cleaning về Available.                                          
  -----------------------------------------------------------------------------------------------------------

**State decision:** `restaurant_tables.runtime_status` là source of
truth vận hành cho `available/reserved/occupied/cleaning` và được cập
nhật trong cùng transaction với Reservation/Dining Session liên quan.
Khả năng sử dụng lâu dài của bàn được tách bằng `is_active`; bàn inactive
không tham gia assignment.

------------------------------------------------------------------------

## 2.4 FR-RES --- Reservation

  ----------------------------------------------------------------------------------------------------------------------------------------------
  ID             Requirement                                                                        Actor          Source         Priority
  -------------- ---------------------------------------------------------------------------------- -------------- -------------- --------------
  FR-RES-01      Customer có thể gửi yêu cầu đặt bàn trên website.                                  Customer       BF-02          Must

  FR-RES-02      Reservation phải ghi nhận tối thiểu họ tên, SĐT, ngày, giờ và số người.            Customer       BR-RES-01      Must

  FR-RES-03      Customer có thể nhập ghi chú cho Reservation.                                      Customer       BF-02          Should

  FR-RES-04      Reservation mới được tạo với trạng thái Pending.                                   System         BF-02          Must

  FR-RES-05      Staff có thể xem danh sách Reservation.                                            Staff          Reservation    Must
                                                                                                                   Need           

  FR-RES-06      Staff có thể xác nhận hoặc từ chối Reservation.                                    Staff          BF-02          Must

  FR-RES-07      Hệ thống hỗ trợ                                                                    Staff          BR-RES-02      Must
                 `Pending / Confirmed / Checked-in / Completed / Cancelled / Rejected / No-show`.                                 

  FR-RES-08      Hệ thống phải ngăn hoặc cảnh báo việc xác nhận Reservation khi không còn khả năng  Staff          BR-RES-03      Must
                 bố trí phù hợp.                                                                                                  

  FR-RES-09      Staff có thể Check-in Reservation khi Customer đến.                                Staff          BR-RES-04      Must

  FR-RES-10      Khi Check-in, Staff có thể bố trí bàn cho Customer.                                Staff          BF-02          Must

  FR-RES-11      Staff có thể đánh dấu Reservation là No-show.                                      Staff          BR-RES-05      Should

  FR-RES-12      Thời gian cho phép trước khi đánh dấu No-show phải có khả năng cấu hình.           Admin          BR-RES-05      Should
  ----------------------------------------------------------------------------------------------------------------------------------------------

**Decision:** Customer không bắt buộc chọn chính xác số bàn khi đặt bàn.

------------------------------------------------------------------------

## 2.5 FR-SESSION --- Dining Session

  ---------------------------------------------------------------------------
  ID              Requirement    Actor          Source         Priority
  --------------- -------------- -------------- -------------- --------------
  FR-SESSION-01   Hệ thống phải  Staff          DEC-02         Must
                  hỗ trợ một                                   
                  phiên phục vụ                                
                  gắn với bàn từ                               
                  lúc khách nhận                               
                  bàn đến khi                                  
                  hoàn tất thanh                               
                  toán.                                        

  FR-SESSION-02   Một Dining     System         Dining Session Must
                  Session phải                                 
                  gắn với ít                                   
                  nhất một bàn                                 
                  đang phục vụ.                                

  FR-SESSION-03   Một Dining     System         BR-ORDER-04    Must
                  Session có thể                               
                  chứa nhiều lần                               
                  gọi món /                                    
                  nhiều Order.                                 

  FR-SESSION-04   Hệ thống phải  System         Dining Session Must
                  lưu thời điểm                                
                  bắt đầu và kết                               
                  thúc Dining                                  
                  Session.                                     

  FR-SESSION-05   Hệ thống không System         BR-TABLE-04    Must
                  được mở Dining                               
                  Session thứ                                  
                  hai trên cùng                                
                  bàn khi phiên                                
                  hiện tại chưa                                
                  kết thúc.                                    

  FR-SESSION-06   Khi payment    System         BR-PAY-05      Must
                  hoàn tất,                                    
                  Dining Session                               
                  được chuyển                                  
                  sang                                         
                  Completed.                                   
  ---------------------------------------------------------------------------

**Critical:** Không implement theo assumption `1 Table = 1 Order`.

------------------------------------------------------------------------

## 2.6 FR-ORDER --- Ordering & POS

  ----------------------------------------------------------------------------
  ID             Requirement      Actor          Source         Priority
  -------------- ---------------- -------------- -------------- --------------
  FR-ORDER-01    Staff có thể tạo Staff          BF-03          Must
                 Order cho Dining                               
                 Session đang                                   
                 hoạt động.                                     

  FR-ORDER-02    Customer có thể  Customer       BF-03          Must
                 submit Cart để                                 
                 tạo Order trong                                
                 Dining Session                                 
                 hợp lệ nếu                                     
                 Customer Ordering                              
                 được bật.                                      

  FR-ORDER-03    Một Order phải   System         BR-ORDER-01    Must
                 có ít nhất một                                 
                 Order Item hợp                                 
                 lệ trước khi                                   
                 được xác nhận.                                 

  FR-ORDER-04    Hệ thống phải    System         BR-ORDER-02    Must
                 validate trạng                                 
                 thái Product                                   
                 trước khi thêm                                 
                 vào Order.                                     

  FR-ORDER-05    Staff có thể     Staff          BF-03          Must
                 thêm nhiều                                     
                 Product vào                                    
                 Order.                                         

  FR-ORDER-06    Staff có thể     Staff          BF-03          Must
                 thay đổi                                       
                 Quantity trước                                 
                 khi món đi vào                                 
                 trạng thái không                               
                 cho phép sửa.                                  

  FR-ORDER-07    Hệ thống cho     Staff,         BR-ORDER-05    Must
                 phép nhập Note   Customer                      
                 riêng cho từng                                 
                 Order Item.                                    

  FR-ORDER-08    Hệ thống phải    System         BR-ORDER-03    Must
                 lưu Unit Price                                 
                 tại thời điểm                                  
                 Order Item được                                
                 tạo.                                           

  FR-ORDER-09    Thay đổi Product System         BR-ORDER-03    Must
                 Price sau đó                                   
                 không được làm                                 
                 thay đổi Order                                 
                 Item lịch sử.                                  

  FR-ORDER-10    Customer/Staff   Customer,      BR-ORDER-04    Must
                 có thể gọi thêm  Staff                         
                 món khi Dining                                 
                 Session vẫn                                    
                 active.                                        

  FR-ORDER-11    Các lần gọi thêm System         DEC-02         Must
                 phải được liên                                 
                 kết với Dining                                 
                 Session hiện                                   
                 tại.                                           

  FR-ORDER-12    Staff có thể xem Staff          BF-05          Must
                 toàn bộ món đã                                 
                 phát sinh trong                                
                 Dining Session.                                

  FR-ORDER-13    Hệ thống lưu     System         Current        Must
                 người tạo Order                 Business       
                 và thời điểm tạo                               
                 khi thông tin                                  
                 này có sẵn.                                    

  FR-ORDER-14    Hệ thống phải    Admin          Reporting Need Must
                 giữ lịch sử                                    
                 Order đã hoàn                                  
                 thành.                                         

  FR-ORDER-15    Order phải lưu   System         DEC-15         Must
                 nguồn tạo là Staff                             
                 hoặc Customer;                                 
                 employee/customer                              
                 reference được lưu                             
                 khi actor tương ứng                            
                 tồn tại.                                       

  FR-ORDER-16    Customer self-   System         PERM-CUSTOMER  Must
                 order chỉ được                                 
                 thao tác trên                                  
                 Dining Session                                 
                 context đã được                                
                 cấp hợp lệ.                                    
  ----------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.7 FR-KITCHEN --- Kitchen / Bar Processing

  --------------------------------------------------------------------------------------
  ID              Requirement              Actor          Source          Priority
  --------------- ------------------------ -------------- --------------- --------------
  FR-KITCHEN-01   Kitchen/Bar Staff có thể Kitchen        BF-04           Must
                  xem danh sách Order Item                                
                  đang chờ xử lý.                                         

  FR-KITCHEN-02   Hệ thống hiển thị        Kitchen        ACT-03          Must
                  Product, Quantity, Note                                 
                  và bàn/order liên quan.                                 

  FR-KITCHEN-03   Kitchen/Bar Staff có thể Kitchen        BF-04           Must
                  cập nhật                                                
                  `Waiting → Preparing`.                                  

  FR-KITCHEN-04   Kitchen/Bar Staff có thể Kitchen        BF-04           Must
                  cập nhật                                                
                  `Preparing → Ready`.                                    

  FR-KITCHEN-05   Staff có thể cập nhật    Staff          BF-04           Must
                  `Ready → Served`.                                       

  FR-KITCHEN-06   Hệ thống quản lý trạng   System         DEC-07          Must
                  thái ở mức Order Item,                                  
                  không chỉ toàn bộ Order.                                

  FR-KITCHEN-07   Hệ thống hỗ trợ hủy Item Staff          BR-KITCHEN-04   Should
                  ở trạng thái Waiting                                    
                  theo quyền được cấp.                                    

  FR-KITCHEN-08   Hủy Item đang Preparing  Staff, Admin   BR-KITCHEN-04   Should
                  phải yêu cầu quyền phù                                  
                  hợp.                                                    

  FR-KITCHEN-09   Item đã Served không     System         BR-KITCHEN-04   Must
                  được xóa trực tiếp khỏi                                 
                  lịch sử.                                                
  --------------------------------------------------------------------------------------

Cancellation requirements:

| ID | Requirement | Priority |
|---|---|---|
| FR-KITCHEN-10 | Cancel Waiting Item chuyển status sang `cancelled`; Staff/Manager/Admin phải có permission tương ứng. | Should |
| FR-KITCHEN-11 | Cancel Preparing Item chỉ dành cho Manager/Admin trong Core. | Should |
| FR-KITCHEN-12 | Mọi cancellation phải lưu actor, thời điểm và reason; không hard-delete Order Item. | Must |

------------------------------------------------------------------------

## 2.8 FR-PAY --- Billing & Payment

  -------------------------------------------------------------------------------------------
  ID             Requirement                     Actor          Source         Priority
  -------------- ------------------------------- -------------- -------------- --------------
  FR-PAY-01      Staff có thể mở thông tin thanh Staff          BF-05          Must
                 toán của Dining Session đang                                  
                 hoạt động.                                                    

  FR-PAY-02      Hệ thống tự động tổng hợp tất   System         BF-05          Must
                 cả Order Item hợp lệ trong                                    
                 phiên.                                                        

  FR-PAY-03      Hệ thống tự động tính Subtotal. System         BR-PAY-02      Must

  FR-PAY-04      Hệ thống áp dụng                System         BR-VOUCHER     Must
                 Discount/Voucher hợp lệ trước                                 
                 khi tính Total.                                               

  FR-PAY-05      Hệ thống tính                   System         BR-PAY-02      Must
                 `Total = Subtotal − Discount`                                 
                 cho Core.                                                     

  FR-PAY-06      Staff có thể ghi nhận phương    Staff          DEC-09         Must
                 thức thanh toán Cash.                                         

  FR-PAY-07      Staff có thể ghi nhận thanh     Staff          DEC-09         Must
                 toán bằng QR/Bank Transfer sau                                
                 khi xác nhận đã nhận tiền.                                    

  FR-PAY-08      Hệ thống phải ngăn một Payment  System         BR-PAY-03      Must
                 thành công bị ghi nhận thành                                  
                 công lần thứ hai.                                             

  FR-PAY-09      Sau khi Payment Paid,           System         BR-PAY-04      Must
                 Order/Dining Session không được                               
                 chỉnh sửa theo luồng thông                                    
                 thường.                                                       

  FR-PAY-10      Hệ thống tạo và lưu             Staff          BF-05          Must
                 Invoice/Bill sau khi thanh toán                               
                 hoàn tất.                                                     

  FR-PAY-11      Sau khi thanh toán hoàn tất, hệ System         BR-PAY-05      Must
                 thống đóng Dining Session và                                  
                 chuyển bàn sang Cleaning.                                     
  -------------------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.9 FR-VOUCHER --- Voucher

  ------------------------------------------------------------------------------
  ID              Requirement      Actor          Source          Priority
  --------------- ---------------- -------------- --------------- --------------
  FR-VOUCHER-01   Manager/Admin có Admin          Voucher Flow    Should
                  thể tạo và chỉnh                                
                  sửa Voucher.                                    

  FR-VOUCHER-02   Voucher có Code, Admin          Voucher         Should
                  Discount                        Business        
                  Value/Type,                                     
                  Valid Period,                                   
                  Minimum Order,                                  
                  Usage Limit và                                  
                  Status.                                         

  FR-VOUCHER-03   Customer/Staff   Customer,      BR-VOUCHER      Should
                  có thể nhập      Staff                          
                  Voucher khi                                     
                  thanh toán.                                     

  FR-VOUCHER-04   Hệ thống phải    System         BR-VOUCHER-01   Should
                  validate hiệu                                   
                  lực và điều kiện                                
                  Voucher.                                        

  FR-VOUCHER-05   Core chỉ cho     System         BR-VOUCHER-03   Should
                  phép tối đa một                                 
                  Voucher trên một                                
                  Invoice.                                        

  FR-VOUCHER-06   Discount không   System         BR-VOUCHER-04   Must
                  được làm Total                                  
                  nhỏ hơn 0.                                      
  ------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.10 FR-INV --- Inventory

  -----------------------------------------------------------------------------------
  ID             Requirement             Actor          Source         Priority
  -------------- ----------------------- -------------- -------------- --------------
  FR-INV-01      Manager/Admin có thể    Admin          Inventory Need Should
                 xem tồn kho hiện tại.                                 

  FR-INV-02      Manager/Admin có thể    Admin          BF-07          Should
                 tạo giao dịch nhập kho.                               

  FR-INV-03      Manager/Admin có thể    Admin          BF-07          Should
                 tạo giao dịch xuất/điều                               
                 chỉnh kho.                                            

  FR-INV-04      Mỗi biến động kho phải  System         BR-INV-01      Should
                 lưu loại giao dịch, số                                
                 lượng, thời gian và                                   
                 người thao tác khi có.                                

  FR-INV-05      Hệ thống không cho phép System         BR-INV-03      Should
                 xuất vượt quá tồn kho                                 
                 trong Core.                                           

  FR-INV-06      Manager/Admin có thể    Admin          BR-INV-04      Should
                 thiết lập Minimum                                     
                 Stock.                                                

  FR-INV-07      Hệ thống hiển thị cảnh  Admin          BR-INV-04      Should
                 báo khi Stock ≤ Minimum                               
                 Stock.                                                

  FR-INV-08      Hệ thống giữ lịch sử    Admin          BR-INV-02      Should
                 nhập/xuất/adjustment.                                 

  FR-INV-09      Không triển khai tự     System Scope   DEC-08         Future
                 động trừ ingredient                                   
                 theo Recipe/BOM trong                                 
                 Core.                                                 
  -----------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.11 FR-CUSTOMER --- Customer Management

  -----------------------------------------------------------------------------------
  ID               Requirement     Actor          Source               Priority
  ---------------- --------------- -------------- -------------------- --------------
  FR-CUSTOMER-01   Manager/Admin   Admin          ACT-04               Must
                   có thể xem danh                                     
                   sách Customer                                       
                   đã được lưu                                         
                   trong hệ thống.                                     

  FR-CUSTOMER-02   Hệ thống có thể System         Customer Need        Must
                   lưu thông tin                                       
                   cơ bản của                                          
                   Customer.                                           

  FR-CUSTOMER-03   Customer đã     Customer       ACT-01               Should
                   đăng nhập có                                        
                   thể xem lịch sử                                     
                   order của mình.                                     

  FR-CUSTOMER-04   Manager/Admin   Admin          Reporting/Customer   Should
                   có thể xem lịch                                     
                   sử order liên                                       
                   quan đến                                            
                   Customer khi có                                     
                   dữ liệu.                                            

  FR-CUSTOMER-05   Hệ thống có thể Admin          Customer Need        Could
                   thống kê số đơn                                     
                   và tổng chi                                         
                   tiêu của                                             
                   Customer từ dữ                                       
                   liệu lịch sử.                                       

  FR-CUSTOMER-06   Customer Account System         DEC-16               Should
                   phải liên kết                                        
                   tối đa một Customer                                  
                   business profile                                     
                   để enforce ownership.                                

  FR-CUSTOMER-07   Customer đã     Customer       PERM-CUSTOMER        Should
                   đăng nhập chỉ                                       
                   được xem own                                        
                   Profile, Orders và                                  
                   Reservations.                                       
  -----------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.12 FR-EMP --- Employee Management

  -----------------------------------------------------------------------------
  ID             Requirement       Actor          Source         Priority
  -------------- ----------------- -------------- -------------- --------------
  FR-EMP-01      Manager/Admin có  Admin          BR-USER        Must
                 thể tạo tài khoản                               
                 nhân viên.                                      

  FR-EMP-02      Manager/Admin có  Admin          ACT-04         Must
                 thể cập nhật                                    
                 thông tin nhân                                  
                 viên.                                           

  FR-EMP-03      Manager/Admin có  Admin          BR-USER-01     Must
                 thể gán                                         
                 Role/Permission                                 
                 cho nhân viên.                                  

  FR-EMP-04      Manager/Admin có  Admin          BR-USER-04     Must
                 thể Disable tài                                 
                 khoản nhân viên.                                

  FR-EMP-05      Disable tài khoản System         BR-USER-04     Must
                 không được làm                                  
                 mất lịch sử                                     
                 nghiệp vụ mà nhân                               
                 viên đã thực                                    
                 hiện.                                           
  -----------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.13 FR-REPORT --- Reporting

  ---------------------------------------------------------------------------
  ID             Requirement     Actor          Source         Priority
  -------------- --------------- -------------- -------------- --------------
  FR-REPORT-01   Manager/Admin   Admin          Reporting Need Must
                 có thể xem tổng                               
                 doanh thu.                                    

  FR-REPORT-02   Manager/Admin   Admin          Reporting      Must
                 có thể xem                                    
                 doanh thu theo                                
                 khoảng thời                                   
                 gian.                                         

  FR-REPORT-03   Manager/Admin   Admin          Reporting      Must
                 có thể xem số                                 
                 lượng Order đã                                
                 hoàn thành.                                   

  FR-REPORT-04   Hệ thống có thể Admin          Reporting      Should
                 tính Average                                  
                 Order Value.                                  

  FR-REPORT-05   Manager/Admin   Admin          Reporting      Must
                 có thể xem danh                               
                 sách sản phẩm                                 
                 bán chạy.                                     

  FR-REPORT-06   Báo cáo phải    System         Problem → Need Must
                 dựa trên dữ                                   
                 liệu                                          
                 Order/Payment                                 
                 đã được lưu                                   
                 trong hệ thống.                               
  ---------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.14 FR-LANG --- Multilingual

  ---------------------------------------------------------------------------
  ID             Requirement     Actor          Source         Priority
  -------------- --------------- -------------- -------------- --------------
  FR-LANG-01     Website hỗ trợ  Customer       DEC-10         Must
                 `VI`, `EN` và                                 
                 `ZH`.                                         

  FR-LANG-02     `VI` là ngôn    System         BR-LANG-01     Must
                 ngữ mặc định.                                 

  FR-LANG-03     Customer có thể Customer       ACT-01         Must
                 chuyển ngôn ngữ                               
                 từ giao diện                                  
                 website.                                      

  FR-LANG-04     Hệ thống hiển   System         BR-LANG        Must
                 thị bản dịch                                  
                 tương ứng với                                 
                 ngôn ngữ đã                                   
                 chọn khi có.                                  

  FR-LANG-05     Khi không tồn   System         BR-LANG-02     Must
                 tại translation                               
                 EN/ZH, hệ thống                               
                 fallback về VI.                               

  FR-LANG-06     Translation     System         BR-LANG-03     Must
                 được tạo tự                                    
                 động phải được                                
                 lưu persistent;                               
                 runtime cache có                              
                 thể dùng để tối                               
                 ưu đọc.                                       

  FR-LANG-07     Manager/Admin   Admin          BR-LANG-04     Should
                 có thể chỉnh                                  
                 sửa thủ công                                  
                 nội dung                                      
                 translation đã                                
                 lưu; bản chỉnh                                
                 tay là source of                              
                 truth ưu tiên.                                

  FR-LANG-08     Việc            System         Language Need  Must
                 Translation                                   
                 Service không                                 
                 hoạt động không                               
                 được làm mất                                  
                 nội dung VI                                   
                 gốc.                                          
  ---------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.15 FR-AI --- AI Recommendation

  --------------------------------------------------------------------------------------
  ID             Requirement                Actor          Source         Priority
  -------------- -------------------------- -------------- -------------- --------------
  FR-AI-01       Hệ thống có thể hiển thị   Customer       AI Need        Should
                 danh sách món được đề xuất                               
                 cho Customer.                                            

  FR-AI-02       Recommendation có thể sử   System         BR-AI-03       Should
                 dụng thời gian hiện tại                                  
                 làm context.                                             

  FR-AI-03       Recommendation có thể sử   System         BR-AI-03       Should
                 dụng dữ liệu thời tiết khi                               
                 Weather Service khả dụng.                                

  FR-AI-04       Recommendation có thể sử   System         BR-AI-03       Should
                 dụng độ phổ biến của                                     
                 Product.                                                 

  FR-AI-05       Recommendation có thể sử   System         BR-AI-03       Should
                 dụng Current Cart/Order                                  
                 context.                                                 

  FR-AI-06       Recommendation có thể sử   System         BR-AI-03       Could
                 dụng Order History nếu                                   
                 Customer có dữ liệu phù                                  
                 hợp.                                                     

  FR-AI-07       Hệ thống không được gợi ý  System         BR-AI-02       Must
                 Product Unavailable.                                     

  FR-AI-08       Recommendation không được  System         BR-AI-01       Must
                 tự động thêm Product vào                                 
                 Cart/Order.                                              

  FR-AI-09       Customer phải là người     Customer       BR-AI-01       Must
                 quyết định có chọn món                                   
                 được recommend hay không.                                

  FR-AI-10       Khi Recommendation Service System         BR-AI-04       Must
                 lỗi, Menu, Reservation,                                  
                 Order và Payment vẫn phải                                 
                 hoạt động.                                               

  FR-AI-11       Core cho phép triển khai   System Scope   DEC-12         Should
                 Recommendation bằng                                      
                 Rule-based/Context-based                                 
                 mà không yêu cầu ML.                                     

  FR-AI-12       Customer có thể chủ động   Customer       BR-CART-04     Should
                 Add-to-Cart từ kết quả                                   
                 Recommendation; action                                   
                 này phải dùng cùng Cart                                  
                 validation như Menu.                                     
  --------------------------------------------------------------------------------------

------------------------------------------------------------------------

## 2.15A FR-CONFIG --- System Configuration

| ID | Requirement | Actor | Source | Priority |
|---|---|---|---|---|
| FR-CONFIG-01 | Admin có thể quản lý các runtime setting được cho phép, gồm no-show timeout. | Admin | FR-RES-12 | Should |
| FR-CONFIG-02 | Runtime setting do Admin chỉnh phải được lưu persistent và validate theo type/range. | System | Scope Alignment | Must |
| FR-CONFIG-03 | Secret/API credential không được lưu qua Admin setting UI; tiếp tục thuộc environment configuration. | System | NFR-SEC-09 | Must |
| FR-CONFIG-04 | Thay đổi setting phải lưu người cập nhật và thời gian cập nhật. | System | NFR-AUDIT | Should |

------------------------------------------------------------------------

# 3. Non-functional Requirements

## 3.1 NFR-PERF --- Performance

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-PERF-01             Các trang chính như     Must
                          Home, Menu và           
                          Reservation phải tải    
                          trong thời gian hợp lý  
                          trên kết nối mạng phổ   
                          thông.                  

  NFR-PERF-02             Các thao tác POS cơ bản Must
                          như mở bàn, thêm món,   
                          cập nhật order và thanh 
                          toán phải phản hồi      
                          nhanh đủ để Staff vận   
                          hành tại quán.          

  NFR-PERF-03             Hệ thống phải hạn chế   Must
                          truy vấn database lặp   
                          lại không cần thiết.    

  NFR-PERF-04             Hình ảnh Product phải   Should
                          được tối ưu để không    
                          làm chậm Menu.          

  NFR-PERF-05             Translation đã có phải  Must
                          được tái sử dụng/cache  
                          thay vì gọi Translation 
                          Service ở mọi request.  

  NFR-PERF-06             Recommendation không    Must
                          được chặn việc tải Menu 
                          nếu AI hoặc Weather     
                          Service chậm.           
  -----------------------------------------------------------------------

Chưa chốt metric cứng kiểu `< 1.5s`; metric cụ thể sẽ được benchmark ở
Testing/Deployment.

------------------------------------------------------------------------

## 3.2 NFR-SEC --- Security

  -------------------------------------------------------------------------
  ID                      Requirement               Priority
  ----------------------- ------------------------- -----------------------
  NFR-SEC-01              Password phải được lưu    Must
                          bằng cơ chế hash an toàn, 
                          không lưu plaintext.      

  NFR-SEC-02              Hệ thống phải kiểm tra    Must
                          Authentication trước khi  
                          cho truy cập chức năng    
                          nội bộ.                   

  NFR-SEC-03              Authorization phải dựa    Must
                          trên Role/Permission.     

  NFR-SEC-04              User không được truy cập  Must
                          chức năng ngoài quyền chỉ 
                          bằng cách thay đổi        
                          URL/request.              

  NFR-SEC-05              Input từ người dùng phải  Must
                          được validate trước khi   
                          xử lý/lưu.                

  NFR-SEC-06              Hệ thống phải có cơ chế   Must
                          bảo vệ request thay đổi   
                          dữ liệu khỏi CSRF phù hợp 
                          với                       
                          architecture/framework.   

  NFR-SEC-07              Database access phải      Must
                          tránh SQL Injection.      

  NFR-SEC-08              Nội dung hiển thị từ user Must
                          input phải được xử lý để  
                          hạn chế XSS.              

  NFR-SEC-09              API Key/secret không được Must
                          hard-code trong source    
                          code public.              

  NFR-SEC-10              Tài khoản Disabled phải   Must
                          mất quyền đăng nhập ngay  
                          sau khi trạng thái được   
                          áp dụng.                  
  -------------------------------------------------------------------------

------------------------------------------------------------------------

## 3.3 NFR-UX --- Usability & Responsive

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-UX-01               Customer Website phải   Must
                          responsive trên         
                          Desktop, Tablet và      
                          Mobile.                 

  NFR-UX-02               Menu phải dễ đọc và     Must
                          thao tác trên điện      
                          thoại.                  

  NFR-UX-03               Chức năng chuyển ngôn   Should
                          ngữ phải dễ tìm và      
                          không làm mất context   
                          trang hiện tại nếu có   
                          thể.                    

  NFR-UX-04               POS phải ưu tiên thao   Must
                          tác nhanh và ít bước    
                          cho nghiệp vụ thường    
                          xuyên.                  

  NFR-UX-05               Trạng thái bàn phải dễ  Must
                          phân biệt trực quan.    

  NFR-UX-06               Trạng thái Order Item   Must
                          phải dễ phân biệt cho   
                          Staff/Kitchen.          

  NFR-UX-07               Action quan trọng như   Should
                          Payment Complete,       
                          Cancel Item hoặc        
                          Disable Account phải có 
                          confirmation phù hợp.   

  NFR-UX-08               Lỗi validation phải     Must
                          hiển thị dễ hiểu và gần 
                          vị trí gây lỗi.         
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 3.4 NFR-REL --- Reliability & Availability

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-REL-01              Các nghiệp vụ Core phải Must
                          hoạt động độc lập với   
                          AI Recommendation.      

  NFR-REL-02              Nếu Weather Service     Must
                          không khả dụng,         
                          Recommendation phải     
                          fallback sang context   
                          còn lại hoặc không hiển 
                          thị recommendation.     

  NFR-REL-03              Nếu Translation Service Must
                          lỗi, nội dung VI gốc    
                          vẫn phải hiển thị.      

  NFR-REL-04              Hệ thống không được tạo Must
                          Payment thành công      
                          trùng lặp do            
                          double-click hoặc retry 
                          request.                

  NFR-REL-05              Các thao tác quan trọng Must
                          phải tránh trạng thái   
                          dữ liệu nửa chừng.      

  NFR-REL-06              Hệ thống phải giữ lịch  Must
                          sử Order và Payment đã  
                          hoàn tất ngay cả khi    
                          Product/Employee sau đó 
                          Inactive/Disabled.      
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 3.5 NFR-DATA --- Data Integrity

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-DATA-01             Một Table không được có Must
                          nhiều Dining Session    
                          active đồng thời.       

  NFR-DATA-02             Order Item phải giữ     Must
                          Unit Price tại thời     
                          điểm tạo.               

  NFR-DATA-03             Thay đổi Product Price  Must
                          không được làm thay đổi 
                          dữ liệu lịch sử.        

  NFR-DATA-04             Product đã tham gia     Must
                          Order không được        
                          hard-delete nếu làm mất 
                          referential history.    

  NFR-DATA-05             Employee đã có lịch sử  Must
                          thao tác không được xóa 
                          theo cách làm mất       
                          audit/history.          

  NFR-DATA-06             Inventory transaction   Should
                          đã hoàn tất phải được   
                          giữ lịch sử; điều chỉnh 
                          sai lệch bằng           
                          Adjustment.             

  NFR-DATA-07             Tổng tiền Invoice phải  Must
                          được tính từ Order Item 
                          và Discount hợp lệ,     
                          không phụ thuộc Product 
                          Price hiện tại.         

  NFR-DATA-08             Relationship quan trọng Must
                          phải có constraint phù  
                          hợp ở                   
                          database/application    
                          level.                  
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 3.6 NFR-MAIN --- Maintainability

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-MAIN-01             Source code phải được   Must
                          tổ chức theo            
                          module/domain rõ ràng.  

  NFR-MAIN-02             Business Logic quan     Must
                          trọng không nên nằm     
                          trực tiếp trong         
                          View/UI.                

  NFR-MAIN-03             Translation, Weather và Should
                          Payment provider nên    
                          được đóng gói thành     
                          service riêng.          

  NFR-MAIN-04             Trạng thái và Business  Must
                          Rule quan trọng không   
                          được hard-code rải rác  
                          nhiều nơi.              

  NFR-MAIN-05             No-show timeout,        Must
                          language, API           
                          credentials và config   
                          tương tự phải được quản 
                          lý bằng configuration   
                          phù hợp.                

  NFR-MAIN-06             Code phải đủ rõ để      Should
                          developer/AI trace      
                          implementation về       
                          Requirement/Business    
                          Rule liên quan.         

  NFR-MAIN-07             Future Scope không được Must
                          trộn vào Core theo cách 
                          làm tăng dependency     
                          không cần thiết.        
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 3.7 NFR-I18N --- Localization

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-I18N-01             Hệ thống phải hỗ trợ    Must
                          Unicode đầy đủ cho VI,  
                          EN và ZH.               

  NFR-I18N-02             Nội dung UI cố định     Must
                          phải dùng localization  
                          resource riêng, không   
                          phụ thuộc hoàn toàn     
                          machine translation.    

  NFR-I18N-03             Nội dung động chưa có   Should
                          translation có thể sử   
                          dụng Translation        
                          Service.                

  NFR-I18N-04             VI luôn là fallback an  Must
                          toàn.                   

  NFR-I18N-05             Thêm language mới trong Should
                          tương lai không nên yêu 
                          cầu sửa toàn bộ source  
                          code.                   

  NFR-I18N-06             Translation cache/store Should
                          phải phân biệt theo     
                          locale và nội dung      
                          nguồn.                  
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 3.8 NFR-AUDIT --- Auditability

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  NFR-AUDIT-01            Order cần lưu thời điểm Should
                          tạo và người tạo khi    
                          có.                     

  NFR-AUDIT-02            Payment phải lưu thời   Must
                          điểm và actor xác nhận. 

  NFR-AUDIT-03            Inventory transaction   Should
                          phải lưu người thao tác 
                          và thời gian khi có.    

  NFR-AUDIT-04            Disable Employee không  Must
                          được xóa lịch sử thao   
                          tác cũ.                 

  NFR-AUDIT-05            Cancel món/payment      Should
                          adjustment sau này nên  
                          có khả năng truy vết    
                          actor thực hiện.        
  -----------------------------------------------------------------------

------------------------------------------------------------------------

# 4. Integration & External Service Requirements

## 4.1 INT-TRANS --- Translation Service

### Purpose

Translation Service hỗ trợ nội dung động như:

-   Product name.
-   Product description.
-   Dynamic content do Admin nhập.

Static UI dùng localization resource riêng.

  ----------------------------------------------------------------------------
  ID                      Requirement                  Priority
  ----------------------- ---------------------------- -----------------------
  INT-TRANS-01            Hệ thống có thể tích hợp     Must
                          Translation Service để dịch  
                          nội dung động từ VI sang     
                          EN/ZH.                       

  INT-TRANS-02            Translation Service phải     Must
                          được gọi thông qua service   
                          abstraction riêng.           

  INT-TRANS-03            Nội dung đã dịch phải có khả Must
                          năng lưu persistent; cache   
                          chỉ là optimization có thể   
                          tái tạo.                     

  INT-TRANS-04            Hệ thống phải kiểm tra       Must
                          translation đã tồn tại trước 
                          khi gọi API ngoài.           

  INT-TRANS-05            Nếu Translation Service      Must
                          lỗi/timeout, hệ thống        
                          fallback về VI.              

  INT-TRANS-06            Translation failure không    Must
                          được làm lỗi                 
                          Menu/Reservation.            

  INT-TRANS-07            Manager/Admin có thể sửa bản Should
                          dịch persistent quan trọng   
                          bằng tay.                    

  INT-TRANS-08            API credentials phải nằm     Must
                          trong                        
                          environment/configuration,   
                          không hard-code.             

  INT-TRANS-09            Hệ thống nên cho phép thay   Should
                          Translation Provider mà      
                          không sửa toàn bộ business   
                          logic.                       
  ----------------------------------------------------------------------------

Conceptual flow:

`Select EN/ZH → Check Persistent Translation → If Missing Call Provider → Persist → Optional Cache → Display`

Nếu provider lỗi:

`Fallback VI`

------------------------------------------------------------------------

## 4.2 INT-WEATHER --- Weather Service

Weather Service chỉ dùng làm context cho Recommendation.

Core data cần:

-   Temperature.
-   Weather condition.
-   Data timestamp.
-   Location phù hợp.

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  INT-WEATHER-01          Hệ thống có thể lấy dữ  Should
                          liệu thời tiết từ       
                          Weather Service ngoài.  

  INT-WEATHER-02          Weather data chỉ dùng   Must
                          làm context cho         
                          Recommendation.         

  INT-WEATHER-03          Weather Service được    Should
                          tích hợp qua            
                          provider/service        
                          abstraction riêng.      

  INT-WEATHER-04          Không gọi Weather API   Should
                          cho từng Customer       
                          request nếu dữ liệu vẫn 
                          còn phù hợp để tái sử   
                          dụng.                   

  INT-WEATHER-05          Weather data có thể     Should
                          được cache trong khoảng 
                          thời gian hợp lý.       

  INT-WEATHER-06          Nếu Weather Service     Must
                          lỗi, Recommendation     
                          phải dùng context còn   
                          lại hoặc không hiển thị 
                          recommendation.         

  INT-WEATHER-07          Weather failure không   Must
                          được ảnh hưởng Menu,    
                          Order, Reservation hoặc 
                          Payment.                

  INT-WEATHER-08          API credentials không   Must
                          được hard-code trong    
                          repository.             
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 4.3 INT-PAY --- Payment Integration

### Core

-   Cash.
-   QR / Bank Transfer.
-   Staff confirmation.

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  INT-PAY-01              Core cho phép Staff ghi Must
                          nhận Cash Payment mà    
                          không phụ thuộc         
                          external provider.      

  INT-PAY-02              Core cho phép Staff xác Must
                          nhận QR/Bank Transfer   
                          thủ công.               

  INT-PAY-03              Payment Provider không  Must
                          tồn tại/không hoạt động 
                          không được ngăn         
                          Cash/Manual Transfer    
                          Payment.                
  -----------------------------------------------------------------------

### Future Payment Gateway

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  INT-PAY-04              Hệ thống có thể tạo     Future
                          payment request tới     
                          Payment Provider.       

  INT-PAY-05              Hệ thống có thể nhận    Future
                          trạng thái thanh toán   
                          từ provider.            

  INT-PAY-06              Callback/webhook phải   Future
                          được xác minh trước khi 
                          cập nhật Paid.          

  INT-PAY-07              Callback lặp lại không  Future
                          được tạo nhiều Payment  
                          thành công.             

  INT-PAY-08              Provider-specific logic Future
                          phải tách khỏi Core     
                          Payment Logic.          

  INT-PAY-09              Hệ thống lưu provider   Future
                          transaction/reference   
                          ID khi có.              

  INT-PAY-10              Provider trả Pending    Future
                          thì Invoice không được  
                          coi là Paid.            

  INT-PAY-11              Provider timeout không  Future
                          được tự suy luận        
                          payment thành công.     
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 4.4 INT-GEN --- General Integration Rules

  -----------------------------------------------------------------------
  ID                      Requirement             Priority
  ----------------------- ----------------------- -----------------------
  INT-GEN-01              External API request    Must
                          phải có timeout.        

  INT-GEN-02              Retry nếu có phải có    Must
                          giới hạn.               

  INT-GEN-03              Không retry vô hạn.     Must

  INT-GEN-04              External service error  Should
                          phải được ghi log phù   
                          hợp.                    

  INT-GEN-05              UI không hiển thị raw   Must
                          API error hoặc stack    
                          trace cho user.         
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 4.5 Failure Strategy

  External Service    Failure behavior   Core result
  ------------------- ------------------ -----------------------------------
  Translation         Timeout/Error      Fallback VI
  Weather             Timeout/Error      Recommendation bỏ Weather context
  AI/Recommendation   Error              Không recommendation
  Payment Gateway     Error              Cash/manual payment vẫn hoạt động

**Principle:** External Services là supporting dependency, không phải
single point of failure của Core Business.

------------------------------------------------------------------------

## 4.6 Data Sharing Boundary

### Translation

Chỉ gửi nội dung cần dịch.

Không cần gửi:

-   Customer phone.
-   Payment data.
-   Employee data.

### Weather

Chỉ cần location/context cần thiết cho weather.

### AI

Nếu Recommendation rule-based nội bộ thì không cần gửi Customer data ra
external AI.

Nếu sau này dùng external AI, phải xác định riêng data sharing policy.

------------------------------------------------------------------------

# 5. Role & Permission Requirements

## 5.1 Permission Matrix

| Module / Action | Customer | Staff | Kitchen | Manager | Admin |
|---|---:|---:|---:|---:|---:|
| Xem Menu, đổi locale | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tạo/Xem own Reservation | ✅ | ❌ | ❌ | ❌ | ❌ |
| Hỗ trợ/xử lý/check-in/no-show Reservation | ❌ | ✅ | ❌ | ✅ | ✅ |
| Xem trạng thái bàn | ❌ | ✅ | ✅ read-only | ✅ | ✅ |
| Mở Dining Session | ❌ | ✅ | ❌ | ✅ | ✅ |
| Customer self-order/gọi thêm | ✅ khi capability bật và session hợp lệ | ❌ | ❌ | ❌ | ❌ |
| Staff tạo Order/gọi thêm | ❌ | ✅ | ❌ | ✅ | ✅ |
| Xem Kitchen Queue | ❌ | ✅ read-only | ✅ | ✅ | ✅ |
| Waiting → Preparing → Ready | ❌ | ❌ | ✅ | ✅ | ✅ |
| Ready → Served | ❌ | ✅ | ❌ | ✅ | ✅ |
| Cancel Waiting Item | ❌ | ✅ | ❌ | ✅ | ✅ |
| Cancel Preparing Item | ❌ | ❌ | ❌ | ✅ | ✅ |
| Xem Bill | Own session | ✅ | ❌ | ✅ | ✅ |
| Xác nhận Cash/Bank Transfer | ❌ | ✅ | ❌ | ✅ | ✅ |
| Quản lý Product/Category/Table/Voucher | ❌ | ❌ | ❌ | ✅ | ✅ |
| Quản lý Inventory/Stock Movement | ❌ | ❌ | ❌ | ✅ | ✅ |
| Xem Customer list | ❌ | ❌ | ❌ | ✅ | ✅ |
| Quản lý Employee profile/status | ❌ | ❌ | ❌ | ✅ | ✅ |
| Gán Role/Permission | ❌ | ❌ | ❌ | ❌ | ✅ |
| Xem Report | ❌ | ❌ | ❌ | ✅ | ✅ |
| Chỉnh Translation | ❌ | ❌ | ❌ | ❌ | ✅ |
| System Configuration | ❌ | ❌ | ❌ | ❌ | ✅ |

Core role codes:

```text
customer
staff
kitchen
manager
admin
```

Manager/Admin vẫn có thể dùng chung Admin presentation context, nhưng
backend permission quyết định action thực tế. `customer` chỉ áp dụng
cho Customer Account; public Customer không cần role để browse Menu.

------------------------------------------------------------------------

## 5.2 PERM-CUSTOMER

### PERM-CUSTOMER-01

Customer được truy cập public functionality:

-   Menu.
-   Product detail.
-   Search/filter.
-   Language switch.
-   Reservation.
-   Recommendation.

### PERM-CUSTOMER-02

Customer không cần authentication để xem Menu.

### PERM-CUSTOMER-03

Customer có account chỉ được xem/chỉnh dữ liệu cá nhân của chính mình.

### PERM-CUSTOMER-04

Customer chỉ được xem Order History của chính mình.

### PERM-CUSTOMER-05

Customer không được truy cập:

-   Internal POS.
-   Employee Management.
-   Inventory.
-   Reports.
-   System Configuration.

### PERM-CUSTOMER-06

Nếu self-ordering được triển khai, Customer chỉ thao tác trên
order/session được xác định hợp lệ cho họ.

------------------------------------------------------------------------

## 5.3 PERM-STAFF

### PERM-STAFF-01

Staff được xem và vận hành trạng thái bàn.

### PERM-STAFF-02

Staff được:

-   Tạo Dining Session.
-   Tạo Order.
-   Thêm món.
-   Gọi thêm.
-   Thêm note.
-   Xem toàn bộ order trong phiên.

### PERM-STAFF-03

Staff được xử lý Reservation:

-   View.
-   Confirm.
-   Reject.
-   Check-in.
-   Mark No-show nếu có permission.

### PERM-STAFF-04

Staff được cập nhật `Ready → Served`.

### PERM-STAFF-05

Staff có thể hủy Waiting Item nếu có permission.

### PERM-STAFF-06

Staff không mặc định được hủy Preparing Item.

### PERM-STAFF-07

Staff được:

-   Mở billing.
-   Xem subtotal/discount/total.
-   Xác nhận Cash.
-   Xác nhận QR/Bank Transfer.
-   Hoàn tất Invoice.

### PERM-STAFF-08

Staff không mặc định được:

-   Sửa Product Price.
-   Tạo/xóa Employee.
-   Quản lý Role.
-   Chỉnh configuration quan trọng.
-   Xem toàn bộ report quản trị.

------------------------------------------------------------------------

## 5.4 PERM-KITCHEN

### PERM-KITCHEN-01

Kitchen/Bar được xem Order Item cần xử lý với:

-   Product.
-   Quantity.
-   Note.
-   Table/order reference.
-   Time nếu cần.

### PERM-KITCHEN-02

Kitchen/Bar được cập nhật:

`Waiting → Preparing`

`Preparing → Ready`

### PERM-KITCHEN-03

Kitchen/Bar không mặc định được:

-   Payment.
-   Sửa Price.
-   Voucher Management.
-   Table Management.
-   Employee Management.
-   Reports.

### PERM-KITCHEN-04

Kitchen/Bar không được xóa trực tiếp Served Item hoặc thay đổi lịch sử
order.

### PERM-KITCHEN-05

Nếu Bar/Kitchen cần tách queue sau này, ưu tiên category
routing/permission thay vì mở thêm role ngay từ Core.

------------------------------------------------------------------------

## 5.5 PERM-MANAGER / PERM-ADMIN

### PERM-MANAGER-01

Manager quản lý nghiệp vụ nhà hàng gồm Menu, Table, Reservation, Order,
Voucher, Inventory, Customer, Employee profile/status và Reports.

### PERM-MANAGER-02

Manager không được gán Role/Permission, chỉnh Translation persistent,
quản lý System Configuration hoặc secret.

### PERM-ADMIN-01

Admin có quyền quản lý:

-   Category.
-   Product.
-   Table.
-   Reservation.
-   Order.
-   Voucher.
-   Inventory.
-   Customer.
-   Employee.
-   Reports.
-   System configuration.
-   Persistent translations.

### PERM-ADMIN-02

Manager/Admin được tạo, cập nhật và Disable Employee.

### PERM-ADMIN-03

Chỉ Admin được gán Role/Permission trong Core role baseline.

### PERM-ADMIN-04

Manager/Admin được cập nhật Product Price và Availability.

### PERM-ADMIN-05

Chỉ Admin được chỉnh Translation persistent thủ công.

### PERM-ADMIN-06

Manager/Admin có thể thực hiện/phê duyệt sensitive actions như:

-   Cancel Preparing Item.
-   Inventory Adjustment.
-   Payment adjustment nếu future scope triển khai.

### PERM-ADMIN-07

Admin quyền cao không đồng nghĩa được phép phá Business Rule hoặc Data
Integrity.

------------------------------------------------------------------------

## 5.6 Sensitive Actions

  Action                  Default role
  ----------------------- -----------------------------
  Update Product Price    Manager / Admin
  Disable Employee        Manager / Admin
  Assign Permission       Admin
  Cancel Waiting Item     Staff / Manager / Admin
  Cancel Preparing Item   Manager / Admin
  Complete Payment        Staff / Manager / Admin
  Inventory Adjustment    Manager / Admin
  Edit Translation        Admin
  System Configuration    Admin

Possible permission naming direction:

`product.update-price`

`employee.disable`

`permission.assign`

`order-item.cancel`

`payment.complete`

`inventory.adjust`

`translation.update`

`settings.update`

Đây là permission naming baseline; implementation có thể bổ sung
permission chi tiết hơn nhưng không được mở rộng quyền mặc định trái với
matrix trên.

------------------------------------------------------------------------

## 5.7 Authorization Rules

### AUTHZ-01

Authorization phải enforce ở backend.

### AUTHZ-02

Internal action flow:

`Authenticated User → Role/Permission → Business Rule → Action`

### AUTHZ-03

Nếu user không có quyền:

-   Request bị từ chối.
-   Data không thay đổi.

### AUTHZ-04

Không nên hard-code toàn bộ behavior theo `if role == ...` ở nhiều nơi
nếu permission model có thể xử lý linh hoạt hơn.

### AUTHZ-05

Manager/Admin có thể quản lý role, nhưng Business Rule/Data Integrity
vẫn bắt buộc.

------------------------------------------------------------------------

## 5.8 Role vs Permission

Model:

`User → Role → Permissions`

Ví dụ Staff:

-   `table.view`
-   `table.assign`
-   `reservation.manage`
-   `order.create`
-   `order.update`
-   `payment.complete`
-   `order-item.mark-served`

Kitchen:

-   `kitchen.queue.view`
-   `order-item.mark-preparing`
-   `order-item.mark-ready`

Admin:

-   Management permissions.

------------------------------------------------------------------------

## 5.9 Customer Ownership

Customer chỉ được truy cập:

-   Own Profile.
-   Own Orders.
-   Own Reservations.

Không được truy cập dữ liệu riêng của Customer khác.

------------------------------------------------------------------------

## 5.10 Historical Data

Nếu Staff A đã tạo Order và sau đó bị Disabled:

`created_by = Staff A`

vẫn phải được giữ.

Tương tự:

-   Payment confirmation.
-   Inventory transaction.
-   Cancel action.

------------------------------------------------------------------------

# 6. Requirement Priority & Scope

## 6.1 Core Business --- Must

### Authentication & Authorization

Must:

-   Internal Login.
-   Logout.
-   Disabled account protection.
-   Backend authorization.
-   Role/Permission.
-   Password security.
-   Public Menu access.

Should:

-   Customer Registration/Profile.
-   Password recovery.

------------------------------------------------------------------------

## 6.2 Menu & Product --- Must

Core:

-   Category.
-   Product.
-   Name.
-   Price.
-   Description.
-   Image.
-   Search/filter.
-   Availability.
-   Active/Inactive.
-   Product management.
-   Historical data protection.

------------------------------------------------------------------------

## 6.3 Table --- Must

Core:

-   Table list.
-   Capacity.
-   Status.
-   Assign Table.
-   Available.
-   Reserved.
-   Occupied.
-   Cleaning.
-   Single active Dining Session per Table.

Not Core:

-   Drag/drop floor map.
-   3D map.
-   Automatic table optimization.

------------------------------------------------------------------------

## 6.4 Reservation --- Must

Must:

-   Request.
-   Date/time.
-   Party size.
-   Contact.
-   Staff review.
-   Confirm.
-   Reject.
-   Check-in.
-   Cancel.
-   Table assignment.

Should:

-   No-show.
-   No-show timeout config.
-   Note.

Future/Not Core:

-   Deposit reservation.
-   Auto reminder.
-   Dynamic reservation optimization.

------------------------------------------------------------------------

## 6.5 Dining Session --- Must

Toàn bộ `FR-SESSION-*` là Must.

Model:

`Table → Dining Session → Order(s) → Order Items`

Không đơn giản thành:

`Table → Order`

------------------------------------------------------------------------

## 6.6 Ordering/POS --- Must

Must:

-   Create Order.
-   Add Product.
-   Quantity.
-   Note.
-   Product availability validation.
-   Additional Orders.
-   Order history.
-   Link Order → Dining Session.
-   Unit Price snapshot.
-   View all Dining Session items.

Critical:

`FR-ORDER-08`

`FR-ORDER-09`

------------------------------------------------------------------------

## 6.7 Kitchen/Bar --- Basic Core

Must:

-   Kitchen queue.
-   Product.
-   Quantity.
-   Note.
-   Table reference.
-   Status per Order Item.
-   Waiting.
-   Preparing.
-   Ready.
-   Served.
-   Prevent hard deletion of Served Item.

Should:

-   Cancel Waiting Item.
-   Elevated cancel of Preparing Item.

Future:

-   Dedicated KDS.
-   Printer routing.
-   Station automation.
-   Preparation analytics.

------------------------------------------------------------------------

## 6.8 Billing & Payment --- Must

Must:

-   Dining Session billing.
-   Subtotal.
-   Discount.
-   Total.
-   Cash.
-   QR/Bank Transfer confirmation.
-   Payment status.
-   Invoice.
-   Complete Session.
-   Table Cleaning.
-   Anti double-payment.

Core Payment không phụ thuộc Payment Gateway.

------------------------------------------------------------------------

## 6.9 Voucher --- Simplified Core / Should

Core simplified:

-   Code.
-   Discount.
-   Active.
-   Valid period.
-   Minimum order.
-   Usage limit.
-   One voucher/invoice.
-   Validation before payment.

Not Core:

-   Voucher stacking.
-   Segmentation.
-   Personalized promotion.
-   Campaign engine.
-   Complex coupon rules.

------------------------------------------------------------------------

## 6.10 Inventory --- Simplified Core / Should

Core:

-   Inventory Item.
-   Current Stock.
-   Import.
-   Export.
-   Adjustment.
-   History.
-   Minimum Stock.
-   Low Stock Warning.

Future:

-   Recipe.
-   BOM.
-   Ingredient quantity.
-   Automatic deduction.
-   Food cost calculation.

**Scope rule:** Inventory Management không được mở rộng thành restaurant
ERP.

------------------------------------------------------------------------

## 6.11 Customer Management

Must:

-   Basic Customer data.
-   Admin view.

Should:

-   Customer account.
-   Profile.
-   Order history.

Could:

-   Total spend.
-   Basic analytics.

Future:

-   CRM.
-   Segmentation.
-   Loyalty.
-   Membership.
-   Marketing automation.

------------------------------------------------------------------------

## 6.12 Employee Management --- Must

Core:

-   Employee account.
-   Update employee.
-   Role.
-   Permission.
-   Disable.
-   Preserve history.

Not Core:

-   Payroll.
-   Attendance.
-   Shift Management.
-   Salary.
-   HRM.

Employee module là Identity + Access + Operational History.

------------------------------------------------------------------------

## 6.13 Reporting --- Basic Core / Must

Core:

-   Revenue.
-   Revenue by period.
-   Order count.
-   Best-selling Product.

Should:

-   Average Order Value.

Future:

-   Advanced BI.
-   Forecasting.
-   Customer segmentation.
-   Profit analysis.
-   Food cost analytics.
-   AI analytics.

------------------------------------------------------------------------

## 6.14 Multilingual --- Must

Core:

-   VI. 
-   EN.
-   ZH.
-   Language switch.
-   VI fallback.
-   Static localization.
-   Dynamic translation storage/cache.
-   Translation Service failure fallback.

Should:

-   Admin translation editing.

Not Core:

-   10+ languages.
-   Translation workflow platform.
-   Enterprise localization management.

------------------------------------------------------------------------

## 6.15 AI Recommendation --- Should

AI nằm trong scope project nhưng không phải Critical Core Business.

Should:

-   Product recommendation.
-   Time context.
-   Weather context.
-   Popularity.
-   Current Cart.
-   Rule/context-based logic.

Could:

-   Customer Order History.

Future:

-   Machine Learning.
-   Collaborative filtering.
-   User embedding.
-   Advanced personalization.
-   Recommendation analytics.

------------------------------------------------------------------------

## 6.16 External Integrations

Translation Service:

`Must`

Weather Service:

`Should`

Payment Gateway:

`Future`

------------------------------------------------------------------------

# 7. Scope Matrix

  Module                   Priority   Scope
  ------------------------ ---------- -----------------
  Authentication           Must       Core
  Menu/Product             Must       Core
  Session Cart             Must       Core
  Table                    Must       Core
  Reservation              Must       Core
  Dining Session           Must       Core
  Ordering/POS             Must       Core
  Kitchen/Bar              Must       Basic Core
  Billing/Payment          Must       Core
  Employee                 Must       Core
  Role/Permission          Must       Core
  Reporting                Must       Basic Core
  Multilingual             Must       Core
  Customer Account         Should     Core Extension
  Customer Self-order      Must       Core when enabled
  Voucher                  Should     Simplified Core
  Inventory                Should     Simplified Core
  AI Recommendation        Should     Project Feature
  Weather Integration      Should     Supporting
  Customer Analytics       Could      Optional
  Payment Gateway          Future     Future
  Recipe/BOM               Future     Future
  Loyalty/Membership       Future     Future
  Advanced CRM             Future     Future
  ML Recommendation        Future     Future
  Kitchen Display System   Future     Future
  Mobile App               Future     Future
  Delivery Integration     Future     Future
  Takeaway Ordering        Future     Future
  Purchasing/Supplier      Future     Future
  News/CMS/WordPress       Future     Future
  Product Gallery/Video    Future     Future
  AI Analytics             Future     Future

------------------------------------------------------------------------

# 8. Scope Layers

## Layer 1 --- Critical Core

-   Auth.
-   Menu.
-   Session Cart.
-   Table.
-   Reservation.
-   Dining Session.
-   Order/POS.
-   Customer self-order in valid Dining Session.
-   Kitchen Basic.
-   Payment.
-   Role/Permission.

## Layer 2 --- Supporting Core

-   Customer.
-   Employee.
-   Reporting.
-   Multilingual.

## Layer 3 --- Simplified / Project Features

-   Voucher.
-   Inventory.
-   AI Recommendation.
-   Weather Integration.

## Layer 4 --- Future

-   Advanced Inventory.
-   Recipe/BOM.
-   Loyalty.
-   CRM.
-   Payment Gateway.
-   Advanced Refund.
-   ML Recommendation.
-   Kitchen Display.
-   Mobile App.
-   Takeaway Ordering.
-   Delivery Integration.
-   Purchasing/Supplier.
-   News/CMS/WordPress.
-   Product Gallery/Video.
-   AI Analytics.

------------------------------------------------------------------------

# 9. Scope Guardrails

### SCOPE-01

AI không được tự implement Future Scope chỉ vì thấy hợp lý.

### SCOPE-02

Không biến Inventory thành ERP/Recipe Management nếu chưa có Change
Request.

### SCOPE-03

Không biến Customer Management thành CRM.

### SCOPE-04

Không biến Employee Management thành HRM.

### SCOPE-05

Không biến Reporting thành BI platform.

### SCOPE-06

Không biến AI Recommendation thành ML project phức tạp.

### SCOPE-07

Không yêu cầu Payment Gateway để hoàn thành Core Payment.

### SCOPE-08

Không thêm microservices/distributed architecture nếu chưa có
requirement tương ứng.

### SCOPE-09

Nếu implementation làm thay đổi Business Flow/Business Rule đã chốt:

`Change Request / Requirement Review`

Không tự sửa requirement để hợp với code.

------------------------------------------------------------------------

# 10. Review & Final System Decisions

## 10.1 Business Need Coverage

  Business Need            Requirement Group    Result
  ------------------------ -------------------- ---------
  Table Management         FR-TABLE-\*          Covered
  Reservation Management   FR-RES-\*            Covered
  Digital Ordering/POS     FR-ORDER-\*          Covered
  Dining Session           FR-SESSION-\*        Covered
  Kitchen/Bar              FR-KITCHEN-\*        Covered
  Billing & Payment        FR-PAY-\*            Covered
  Digital Menu             FR-MENU-\*           Covered
  Customer Cart            FR-CART-\*           Covered
  Inventory                FR-INV-\*            Covered
  Voucher                  FR-VOUCHER-\*        Covered
  Customer                 FR-CUSTOMER-\*       Covered
  Employee                 FR-EMP-\*            Covered
  Reporting                FR-REPORT-\*         Covered
  Multilingual             FR-LANG-\*           Covered
  AI Recommendation        FR-AI-\*             Covered
  Role & Permission        PERM-\* / AUTHZ-\*   Covered
  System Configuration     FR-CONFIG-\*         Covered

------------------------------------------------------------------------

## 10.2 Business Rule Coverage --- Critical Examples

### BR-TABLE-04

> Một Table không có hai Dining Session active.

Covered by:

-   FR-TABLE-06.
-   FR-SESSION-05.
-   NFR-DATA-01.

### BR-ORDER-03

> Unit Price phải được lưu tại thời điểm Order.

Covered by:

-   FR-ORDER-08.
-   FR-ORDER-09.
-   NFR-DATA-02.
-   NFR-DATA-03.

### BR-PAY-03

> Không double-complete Payment.

Covered by:

-   FR-PAY-08.
-   NFR-REL-04.

------------------------------------------------------------------------

# 11. Final System Decisions

## SYS-DEC-01 --- Billing Unit

**Core Billing Unit = Dining Session**

Một Dining Session có thể chứa nhiều Order.

Invoice được tổng hợp từ toàn bộ billable Order Items trong Dining
Session.

------------------------------------------------------------------------

## SYS-DEC-02 --- Product Status Separation

Giữ riêng:

-   `Active / Inactive`
-   `Available / Unavailable`

Ví dụ:

`Active = true`

`Available = false`

nghĩa là Product vẫn thuộc hệ thống nhưng tạm hết hàng.

------------------------------------------------------------------------

## SYS-DEC-03 --- Reservation vs Table Lock

Reservation và Table Assignment liên quan nhưng không đồng nhất.

Reservation lưu:

-   Date.
-   Time.
-   Party Size.

Table có thể được assign khi Staff chuẩn bị hoặc khi Check-in.

Customer không bắt buộc chọn bàn cụ thể.

------------------------------------------------------------------------

## SYS-DEC-04 --- Customer vs User Account

`Customer ≠ User Account bắt buộc`

Walk-in Customer vẫn có thể được phục vụ mà không đăng ký tài khoản.

------------------------------------------------------------------------

## SYS-DEC-05 --- Employee Scope

Employee Management chỉ phục vụ:

-   Identity.
-   Access.
-   Operational History.

Không phải HRM.

------------------------------------------------------------------------

## SYS-DEC-06 --- Localization vs Translation API

Static UI:

`Localization Resources`

Dynamic content:

`Persistent Translation + Optional Runtime Cache + Translation Service`

Không dùng machine translation cho toàn bộ UI ở mọi request.

------------------------------------------------------------------------

## SYS-DEC-07 --- AI Boundary

AI Recommendation:

-   Chỉ gợi ý.
-   Không tự tạo Order Item.
-   Không phải dependency của Core.

------------------------------------------------------------------------

## SYS-DEC-08 --- Backend Authorization

Permission phải được enforce ở backend.

Ẩn button frontend không đủ.

------------------------------------------------------------------------

## SYS-DEC-09 --- Permission vs Data Integrity

Admin permission không được override Data Integrity Rule.

Ví dụ:

Admin không được làm historical Order Price tự thay đổi theo Product
Price mới.

## SYS-DEC-10 --- Canonical State Vocabulary

Canonical Core states:

```text
Reservation: pending / confirmed / checked-in / completed /
             rejected / cancelled / no-show

Restaurant Table runtime: available / reserved / occupied / cleaning

Dining Session: active / completed

Order Item: waiting / preparing / ready / served / cancelled

Bill: draft / unpaid / paid / cancelled

Payment: pending / success / failed / cancelled
```

`closed` không phải Dining Session status; dùng `completed`.
`billing` là UI/workflow context, không phải Dining Session status.

## SYS-DEC-11 --- Bill and Invoice

Bill là record tài chính trước và sau payment. Khi Bill được Paid, hệ
thống tạo Invoice representation/print từ chính paid Bill; Core không
có Invoice entity/table riêng.

## SYS-DEC-12 --- Cart and Customer Self-order

Core Cart là session/application state. Customer chỉ submit Cart vào
Dining Session hợp lệ; backend tạo Order và snapshot price sau khi
validate lại toàn bộ input.

## SYS-DEC-13 --- Persistent Translation and Settings

Manual/dynamic translations và runtime settings do Admin quản lý phải
có persistent storage. Runtime cache chỉ là optimization có thể tái tạo.

## SYS-DEC-14 --- Inventory Boundary

Supplier/Purchasing không thuộc Core. Core Inventory chỉ quản lý
Inventory Item và Stock Movement import/export/adjustment.

------------------------------------------------------------------------

# 12. Product Lifecycle Review

`Active / Inactive`

và:

`Available / Unavailable`

là hai chiều trạng thái khác nhau.

Không hard-delete Product đã có Order History.

------------------------------------------------------------------------

# 13. Table Status Review

Core states:

-   Available.
-   Reserved.
-   Occupied.
-   Cleaning.

Nếu bàn ngừng sử dụng lâu dài, ưu tiên dùng Table Active/Inactive thay
vì tạo quá nhiều runtime states.

------------------------------------------------------------------------

# 14. Order → Kitchen Review

Kitchen status được quản lý ở **Order Item level**.

Ví dụ:

-   Beer --- Served.
-   Fries --- Ready.
-   Chicken --- Preparing.
-   Hot Pot --- Waiting.

Không dùng một kitchen status duy nhất cho cả Order.

------------------------------------------------------------------------

# 15. Billing Review

Flow:

`Dining Session`

→ `All valid Order Items`

→ `Subtotal`

→ `Voucher/Discount`

→ `Total`

→ `Payment`

→ `Invoice`

→ `Session Completed`

→ `Table Cleaning`

------------------------------------------------------------------------

# 16. Inventory Review

Core:

-   Current Stock.
-   Import.
-   Export.
-   Adjustment.
-   History.
-   Minimum Stock.

Future:

-   Recipe/BOM.
-   Ingredient deduction.

------------------------------------------------------------------------

# 17. Multilingual Review

Languages:

-   VI --- Default.
-   EN.
-   ZH.

Flow:

`Requested Language → Persistent Translation? → If Missing Translation Service → Persist → Optional Cache → Display`

Failure:

`Translation Error → VI fallback`

------------------------------------------------------------------------

# 18. AI Recommendation Review

Possible inputs:

-   Product Data.
-   Time.
-   Weather.
-   Popularity.
-   Current Cart.
-   Optional Order History.

Core implementation:

`Rule-based + Context`

Failure:

`Recommendation Error → No Recommendation → Core continues`

------------------------------------------------------------------------

# 19. External Dependency Review

Core relationship:

-   Translation → fallback VI.
-   Weather → optional context.
-   Payment Gateway → Future.

External services không được trở thành single point of failure cho Core
Business.

------------------------------------------------------------------------

# 20. Security & Authorization Review

Request flow:

`Authentication`

→ `Role/Permission`

→ `Business Rule`

→ `Data Integrity`

→ `Execute`

Frontend-only permission không được coi là security.

------------------------------------------------------------------------

# 21. System Requirements Baseline v1.0

## System Purpose

89 Beer Garden là hệ thống quản lý hoạt động phục vụ tại quán kết hợp
Customer Website và POS.

## Primary Operational Model

`Reservation / Walk-in`

→ `Table`

→ `Dining Session`

→ `Order(s)`

→ `Order Items`

→ `Kitchen / Bar`

→ `Serving`

→ `Billing`

→ `Payment`

→ `Invoice`

→ `Complete`

## Primary Actors

-   Customer.
-   Staff.
-   Kitchen / Bar Staff.
-   Manager / Admin.

## Core Supporting Capabilities

-   Customer.
-   Employee.
-   Reporting.
-   Multilingual.

## Simplified Features

-   Voucher.
-   Inventory.
-   AI Recommendation.

## External Services

-   Translation.
-   Weather.
-   Future Payment Gateway.

------------------------------------------------------------------------

# 22. Definition of Done --- System Requirements

System Requirements được coi là chốt khi:

-   Functional Requirements đã xác định.
-   Non-functional Requirements đã xác định.
-   Integration Requirements đã xác định.
-   Role & Permission đã xác định.
-   Requirement Priority đã xác định.
-   Core/Future Scope đã phân tách.
-   Business Rules đã được trace sang Requirement.
-   Critical Data Rules đã xác định.
-   External failure behavior đã xác định.
-   Các điểm mơ hồ lớn đã được review.
-   System Decisions đã được chốt.
-   Có baseline để chuyển sang Use Case.

------------------------------------------------------------------------

# 23. Guidance for AI Coding Agents

AI coding agents phải tuân thủ các nguyên tắc sau:

1.  Không giả định `1 Table = 1 Order`.
2.  Billing của Core thực hiện theo Dining Session.
3.  Order Item phải lưu Unit Price lịch sử.
4.  Không tính lại Order lịch sử từ `products.price`.
5.  Kitchen status được quản lý ở Order Item level.
6.  Không hard-delete Product đã có Order History.
7.  Không hard-delete Employee theo cách làm mất historical references.
8.  Customer không bắt buộc phải có User Account.
9.  Reservation không đồng nghĩa với khóa cứng một Table từ thời điểm
    tạo.
10. Product Active/Inactive và Available/Unavailable là hai state khác
    nhau.
11. Backend phải enforce Role/Permission.
12. Admin không được phá Data Integrity Rule.
13. Translation Service lỗi phải fallback VI.
14. Không gọi Translation API ở mọi request nếu đã có translation.
15. Weather Service chỉ là optional recommendation context.
16. AI Recommendation lỗi không được làm Core Business lỗi.
17. AI không tự thêm món vào Cart/Order.
18. Payment Core phải hoạt động bằng Cash/Manual QR ngay cả khi không có
    Payment Gateway.
19. Không tự kéo Future Scope vào Core.
20. Không biến Inventory thành ERP/BOM system.
21. Không biến Customer Management thành CRM.
22. Không biến Employee Management thành HRM.
23. Không biến Reporting thành BI platform.
24. Không tự thêm microservices/distributed architecture khi requirement
    chưa yêu cầu.
25. Nếu implementation mâu thuẫn với requirement/business rule, đánh dấu
    Change Request thay vì sửa ngầm requirement.
26. Database schema chưa được tài liệu này chốt chi tiết; không tự suy
    diễn schema cuối cùng ngoài các constraint requirement đã nêu.
27. API contract và UI flow chi tiết sẽ được chốt ở các tài liệu sau.

------------------------------------------------------------------------

# 24. Handoff to Use Case Analysis

Tài liệu này là **System Requirements Baseline v1.0**.

Bước tiếp theo phải chuyển Functional Requirements thành các Use Case
theo Actor, ví dụ:

`Customer`

→ Browse Menu\
→ Switch Language\
→ Make Reservation\
→ Place Order\
→ Apply Voucher\
→ View Recommendation

`Staff`

→ Assign Table\
→ Open Dining Session\
→ Create Order\
→ Serve Item\
→ Process Reservation\
→ Complete Payment

`Kitchen / Bar`

→ View Kitchen Queue\
→ Mark Preparing\
→ Mark Ready

`Manager / Admin`

→ Manage Product\
→ Manage Table\
→ Manage Employee\
→ Manage Inventory\
→ Manage Voucher\
→ View Reports

Mỗi Use Case sau này cần trace ngược về `FR-*`, `PERM-*` và Business
Rule tương ứng.

------------------------------------------------------------------------

**Status:** `FINAL BASELINE v1.0`
