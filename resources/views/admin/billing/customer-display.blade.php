<!doctype html>
<html lang="vi">
@php
    $displaySettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
    $displaySiteName = $displaySettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden';
@endphp
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Màn hình thanh toán - {{ $displaySiteName }}</title>
    <style>
        *{box-sizing:border-box}[hidden]{display:none!important}body{margin:0;background:#062f23;color:#fff;font-family:Arial,sans-serif;min-height:100vh;display:grid;place-items:center;padding:3vh 3vw}
        main{width:min(1280px,94vw);text-align:center}.brand{font-size:24px;font-weight:800;margin-bottom:18px}.panel{display:grid;align-items:center;min-height:min(680px,82vh);background:#fff;color:#082c22;border-radius:24px;padding:clamp(26px,4vw,52px);box-shadow:0 24px 60px #001b1480}
        .idle h1,.state h1{font-size:clamp(34px,4vw,52px);margin:10px 0}.idle p,.state p{color:#61736b;font-size:20px}.payment{display:grid;grid-template-columns:minmax(380px,48%) minmax(360px,1fr);align-items:center;gap:clamp(38px,6vw,86px);text-align:left}
        .qr-frame{position:relative;display:grid;place-items:center;width:100%;max-width:500px;aspect-ratio:1;margin:auto;border:2px dashed #c7d8cf;border-radius:22px;background:#f3f8f5;overflow:hidden}.qr-frame img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#fff;opacity:0;transition:opacity .15s}.qr-frame img.is-loaded{opacity:1}.qr-placeholder{display:grid;place-items:center;gap:12px;color:#688078}.qr-placeholder i{display:grid;width:92px;height:92px;place-items:center;border:5px solid #b8ccc1;border-radius:18px;font-size:42px;font-style:normal}.qr-placeholder b{font-size:18px}
        .payment h1{font-size:clamp(34px,3.2vw,50px);margin:8px 0 12px}.payment [data-table]{color:#b56d00;font-size:21px;font-weight:800}.amount{display:block;color:#08724d;font-size:clamp(44px,4.5vw,68px);margin:10px 0 22px}.meta{display:grid;gap:16px;margin-top:18px}.meta span{color:#687a72;font-size:17px}.meta b{display:block;color:#0b3025;font-size:24px;margin-top:5px;word-break:break-all}.countdown{margin-top:28px;color:#b56d00;font-size:22px}.state{padding:70px 20px}.paid h1{color:#08724d}.expired h1{color:#c0392b}
        @media(max-width:800px){body{padding:20px}.panel{min-height:auto}.payment{grid-template-columns:1fr;text-align:center}.qr-frame{max-width:390px}.amount{font-size:42px}}
    </style>
</head>
<body><main><div class="brand">{{ $displaySiteName }} · Thanh toán</div><section class="panel">
    <div class="idle" data-idle><h1>Sẵn sàng nhận thanh toán</h1><p>QR của hóa đơn sẽ tự động hiển thị tại đây.</p></div>
    <div class="payment" data-payment hidden><div class="qr-frame"><div class="qr-placeholder"><i>▦</i><b data-qr-message>Đang tải mã QR...</b></div><img data-qr alt=""></div><div><span data-table></span><h1>Quét mã để thanh toán</h1><strong class="amount" data-amount></strong><div class="meta"><span>Mã đơn / hóa đơn<b data-bill></b></span><span>Nội dung chuyển khoản<b data-reference></b></span></div><div class="countdown">Còn <b data-countdown>30:00</b></div></div></div>
    <div class="state paid" data-paid hidden><h1>Thanh toán thành công</h1><p>Nhà hàng đã nhận được tiền. Xin cảm ơn quý khách!</p></div>
    <div class="state expired" data-expired hidden><h1>Mã thanh toán đã hết hạn</h1><p>Vui lòng báo nhân viên tạo mã thanh toán mới.</p></div>
</section></main>
<script>
(()=>{const url=@json(route($statusRoute));const els={idle:document.querySelector('[data-idle]'),payment:document.querySelector('[data-payment]'),paid:document.querySelector('[data-paid]'),expired:document.querySelector('[data-expired]')};const qr=document.querySelector('[data-qr]');const qrMessage=document.querySelector('[data-qr-message]');let expires=0,lastQr='',currentState='';
const show=(key)=>{if(currentState===key)return;currentState=key;Object.entries(els).forEach(([name,el])=>el.hidden=name!==key)};const money=n=>new Intl.NumberFormat('vi-VN').format(n)+' ₫';
const countdown=()=>{if(!expires||els.payment.hidden)return;const left=Math.max(0,Math.ceil((expires-Date.now())/1000));document.querySelector('[data-countdown]').textContent=String(Math.floor(left/60)).padStart(2,'0')+':'+String(left%60).padStart(2,'0');if(left===0){expires=0;show('expired')}};
const clear=()=>{qr.removeAttribute('src');qr.classList.remove('is-loaded');qrMessage.textContent='Đang tải mã QR...';document.querySelector('[data-amount]').textContent='';document.querySelector('[data-bill]').textContent='';document.querySelector('[data-reference]').textContent=''};
qr.addEventListener('load',()=>{qr.classList.add('is-loaded');qrMessage.textContent='Mã QR thanh toán'});qr.addEventListener('error',()=>{qr.classList.remove('is-loaded');qrMessage.textContent='Không tải được QR · đang thử lại';lastQr=''});
const poll=async()=>{try{const response=await fetch(url,{cache:'no-store',headers:{Accept:'application/json'}});if(!response.ok)return;const data=await response.json();if(!data.active){clear();show('idle');return}if(data.paid){clear();show('paid');return}if(data.expired){expires=0;lastQr='';clear();show('expired');return}show('payment');expires=data.expires_at;document.querySelector('[data-table]').textContent=data.table||'';document.querySelector('[data-amount]').textContent=money(data.amount);document.querySelector('[data-bill]').textContent=data.bill_code;document.querySelector('[data-reference]').textContent=data.reference;if(lastQr!==data.qr_url){qr.classList.remove('is-loaded');qrMessage.textContent='Đang tải mã QR...';qr.src=data.qr_url;lastQr=data.qr_url}countdown()}catch(_){}};
poll();setInterval(poll,2000);setInterval(countdown,1000);
})();
</script></body></html>
