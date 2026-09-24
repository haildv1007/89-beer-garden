# 89 Beer Garden — Brand Interface Audit & Redesign Contract

## Phạm vi

Vòng này áp dụng cho Customer/Public layer đã triển khai trong Phase 5.1. POS,
Kitchen và Admin không nằm trong phạm vi. Business rules, authorization,
Dining Session context, money calculation, localization và route contracts
được giữ nguyên.

## Kết quả audit baseline 5.1

- Homepage vẫn lặp công thức eyebrow, tiêu đề, mô tả và card ở nhiều section.
- Primary CTA trong hero là xem menu, chưa đúng hierarchy của Brand page.
- Navigation dùng nhãn context “Khách hàng” thay vì information architecture
  công khai và chưa cho người dùng đi thẳng đến phần Không gian/Về 89.
- Display font dựa vào Arial Narrow/Impact và body dựa vào Inter fallback nhưng
  không có font asset; kết quả phụ thuộc máy và dễ mang cảm giác template.
- Radius 16–24px, shadow và card surface được dùng rộng hơn Constitution.
- Menu mở đầu bằng một khối banner bo tròn lớn; search, filter và category cạnh
  tranh nhau thay vì tạo một đường scan rõ.
- Food Card đặt category/status/detail link trước hoặc cạnh primary ordering
  action, chưa tuân theo thứ tự Image → Name → Description → Price → Add.
- Dữ liệu demo thiếu ảnh khiến placeholder màu trở thành nguồn màu chính, trái
  với nguyên tắc photography owns emotion.
- Reservation copy/CTA được lặp ở cuối homepage và footer.

## Page-specific design contract

### Homepage — Brand Experience

- User intent: hiểu 89 là nơi hẹn cho nhiều cuộc vui, xem món và giữ bàn.
- Primary action: Đặt bàn.
- Secondary action: Xem thực đơn.
- Content priority: Food → People → Atmosphere → Convenience.
- Rhythm: immersive → compact → dense → immersive → compact.
- Composition: transparent brand header trên hero full viewport, brand story
  typography-led, ba món showcase không card chrome, atmosphere full-bleed,
  ba brand pillars và reservation close. Category/search/process không xuất
  hiện trên homepage vì thuộc Food Commerce Experience.
- Anti-AI gate: không copy panel có side stripe, glassmorphism, card lồng card,
  gradient trang trí, shadow nặng hay lặp scaffold heading + description +
  card. Ảnh AI hiện tại chỉ là placeholder tạm thời và phải
  được thay bằng ảnh thật khi có asset.

### Menu — Food Commerce Experience

- User intent: tìm món, scan giá/tình trạng và thêm món khi có Dining Context.
- Primary action: Thêm món / xem món đã chọn khi ordering khả dụng.
- Secondary action: xem chi tiết khi đang duyệt menu công khai.
- Composition: compact introduction, search, sticky horizontal categories,
  catalogue grid; không dùng marketing hero.
- Food Card contract: Image → Food name → factual description → Price → Add.
- Availability luôn có text, add action có accessible label chứa tên món.

## Token direction

- Neutral: paper cream, cát ấm và charcoal pha xanh; ảnh là nguồn màu
  phong phú nhất.
- Brand: deep garden green; beer amber cho CTA/active state; warm red chỉ dùng
  cho promotion hoặc warning.
- Radius: 4–8px cho phần lớn component, pill chỉ dùng cho category/status.
- Shadow: rất nhẹ trên card và sticky shell; không tạo surface nổi kiểu app.
- Typography: Playfair Display cho brand headings và Be Vietnam Pro cho reading/UI.
  Food Commerce dùng sans cho tên, giá và action để scan nhanh.
- Spacing: 4, 8, 12, 16, 24, 32, 48, 64, 96 và 128.

## Verification contract

- Breakpoints: 375, 768, 1024 và 1440px.
- Kiểm tra hierarchy, CTA, category navigation, touch target, sticky overlap,
  VI/EN/ZH reflow, focus order, reduced motion và horizontal overflow.
- Dữ liệu thật được ưu tiên; không thêm rating, review, địa chỉ, giờ mở cửa,
  promotion hoặc business claim chưa có nguồn.
