# 89 Beer Garden — Local Development Environment

Tài liệu này hướng dẫn chuẩn bị môi trường phát triển local trên Windows cho dự
án 89 Beer Garden. Mục tiêu là giúp cài đặt, cấu hình và kiểm tra các công cụ
cần thiết trước khi khởi tạo Laravel.

Đây là tài liệu hướng dẫn vận hành, không thay thế các tài liệu baseline của dự
án. Công nghệ phải tuân theo `README.md` và `docs/development-context.md`:

```text
Backend: PHP 8.4 + Laravel 12
Database: MySQL 8.x
Frontend tooling: Node.js + npm + Vite
Version control: Git
Editor: Visual Studio Code
```

---

## 1. Phạm vi Giai đoạn 0

Thực hiện lần lượt:

1. Cài Visual Studio Code.
2. Cài PHP 8.4.
3. Bật và kiểm tra PHP extensions.
4. Cài Composer.
5. Cài MySQL 8.x và MySQL Workbench.
6. Cài Node.js và npm.
7. Cài Git.
8. Kiểm tra tất cả công cụ.
9. Xác nhận MySQL chạy và kết nối được.
10. Xác nhận `LOCAL ENVIRONMENT READY`.

Nên cài và kiểm tra ngay sau từng bước để dễ xác định nguyên nhân nếu có lỗi.

Trong Giai đoạn 0, không:

- khởi tạo Laravel;
- tạo database của dự án;
- chạy migration hoặc seeder;
- chạy `npm install` trong repository;
- triển khai chức năng;
- sửa source code hoặc tài liệu baseline;
- tự động chuyển sang Giai đoạn 1.

---

## 2. Visual Studio Code

### 2.1 Mục đích

Visual Studio Code là trình soạn thảo dùng để:

- mở và chỉnh sửa source code;
- viết PHP, Blade, CSS và JavaScript;
- chạy terminal;
- chạy Composer, npm và Laravel Artisan;
- theo dõi thay đổi Git.

### 2.2 Cài đặt

Tải Visual Studio Code từ trang chính thức và cài cho Windows. Nên bật các lựa
chọn `Add to PATH` và `Open with Code` nếu trình cài đặt cung cấp.

Kiểm tra:

```powershell
code --version
```

### 2.3 Extensions đề xuất

| Extension | Công dụng |
|---|---|
| PHP Intelephense | Gợi ý code PHP, điều hướng class/method và phát hiện một số lỗi cú pháp. |
| Laravel Blade Snippets | Gợi ý cú pháp Blade như `@if`, `@foreach`, `@section`. |
| EditorConfig for VS Code | Giữ quy ước tab, space, encoding và kiểu xuống dòng của dự án. |
| MySQL/Database Client | Kết nối database trong VS Code; không bắt buộc nếu dùng Workbench. |

---

## 3. PHP 8.4

### 3.1 Mục đích

PHP là ngôn ngữ chạy backend Laravel. PHP xử lý request, business logic, kết
nối database và giao tiếp với các dịch vụ bên ngoài.

### 3.2 Tải và cài đặt

Tải PHP for Windows với các lựa chọn:

```text
PHP 8.4
x64
Non Thread Safe (NTS)
ZIP
```

Giải nén vào:

```text
C:\php84
```

Trong thư mục này, sao chép `php.ini-development` rồi đổi tên bản sao thành:

```text
C:\php84\php.ini
```

Nên bật `View → Show → File name extensions` trong File Explorer để tránh tạo
nhầm file `php.ini.txt`.

### 3.3 Cấu hình PATH

Mở:

```text
Environment Variables → User variables → Path
```

Thêm và đưa lên đầu danh sách:

```text
C:\php84
```

Nếu có đường dẫn PHP 8.2 hoặc PHP do WinGet cài, đặt `C:\php84` phía trên các
đường dẫn đó. Đóng toàn bộ PowerShell và VS Code rồi mở lại để nhận PATH mới.

Kiểm tra:

```powershell
Get-Command php
where.exe php
php -v
php --ini
```

Kết quả cần xác nhận:

```text
PHP executable: C:\php84\php.exe
PHP version: 8.4.x
Loaded Configuration File: C:\php84\php.ini
```

