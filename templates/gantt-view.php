<?php defined('ABSPATH') || exit; ?>
<section class="vg-root" dir="rtl" data-project="<?php echo esc_attr($project_id); ?>" style="--vg-height:<?php echo esc_attr($height); ?>px">
  <header class="vg-toolbar">
    <div class="vg-brand"><strong><?php echo esc_html($project['title']); ?></strong><small>وترا گانت</small></div>
    <div class="vg-controls">
      <button type="button" data-view="gantt" class="vg-active">گانت</button><button type="button" data-view="calendar">تقویم</button>
      <select class="vg-zoom" aria-label="مقیاس زمانی"><option value="day">روز</option><option value="week" selected>هفته</option><option value="month">ماه</option></select>
      <button type="button" class="vg-export">خروجی CSV</button>
      <button type="button" class="vg-add">+ فعالیت</button>
    </div>
  </header>
  <p class="vg-status" role="status" aria-live="polite"></p>
  <p class="vg-grid-tip">ورود سریع: در ردیف آخر (ردیف جدید) تایپ کنید و Enter بزنید؛ برای ویرایش هر سلول روی آن کلیک/دوبار کلیک کنید و با Enter ذخیره می‌شود. تاریخ‌ها شمسی‌اند (از راه دراپ‌داون یا تایپ). ✎ = تنظیمات پیشرفته (سرگروه، نوع، حذف).</p>
  <div class="vg-gantt">
    <div class="vg-grid">
      <div class="vg-grid-head"><span>WBS</span><span>نام فعالیت</span><span>نوع</span><span>مدت</span><span>شروع</span><span>پایان</span><span>پیش‌نیاز</span><span>سهم ٪</span><span>عملیات</span></div>
      <div class="vg-grid-body"></div>
    </div>
    <div class="vg-divider" role="separator" aria-label="تغییر عرض جدول" tabindex="0"></div>
    <div class="vg-chart"><div class="vg-chart-head"></div><div class="vg-chart-body"><div class="vg-chart-content"></div></div></div>
  </div>
  <div class="vg-calendar" hidden></div>
  <dialog class="vg-dialog" aria-labelledby="vg-dialog-title">
    <form class="vg-form">
      <h2 id="vg-dialog-title">فعالیت</h2>
      <label>نام فعالیت<input name="title" maxlength="255" required></label>
      <label>سرگروه<select name="parent_id"></select></label>
      <div class="vg-fields"><label>نوع<select name="task_type"><option value="task">فعالیت</option><option value="summary">خلاصه</option><option value="milestone">نقطه عطف</option></select></label><label>زمان‌بندی<select name="schedule_mode"><option value="auto">خودکار</option><option value="manual">دستی</option></select></label></div>
      <div class="vg-fields"><label>مدت (روز کاری)<input name="duration" type="number" min="0" max="36500" value="1" required></label><label>سهم از کل ٪<input name="weight_percent" type="number" min="0" max="100" step="0.001" value="0"></label></div>
      <div class="vg-fields"><label>شروع شمسی (۱۴۰۵/۰۶/۲۹)<input name="start_jalali" dir="ltr" placeholder="1405/06/29" data-jalali-picker></label><label>پایان شمسی (دستی)<input name="end_jalali" dir="ltr" placeholder="1405/07/01" data-jalali-picker></label></div>
      <label>پیش‌نیازها (مانند 1FS, 2SS+3, 3FF-2)<input name="predecessors" dir="ltr" placeholder="1FS, 2SS+3"></label>
      <p class="vg-hint">اطلاعات را مستقیم در ردیف‌ها وارد کنید؛ با Enter ذخیره و به ردیف بعد می‌رود. تاریخ‌ها شمسی و از راه دراپ‌داون یا تایپ معتبرند. در حالت خودکار پایان محاسبه می‌شود؛ برای پایان دستی، تاریخ پایان را بنویسید. دکمه ✎ برای انتخاب سرگروه، نوع و حذف استفاده می‌شود.</p>
      <div class="vg-actions"><button type="submit">ذخیره</button><button type="button" class="vg-cancel">انصراف</button><button type="button" class="vg-delete" hidden>حذف فعالیت</button></div>
    </form>
  </dialog>
</section>
