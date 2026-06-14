<?php

require_once __DIR__ . '/whatsapp_helper.php';

$numero_teste = '+351925911895';   // o número autorizado no Sandbox


echo "<pre style='font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;border-radius:8px'>";
echo "Cuida de Mim — Teste WhatsApp\n";
echo str_repeat('─', 50) . "\n";
echo "A enviar para: " . $numero_teste . "\n";
echo "De: " . TWILIO_WHATSAPP_FROM . "\n";
echo "SID: " . TWILIO_SID . "\n";
echo str_repeat('─', 50) . "\n";

$resultado = enviar_whatsapp($numero_teste,
    "*Cuida de Mim — Teste de Ligação*\n\n"
    . "Olá! \n\n"
    . "Se recebeste esta mensagem, o sistema de lembretes WhatsApp está a funcionar correctamente! \n\n"
    . "Vais receber:\n"
    . "  • Lembretes de medicamentos\n"
    . "  • Alertas de consultas\n"
    . "  • Notificações gerais\n\n"
    . "_App Cuida de Mim_"
);

if ($resultado['sucesso']) {
    echo "SUCESSO!\n";
    echo "   SID da mensagem: " . $resultado['sid'] . "\n";
    echo "\n Verifica o teu WhatsApp!\n";
} else {
    echo "ERRO!\n";
    echo "   " . $resultado['erro'] . "\n";
    echo "\nDicas:\n";
    echo "   1. Verifica se o número está autorizado no Sandbox da Twilio\n";
    echo "   2. Envia 'join <código>' para +14155238886 no WhatsApp\n";
    echo "   3. Confirma o Account SID e Auth Token em 2_twilio_config.php\n";
}

echo "\n" . str_repeat('─', 50) . "\n";
echo "APAGA ESTE FICHEIRO DEPOIS DOS TESTES!\n";
echo "</pre>";
