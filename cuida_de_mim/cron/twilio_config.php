<?php
/*Cuida de Mim — Configuração Twilio WhatsApp*/

// CREDENCIAIS TWILIO 
define('TWILIO_SID',   'SEU_TWILIO_SID_AQUI');
define('TWILIO_TOKEN', 'SEU_TWILIO_TOKEN_AQUI');

// Número do WhatsApp Sandbox da Twilio (não alteres enquanto estiveres em modo teste)
define('TWILIO_WHATSAPP_FROM',  'whatsapp:+14155238886');

// MODO 
// true  = ambiente de testes (sandbox) — só envia para números autorizados
// false = produção (requer Business Verification na Twilio)
define('TWILIO_SANDBOX',        true);

// JANELA DO CRON 
// O cron corre de 30 em 30 minutos, portanto a janela é 30 minutos
define('CRON_JANELA_MIN',       30);
