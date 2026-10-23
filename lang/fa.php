<?php

return [
    'driver.name' => 'سداد (بانک ملی)',
    'service.name' => 'سداد (بانک ملی)',
    'fields.apiKey' => 'شناسه ترمینال',
    'fields.apiKey_help' => 'شناسه ترمینال سداد.',
    'fields.merchantId' => 'شناسه مرچنت',
    'fields.merchantId_help' => 'شناسه مرچنت از قرارداد سداد.',
    'fields.terminalKey' => 'کلید ترمینال',
    'fields.terminalKey_help' => 'کلید 3DES ترمینال به صورت Base64.',
    'messages.SADAD requires a numeric order id.' => 'سداد نیازمند شناسه سفارش عددی است.',
    'messages.SADAD requires a callback URL.' => 'سداد نیازمند آدرس بازگشت است.',
    'messages.SADAD requires a merchant id.' => 'سداد نیازمند شناسه مرچنت است.',
    'messages.SADAD requires a terminal key.' => 'سداد نیازمند کلید ترمینال است.',
    'messages.SADAD terminal key must be base64-encoded 3DES key data.' => 'کلید ترمینال سداد باید داده های کلید 3DES به صورت Base64 باشد.',
    'messages.SADAD signing failed — the terminal key is not a valid 3DES key.' => 'امضای سداد ناموفق بود؛ کلید ترمینال معتبر نیست.',
    'messages.SADAD invalid Callback payload.' => 'پاسخ بازگشتی سداد نامعتبر است.',
    'messages.Amount must be greater than zero.' => 'مبلغ باید بزرگ‌تر از صفر باشد.',
    'messages.SADAD verified amount mismatch.' => 'مبلغ تایید شده سداد با مبلغ ثبت‌شده همخوانی ندارد.',
];
