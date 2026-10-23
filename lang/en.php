<?php

return [
    'driver.name' => 'SADAD (Bank Melli)',
    'service.name' => 'SADAD (Bank Melli)',
    'fields.apiKey' => 'Terminal ID',
    'fields.apiKey_help' => 'Your SADAD terminal id.',
    'fields.merchantId' => 'Merchant ID',
    'fields.merchantId_help' => 'Merchant id from your SADAD contract.',
    'fields.terminalKey' => 'Terminal Key',
    'fields.terminalKey_help' => 'Base64-encoded 3DES terminal key from SADAD.',
    'messages.SADAD requires a numeric order id.' => 'SADAD requires a numeric order id.',
    'messages.SADAD requires a callback URL.' => 'SADAD requires a callback URL.',
    'messages.SADAD requires a merchant id.' => 'SADAD requires a merchant id.',
    'messages.SADAD requires a terminal key.' => 'SADAD requires a terminal key.',
    'messages.SADAD terminal key must be base64-encoded 3DES key data.' => 'SADAD terminal key must be base64-encoded 3DES key data.',
    'messages.SADAD signing failed — the terminal key is not a valid 3DES key.' => 'SADAD signing failed — the terminal key is not a valid 3DES key.',
    'messages.SADAD invalid Callback payload.' => 'SADAD invalid Callback payload.',
    'messages.Amount must be greater than zero.' => 'Amount must be greater than zero.',
    'messages.SADAD verified amount mismatch.' => 'SADAD verified amount mismatch.',
];
