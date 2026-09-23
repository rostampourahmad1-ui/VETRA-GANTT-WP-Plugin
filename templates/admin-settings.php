<?php defined('ABSPATH') || exit; ?>
<div class="wrap vg-settings" dir="rtl">
  <h1>تنظیمات وترا گانت</h1>
  <p>تنظیمات پیش‌فرض نمایش گانت و رابط کاربری را مدیریت کنید.</p>
  <form method="post" action="options.php">
    <?php settings_fields('vg_settings'); do_settings_sections('vetra-gantt-settings'); submit_button('ذخیره تنظیمات'); ?>
  </form>
</div>
