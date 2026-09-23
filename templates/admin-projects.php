<?php defined('ABSPATH') || exit; ?>
<div class="wrap vg-admin" dir="rtl">
  <div class="vg-admin-head">
    <div><h1>وترا گانت</h1><p>مدیریت پروژه‌ها، تقویم کاری و کدهای کوتاه</p></div>
    <button type="button" class="button button-primary vg-project-add">افزودن پروژه</button>
  </div>
  <div class="notice vg-admin-notice" hidden><p></p></div>
  <div class="vg-project-loading">در حال دریافت پروژه‌ها…</div>
  <div class="vg-project-empty" hidden><h2>هنوز پروژه‌ای ساخته نشده است</h2><p>برای شروع، اولین پروژه را ایجاد کنید.</p></div>
  <div class="vg-project-table-wrap" hidden>
    <table class="widefat striped vg-project-table">
      <thead><tr><th>عنوان</th><th>شروع</th><th>فعالیت‌ها</th><th>کد کوتاه</th><th>عملیات</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
  <dialog class="vg-project-dialog" aria-labelledby="vg-project-dialog-title">
    <form class="vg-project-form">
      <h2 id="vg-project-dialog-title">پروژه</h2>
      <label>عنوان پروژه<input name="title" maxlength="255" required></label>
      <label>تاریخ شروع شمسی<input name="start_jalali" dir="ltr" placeholder="1405/06/29" required data-jalali-picker></label>
      <fieldset><legend>روزهای کاری</legend><div class="vg-workdays">
        <label><input type="checkbox" name="workdays" value="6"> شنبه</label>
        <label><input type="checkbox" name="workdays" value="0"> یکشنبه</label>
        <label><input type="checkbox" name="workdays" value="1"> دوشنبه</label>
        <label><input type="checkbox" name="workdays" value="2"> سه‌شنبه</label>
        <label><input type="checkbox" name="workdays" value="3"> چهارشنبه</label>
        <label><input type="checkbox" name="workdays" value="4"> پنجشنبه</label>
        <label><input type="checkbox" name="workdays" value="5"> جمعه</label>
      </div></fieldset>
      <label>تعطیلات شمسی <small>هر تاریخ در یک خط</small><textarea name="holidays_jalali" rows="5" dir="ltr" placeholder="1405/01/01"></textarea></label>
      <p class="vg-holiday-tools"><button type="button" class="button vg-holidays-load">بارگذاری خودکار تعطیلات رسمی</button><small>تعطیلات رسمی ایران بر اساس سال شروع پروژه افزوده می‌شود.</small></p>
      <p class="vg-form-error" role="alert"></p>
      <div class="vg-form-actions"><button type="submit" class="button button-primary">ذخیره</button><button type="button" class="button vg-project-cancel">انصراف</button></div>
    </form>
  </dialog>
</div>
