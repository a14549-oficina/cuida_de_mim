<?php
/*Cuida de Mim — Função de Envio WhatsApp via Twilio 
 * Não usa o SDK completo (evita Composer no XAMPP).
 * Usar diretamente a API REST da Twilio com cURL — funciona em qualquer XAMPP.*/

require_once __DIR__ . '/twilio_config.php';

/* Envia uma mensagem WhatsApp via Twilio REST API.
 * string $telefone  Número no formato internacional SEM espaços: +351912345678
 * string $mensagem  Texto da mensagem
 * array  ['sucesso' => bool, 'sid' => string|null, 'erro' => string|null]*/

function enviar_whatsapp(string $telefone, string $mensagem): array
{
    // Garantir que o número está no formato correto
    $telefone = limpar_telefone($telefone);
    if (!$telefone) {
        return ['sucesso' => false, 'sid' => null, 'erro' => 'Número de telefone inválido.'];
    }

    $url  = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
    $para = 'whatsapp:' . $telefone;

    $dados = http_build_query([
        'To'   => $para,
        'From' => TWILIO_WHATSAPP_FROM,
        'Body' => $mensagem,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $dados,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => TWILIO_SID . ':' . TWILIO_TOKEN,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resposta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_erro = curl_error($ch);
    curl_close($ch);

    if ($curl_erro) {
        return ['sucesso' => false, 'sid' => null, 'erro' => 'cURL erro: ' . $curl_erro];
    }

    $json = json_decode($resposta, true);

    if ($http_code === 201 && isset($json['sid'])) {
        return ['sucesso' => true, 'sid' => $json['sid'], 'erro' => null];
    }

    $mensagem_erro = $json['message'] ?? ('HTTP ' . $http_code . ' — ' . $resposta);
    return ['sucesso' => false, 'sid' => null, 'erro' => $mensagem_erro];
}

/*Limpa e valida o número de telefone.
 *Aceita: 912345678, +351912345678, 00351912345678, 351 912 345 678
 *Retorna sempre: +351912345678  (ou false se inválido)*/

function limpar_telefone(string $numero): string|false
{
    // Remove tudo exceto dígitos e o + inicial
    $numero = preg_replace('/[^0-9+]/', '', $numero);

    // Se começar com 00, troca por +
    if (str_starts_with($numero, '00')) {
        $numero = '+' . substr($numero, 2);
    }

    // Se for número português sem prefixo (9 dígitos, começa por 9 o)
    if (preg_match('/^[923]\d{8}$/', $numero)) {
        $numero = '+351' . $numero;
    }

    // Valida formato E.164 básico
    if (!preg_match('/^\+[1-9]\d{7,14}$/', $numero)) {
        return false;
    }

    return $numero;
}