Lưu ý: trong PowerShell phải dùng `where.exe php`, không dùng `where php`.

### 3.4 PHP extensions

PHP extension bổ sung khả năng cho PHP. Mở `C:\php84\php.ini`, bảo đảm có:

```ini
extension_dir = "ext"
```

Để bật extension, xóa dấu `;` ở đầu dòng. Ví dụ:

```ini
;extension=curl
```

thành:

```ini
extension=curl
```

Bật các extension sau:

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
```

| Extension | Công dụng |
|---|---|
| `curl` | Gọi Gemini API, Translation API, Weather API hoặc dịch vụ HTTPS khác. |
| `fileinfo` | Nhận biết loại file và hỗ trợ validation file upload. |
| `mbstring` | Xử lý Unicode, tiếng Việt và nội dung đa ngôn ngữ chính xác. |
| `mysqli` | Kết nối MySQL qua giao diện MySQLi; hữu ích cho một số công cụ/thư viện. |
| `openssl` | Kết nối HTTPS, mã hóa và hỗ trợ Composer tải package an toàn. |
| `PDO` | Giao diện database thống nhất mà Laravel sử dụng; thường có sẵn trong PHP. |
| `pdo_mysql` | Cho phép Laravel/PDO kết nối MySQL; bắt buộc cho dự án. |
| `zip` | Đọc và giải nén ZIP; Composer có thể dùng khi cài package. |

Các module Laravel thường cần và thường đã có sẵn gồm `ctype`, `dom`, `filter`,
`hash`, `json`, `libxml`, `PDO`, `session`, `tokenizer` và `xml`.

Kiểm tra toàn bộ module:

```powershell
php -m
```

Kiểm tra nhanh các extension chính:

```powershell
php -m | Select-String "curl|fileinfo|mbstring|mysqli|openssl|PDO|pdo_mysql|zip"
```

---

## 4. Composer

### 4.1 Mục đích

Composer quản lý thư viện PHP. Composer tải Laravel và các package được khai
báo trong `composer.json`, quản lý phiên bản dependency và tạo cơ chế autoload
class PHP.

```text
composer.json → Composer → PHP packages
```

### 4.2 Cài đặt

Tải `Composer-Setup.exe` từ trang Composer chính thức. Khi được hỏi PHP
executable, chọn:

```text
C:\php84\php.exe
```

Hoàn tất cài đặt, đóng PowerShell và mở lại.

### 4.3 Kiểm tra

```powershell
composer --version
composer diagnose
```

Composer phải nhận:

```text
PHP version: 8.4.x
PHP binary path: C:\php84\php.exe
```

Các mục quan trọng trong `composer diagnose` phải là `OK`, đặc biệt:

```text
Checking platform settings
Checking git settings
Checking http connectivity to packagist
Checking https connectivity to packagist
Checking github.com rate limit
```

Thông báo sau không phải lỗi nghiêm trọng nếu PHP đã có extension `zip`:

```text
zip: extension present, unzip not available, 7-Zip not available
```

Nếu gặp `curl error 6` hoặc `Could not resolve host: api.github.com`, kiểm tra:

```powershell
Resolve-DnsName api.github.com
curl.exe -I https://api.github.com/rate_limit
ipconfig /flushdns
composer diagnose
```

Nếu Composer vẫn nhận PHP cũ, kiểm tra `Get-Command php`, thứ tự PATH, hoặc chạy
lại Composer Setup và chọn `C:\php84\php.exe`.

---

## 5. MySQL 8.x

### 5.1 Mục đích

MySQL là hệ quản trị cơ sở dữ liệu dùng để lưu dữ liệu tài khoản, menu, bàn,
khách hàng, đặt bàn, phiên phục vụ, đơn gọi món, hóa đơn, thanh toán, kho,
voucher và báo cáo.

```text
Laravel → PDO/pdo_mysql → MySQL
```

### 5.2 Cài đặt

Tải MySQL Installer for Windows. Với máy development có thể chọn `Developer
Default`; nếu chọn `Custom`, cài tối thiểu:

```text
MySQL Server 8.x
MySQL Workbench
MySQL Shell (optional)
```

Cấu hình đề xuất:

```text
Config Type: Development Computer
Connectivity: TCP/IP
Host: 127.0.0.1
Port: 3306
Administrative user: root
Windows Service: enabled
```

Đặt mật khẩu `root` và lưu an toàn. Không ghi mật khẩu vào README, baseline,
Git hoặc ảnh chia sẻ công khai.

Trong Giai đoạn 0 chưa tạo database của 89 Beer Garden.

### 5.3 Cấu hình PATH cho MySQL Client

Thêm đường dẫn sau vào User `Path`:

```text
C:\Program Files\MySQL\MySQL Server 8.0\bin
```

Không nhầm với:

```text
C:\Program Files\MySQL\MySQL Shell 8.0\bin
```

`MySQL Server ...\bin` chứa `mysql.exe`; `MySQL Shell ...\bin` chủ yếu chứa
`mysqlsh`.

Đóng toàn bộ PowerShell và VS Code rồi mở lại. Kiểm tra:

```powershell
where.exe mysql
mysql --version
```

Nếu PATH chưa hoạt động, có thể kiểm tra trực tiếp:

```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" --version
```

### 5.4 Kiểm tra service

Mở `Services` trên Windows và tìm service tương tự `MySQL80`. Trạng thái cần là
`Running`.

Có thể kiểm tra bằng PowerShell:

```powershell
Get-Service MySQL80
```

### 5.5 Kiểm tra kết nối bằng command line

```powershell
mysql -h 127.0.0.1 -P 3306 -u root -p
```

Ý nghĩa:

- `-h 127.0.0.1`: máy MySQL local;
- `-P 3306`: cổng MySQL;
- `-u root`: tài khoản đăng nhập;
- `-p`: yêu cầu nhập mật khẩu.

Khi nhập mật khẩu, terminal không hiện ký tự hoặc dấu `*`; đây là hành vi bình
thường.

Sau khi thấy dấu nhắc `mysql>`, chỉ nhập câu SQL, không nhập lại chữ `mysql>`:

```sql
SELECT VERSION();
SELECT CURRENT_USER();
SHOW DATABASES;
EXIT;
```

Kết quả thành công cần xác nhận:

- phiên bản là MySQL 8.x;
- user là tài khoản đã đăng nhập, ví dụ `root@localhost`;
- `SHOW DATABASES` trả danh sách database hệ thống;
- `EXIT` quay về PowerShell.

### 5.6 Kiểm tra bằng MySQL Workbench

Tạo connection:

```text
Connection Name: Local MySQL
Hostname: 127.0.0.1
Port: 3306
Username: root
Password: mật khẩu đã đặt
```

Chọn `Test Connection`. Khi kết nối thành công, chạy lại:

```sql
SELECT VERSION();
SELECT CURRENT_USER();
SHOW DATABASES;
```

---

## 6. phpMyAdmin và XAMPP

phpMyAdmin là giao diện web quản lý MySQL, không phải database. Laravel không
phụ thuộc phpMyAdmin để hoạt động.

XAMPP thường đóng gói Apache, PHP, phpMyAdmin và MariaDB. Vì baseline của dự án
là PHP 8.4 và MySQL 8.x, không dùng trọn bộ XAMPP làm runtime chính nếu phiên
bản đóng gói không khớp baseline.

Nếu cần giao diện quen thuộc, có thể cấu hình XAMPP chỉ cho Apache/phpMyAdmin và
cho phpMyAdmin kết nối MySQL Community Server. Khi đó:

```text
Laravel/Composer       → C:\php84\php.exe
Database dự án         → MySQL Community Server 8.x
XAMPP Apache/phpMyAdmin → MySQL Community Server 8.x
XAMPP MariaDB          → không chạy
```

Không chạy đồng thời MariaDB của XAMPP và MySQL Community Server trên cổng
`3306`.

---

## 7. Node.js và npm

### 7.1 Mục đích

Node.js chạy các công cụ frontend như Vite, xử lý JavaScript/CSS và build tài
nguyên frontend. npm quản lý frontend packages khai báo trong `package.json`.

```text
composer.json → Composer → PHP packages
package.json  → npm      → frontend packages
```

### 7.2 Cài đặt và kiểm tra

Cài bản Node.js LTS; npm được cài kèm Node.js. Sau khi cài, mở PowerShell mới:

```powershell
node -v
npm -v
```

Trong Giai đoạn 0 chưa chạy `npm install` hoặc `npm run dev` trong repository.

### 7.3 Lỗi PowerShell chặn npm.ps1

Nếu `node -v` hoạt động nhưng `npm -v` báo:

```text
running scripts is disabled on this system
PSSecurityException
```

Có thể kiểm tra ngay bằng:

```powershell
npm.cmd -v
```

Để sửa lâu dài cho tài khoản hiện tại:

```powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
```

Nhập `Y` khi được hỏi, đóng terminal rồi mở lại. Không sử dụng `Unrestricted`
hoặc `Bypass` chỉ để chạy npm.

Kiểm tra lại:

```powershell
node -v
npm -v
```

---

## 8. Git

### 8.1 Mục đích

Git quản lý lịch sử source code, theo dõi file thay đổi, lưu commit, so sánh
phiên bản và hỗ trợ đồng bộ với GitHub.

### 8.2 Cài đặt và kiểm tra

Cài Git for Windows với các lựa chọn mặc định phù hợp, sau đó:

```powershell
git --version
```

Cấu hình danh tính nếu chưa có:

```powershell
git config --global user.name "Tên của bạn"
git config --global user.email "email@example.com"
git config --global --list
```

Repository đã tồn tại nên không chạy `git init`.

---

## 9. Kiểm tra tổng thể

Mở một PowerShell mới và chạy:

```powershell
Get-Command php
where.exe php
php -v
php --ini
php -m
composer --version
composer diagnose
mysql --version
node -v
npm -v
git --version
```

Kiểm tra MySQL:

```powershell
mysql -h 127.0.0.1 -P 3306 -u root -p
```

```sql
SELECT VERSION();
SELECT CURRENT_USER();
SHOW DATABASES;
EXIT;
```

---

## 10. Checklist hoàn thành

- [ ] VS Code hoạt động.
- [ ] PHP hiển thị phiên bản 8.4.x.
- [ ] PHP chạy từ `C:\php84\php.exe`.
- [ ] PHP đọc `C:\php84\php.ini`.
- [ ] Các PHP extension cần thiết đã bật.
- [ ] Composer hoạt động và nhận PHP 8.4.
- [ ] Các kiểm tra quan trọng của `composer diagnose` là `OK`.
- [ ] MySQL 8.x đã cài.
- [ ] MySQL service đang chạy.
- [ ] `mysql` hoạt động trong PowerShell.
- [ ] Kết nối MySQL thành công.
- [ ] `SELECT VERSION()` và `SHOW DATABASES` chạy thành công.
- [ ] Node.js hoạt động.
- [ ] npm hoạt động.
- [ ] Git hoạt động.
- [ ] Git đã có username và email phù hợp.

Khi toàn bộ checklist hoàn thành, xác nhận:

```text
LOCAL ENVIRONMENT READY
```

Chỉ sau xác nhận này mới bắt đầu Giai đoạn 1 — Khởi tạo Project.

---

## 11. Môi trường đã xác minh ngày 24/08/2026

Các phiên bản dưới đây là kết quả xác minh trên máy local tại thời điểm hoàn
thành Giai đoạn 0; không phải yêu cầu khóa cứng cho mọi máy ngoài phạm vi
baseline major/minor đã chốt:

```text
PHP:      8.4.24
Composer: 2.10.2
MySQL:    8.0.46 Community Server
Node.js:  24.19.0
npm:      11.17.0
Git:      2.55.0.windows.5
```

Kết quả đã xác minh:

- PHP sử dụng `C:\php84\php.exe` và `C:\php84\php.ini`;
- các PHP extensions cần thiết đã được nạp;
- Composer nhận đúng PHP 8.4 và diagnostics quan trọng đều `OK`;
- MySQL đăng nhập thành công, `SELECT VERSION()` và `SHOW DATABASES` chạy được;
- Node.js, npm và Git hoạt động trong PowerShell.
