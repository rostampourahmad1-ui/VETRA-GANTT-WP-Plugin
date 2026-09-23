<?php defined('ABSPATH') || exit; ?>
<section class="vg-root" dir="rtl" data-project="<?php echo esc_attr($project_id); ?>" style="--vg-height:<?php echo esc_attr($height); ?>px">
  <header class="vg-toolbar">
    <div class="vg-brand"><strong><?php echo esc_html($project['title']); ?></strong><small>وترا گانت</small></div>
    <nav class="vg-tabs" aria-label="نماهای پروژه">
      <button type="button" data-view="wbs">WBS</button>
      <button type="button" data-view="gantt">Gantt</button>
      <button type="button" data-view="integrated" class="vg-active">ادغام</button>
      <button type="button" data-view="calendar">تقویم</button>
      <button type="button" data-view="timeline">Timeline</button>
    </nav>
    <div class="vg-controls">
      <select class="vg-zoom" aria-label="مقیاس زمانی"><option value="day">روز</option><option value="week" selected>هفته</option><option value="month">ماه</option></select>
      <div class="vg-io-wrap"><button type="button" class="vg-io-toggle" data-io-toggle="import" aria-expanded="false">⇧ درون‌ریزی</button><div class="vg-io-menu" data-io-panel="import" hidden><strong>درون‌ریزی</strong><button type="button" data-import-trigger="excel">Excel / CSV</button><button type="button" data-import-trigger="mpp">MPP / XML</button><input type="file" data-import-file="excel" accept=".csv,.xls" hidden><input type="file" data-import-file="mpp" accept=".mpp,.xml" hidden><small>فایل XML خروجی Microsoft Project مستقیم خوانده می‌شود؛ MPP باینری باید ابتدا به XML تبدیل شود.</small></div></div>
      <button type="button" class="vg-add">+ فعالیت</button>
    </div>
  </header>
  <p class="vg-status" role="status" aria-live="polite"></p>
  <p class="vg-grid-tip">ورود سریع: در ردیف آخر (ردیف جدید) تایپ کنید و Enter بزنید؛ برای ویرایش هر سلول روی آن کلیک/دوبار کلیک کنید و با Enter ذخیره می‌شود. برای ساخت زیرگروه روی ↳ و برای جمع/باز کردن خلاصه روی −/＋ بزنید. WBS به‌صورت خودکار چندسطحی ساخته می‌شود.</p>
  <div class="vg-gantt">
    <div class="vg-grid">
      <div class="vg-zone-toolbar" data-zone="wbs">
        <strong>WBS / فعالیت‌ها</strong>
        <div class="vg-zone-actions">
          <button type="button" data-zone-action="zoom-out" data-zone-target="wbs" aria-label="کوچک‌نمایی WBS" title="کوچک‌نمایی">−</button>
          <button type="button" data-zone-action="zoom-in" data-zone-target="wbs" aria-label="بزرگ‌نمایی WBS" title="بزرگ‌نمایی">+</button>
          <button type="button" data-zone-action="focus" data-zone-target="wbs" aria-label="نمایش فقط WBS" title="نمایش فقط WBS">⛶</button>
        </div>
      </div>
      <div class="vg-grid-head"><span>WBS</span><span>نام فعالیت</span><span>نوع</span><span>مدت</span><span>شروع</span><span>پایان</span><span>پیش‌نیاز</span><span>سهم ٪</span><span>عملیات</span></div>
      <div class="vg-grid-body"></div>
    </div>
    <div class="vg-divider" role="separator" aria-label="تغییر عرض جدول" tabindex="0"></div>
    <div class="vg-chart">
      <div class="vg-zone-toolbar" data-zone="gantt">
        <strong>Gantt / Timeline</strong>
        <div class="vg-zone-actions">
          <button type="button" data-zone-action="zoom-out" data-zone-target="gantt" aria-label="کوچک‌نمایی نمودار" title="کوچک‌نمایی">−</button>
          <button type="button" data-zone-action="zoom-in" data-zone-target="gantt" aria-label="بزرگ‌نمایی نمودار" title="بزرگ‌نمایی">+</button>
          <button type="button" data-zone-action="focus" data-zone-target="gantt" aria-label="نمایش فقط نمودار گانت" title="نمایش فقط گانت">⛶</button>
        </div>
      </div>
      <div class="vg-chart-head"></div><div class="vg-chart-body"><div class="vg-chart-content"></div></div>
    </div>
  </div>
  <div class="vg-calendar" hidden></div>
  <div class="vg-timeline" hidden></div>
  <footer class="vg-bottom-toolbar"><span>خروجی و چاپ</span><div class="vg-io-wrap"><button type="button" class="vg-export vg-io-toggle" data-io-toggle="export" aria-expanded="false" aria-label="نمایش منوی خروجی" title="خروجی گرفتن">⇩</button><div class="vg-io-menu vg-io-menu-bottom" data-io-panel="export" hidden><strong>خروجی</strong><button type="button" data-export-target="all">Excel همه داده‌ها</button><button type="button" data-export-target="wbs">Excel فقط WBS</button><button type="button" data-export-target="gantt">Excel فقط Gantt</button><hr><button type="button" data-print-target="all">PDF / چاپ همه</button><button type="button" data-print-target="wbs">PDF / چاپ WBS</button><button type="button" data-print-target="gantt">PDF / چاپ Gantt</button></div></div></footer>
  <dialog class="vg-dialog" aria-labelledby="vg-dialog-title">
    <form class="vg-form">
      <h2 id="vg-dialog-title">فعالیت</h2>
      <label>نام فعالیت<input name="title" maxlength="255" required></label>
      <label>سرگروه / زیرگروه<select name="parent_id"></select></label>
      <div class="vg-fields"><label>نوع<select name="task_type"><option value="task">فعالیت</option><option value="summary">خلاصه</option><option value="milestone">نقطه عطف</option></select></label><label>زمان‌بندی<select name="schedule_mode"><option value="auto">خودکار</option><option value="manual">دستی</option></select></label></div>
      <div class="vg-fields"><label>مدت (روز کاری)<input name="duration" type="number" min="0" max="36500" value="1" required></label><label>سهم از کل ٪<input name="weight_percent" type="number" min="0" max="100" step="0.001" value="0"></label></div>
      <div class="vg-fields"><label>شروع شمسی (۱۴۰۵/۰۶/۲۹)<input name="start_jalali" dir="ltr" placeholder="1405/06/29" data-jalali-picker></label><label>پایان شمسی (دستی)<input name="end_jalali" dir="ltr" placeholder="1405/07/01" data-jalali-picker></label></div>
      <label>پیش‌نیازها (مانند 1FS, 2SS+3, 3FF-2)<input name="predecessors" dir="ltr" placeholder="1FS, 2SS+3"></label>
      <p class="vg-hint">اطلاعات را مستقیم در ردیف‌ها وارد کنید؛ با Enter ذخیره و به ردیف بعد می‌رود. برای ساخت زیرگروه، روی ↳ ردیف والد بزنید یا از دکمه تورفتگی استفاده کنید. WBS به‌صورت خودکار در هر عمق (۱، ۱.۱، ۱.۱.۱ و …) ساخته می‌شود.</p>
      <div class="vg-actions"><button type="submit">ذخیره</button><button type="button" class="vg-cancel">انصراف</button><button type="button" class="vg-delete" hidden>حذف فعالیت</button></div>
    </form>
  </dialog>
</section>
