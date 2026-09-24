@extends('layouts.admin')
@section('title', 'Báo cáo vận hành')
@section('content')
<div class="operations-report">
 <div class="report-toolbar">
  @include('admin.reports._tabs')
  <form class="report-filter" method="GET"><label class="report-filter-period"><span class="visually-hidden">Khoảng thời gian</span><select name="preset" aria-label="Khoảng thời gian"><option value="today" @selected($preset==='today')>Hôm nay</option><option value="7" @selected($preset==='7')>7 ngày</option><option value="14" @selected($preset==='14')>14 ngày</option><option value="custom" @selected($preset==='custom')>Tùy chọn</option></select></label><label class="report-filter-date"><span class="visually-hidden">Từ ngày</span><input type="date" name="from" aria-label="Từ ngày" value="{{ $preset==='custom'?$from->toDateString():'' }}" @disabled($preset!=='custom')></label><label class="report-filter-date"><span class="visually-hidden">Đến ngày</span><input type="date" name="to" aria-label="Đến ngày" value="{{ $preset==='custom'?$to->toDateString():'' }}" @disabled($preset!=='custom')></label><button>Áp dụng</button></form>
 </div>

 @php
  $cards=[['Doanh thu thực thu','revenue','₫'],['Hóa đơn đã thanh toán','invoices',''],['Trung bình hóa đơn','average_invoice','₫'],['Lượt khách tại bàn','guests',''],['Doanh thu/khách','revenue_per_guest','₫']];
  $comparisonLabel='so với kỳ trước';
 @endphp
 <div class="report-kpis">
  @foreach($cards as [$label,$key,$unit])
   @php($m=$summary[$key])
   <article>
    <span>{{ $label }}</span>
    <strong>{{ number_format($m['value']) }} {{ $unit }}</strong>
    @if($preset !== 'today')
     <small class="{{ $m['change']===null?'':($m['change']>0?'up':($m['change']<0?'down':'')) }}">
      @if($m['change'] === null)
       <em>{{ ucfirst($comparisonLabel) }}</em>
      @else
       {{ $m['change']>0?'▲':($m['change']<0?'▼':'•') }} {{ abs($m['change']) }}% <em>{{ $comparisonLabel }}</em>
      @endif
     </small>
    @endif
   </article>
  @endforeach
 </div>

 <div class="report-grid wide"><section class="report-card"><div class="section-title"><div><h2>Xu hướng doanh thu</h2><p>Tiền thực thu theo {{ $preset==='today'?'giờ':'ngày' }}</p></div><strong>{{ number_format($summary['revenue']['value']) }} ₫</strong></div><div class="revenue-chart">@foreach($trend as $point)<div><i @class(['has-revenue'=>$point->amount>0]) title="{{ $point->label }}: {{ number_format($point->amount) }} ₫" style="height:{{ $point->percent }}%"></i><small>{{ $loop->count<=14||$loop->odd?$point->label:'' }}</small></div>@endforeach</div></section>
 <section class="report-card"><div class="section-title"><div><h2>Cơ cấu nguồn thu</h2><p>Doanh thu theo hình thức nhận món</p></div></div>@php($labels=['dine_in'=>'Tại bàn','pickup'=>'Nhận tại quán','delivery'=>'Giao tận nơi'])<div class="breakdown">@forelse($sourceBreakdown as $item)<div><header><span>{{ $labels[$item->name]??$item->name }}</span><strong>{{ number_format($item->amount) }} ₫</strong></header><i><b style="width:{{$item->percent}}%"></b></i><small>{{ $item->percent }}% · {{ $item->count }} hóa đơn</small></div>@empty<p>Chưa có doanh thu.</p>@endforelse</div></section></div>

 <section class="report-card report-block"><div class="section-title"><div><h2>Hiệu quả phục vụ tại bàn</h2><p>Tính theo phiên bắt đầu trong kỳ</p></div></div><div class="stat-strip">@foreach([['Tổng phiên',$sessions['total']],['Hoàn tất',$sessions['completed']],['Đang phục vụ',$sessions['active']],['Đã hủy',$sessions['cancelled']],['Khách/phiên',$sessions['average_guests']],['Thời gian TB',$sessions['average_minutes'].' phút'],['Lượt gọi/phiên',$sessions['average_rounds']],['Đổi bàn',$sessions['transfers']]] as [$label,$value])<div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>@endforeach</div></section>

 <div class="report-grid products"><section class="report-card"><div class="section-title"><div><h2>Món bán chạy</h2><p>Tại bàn và đơn ngoài quán đã thanh toán</p></div></div><div class="table-responsive"><table><thead><tr><th>Món</th><th>Danh mục</th><th>SL</th><th>Doanh thu</th></tr></thead><tbody>@forelse($topProducts as $p)<tr><td><strong>{{ $p->product_name }}</strong></td><td>{{ $p->category_name }}</td><td>{{ $p->quantity }}</td><td>{{ number_format($p->line_revenue) }} ₫</td></tr>@empty<tr><td colspan="4">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></section>
 <section class="report-card"><div class="section-title"><div><h2>Món bị hủy</h2><p>Giá trị đã loại khỏi đơn</p></div></div><table><thead><tr><th>Món</th><th>SL</th><th>Giá trị</th></tr></thead><tbody>@forelse($cancelledProducts as $p)<tr><td>{{ $p->product_name }}</td><td>{{ $p->quantity }}</td><td>{{ number_format($p->value) }} ₫</td></tr>@empty<tr><td colspan="3">Không có món bị hủy.</td></tr>@endforelse</tbody></table></section></div>

 <div class="report-grid"><section class="report-card"><div class="section-title"><div><h2>Hiệu quả bàn</h2><p>Top bàn theo doanh thu thực thu</p></div></div><div class="table-responsive"><table><thead><tr><th>Bàn</th><th>Phiên</th><th>Khách</th><th>Doanh thu</th><th>TB/phiên</th></tr></thead><tbody>@forelse($topTables as $t)<tr><td><strong>{{ $t->name?:$t->code }}</strong></td><td>{{ $t->sessions }}</td><td>{{ $t->guests }}</td><td>{{ number_format($t->revenue) }} ₫</td><td>{{ number_format($t->average) }} ₫</td></tr>@empty<tr><td colspan="5">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></section>
 <section class="report-card"><div class="section-title"><div><h2>Đặt bàn</h2><p>Theo ngày khách dự kiến đến</p></div><strong>{{ $reservations['arrival_rate'] }}% đến</strong></div><div class="mini-kpis">@foreach([['Tổng yêu cầu',$reservations['total']],['Đã đến',$reservations['arrived']],['Trễ',$reservations['late']],['Không đến',$reservations['no_show']],['Hủy/từ chối',$reservations['cancelled']]] as [$l,$v])<div><span>{{ $l }}</span><strong>{{ $v }}</strong></div>@endforeach</div></section></div>

 <div class="report-grid products"><section class="report-card"><div class="section-title"><div><h2>Đơn ngoài quán</h2><p>Số đơn theo ngày đặt, tiền theo ngày thanh toán</p></div><strong>{{ number_format($fulfillment['revenue']) }} ₫</strong></div><div class="mini-kpis">@foreach([['Tổng đơn',$fulfillment['total']],['Nhận tại quán',$fulfillment['pickup']],['Giao tận nơi',$fulfillment['delivery']],['Đã thanh toán',$fulfillment['paid']],['Đơn trung bình',number_format($fulfillment['average']).' ₫'],['Phí giao hàng',number_format($fulfillment['shipping_fees']).' ₫']] as [$l,$v])<div><span>{{ $l }}</span><strong>{{ $v }}</strong></div>@endforeach</div></section>
 <section class="report-card"><div class="section-title"><div><h2>Phương thức thanh toán</h2><p>Tiền đã thu trong kỳ</p></div><a href="{{ route('admin.reports.payments') }}">Lịch sử →</a></div>@php($methods=['cash'=>'Tiền mặt','bank_transfer'=>'Chuyển khoản','other'=>'Khác'])<div class="breakdown">@forelse($paymentBreakdown as $item)<div><header><span>{{ $methods[$item->name]??$item->name }}</span><strong>{{ number_format($item->amount) }} ₫</strong></header><i><b style="width:{{$item->percent}}%"></b></i><small>{{ $item->percent }}% · {{ $item->count }} giao dịch</small></div>@empty<p>Chưa có giao dịch.</p>@endforelse</div>@if($reconciliationCount)<a class="review-link" href="{{ route('admin.reports.payments',['tab'=>'reconciliation']) }}">{{ $reconciliationCount }} giao dịch cần đối soát →</a>@endif</section></div>
</div>
<script>
 const overviewPreset = document.querySelector('.operations-report .report-filter select[name="preset"]');
 overviewPreset?.addEventListener('change', () => {
  document.querySelectorAll('.operations-report .report-filter-date input').forEach(input => {
   input.disabled = overviewPreset.value !== 'custom';
  });
 });
</script>
@endsection
