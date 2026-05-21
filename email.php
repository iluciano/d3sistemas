<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Metodo nao permitido.';
    exit;
}

const EMAIL_DESTINATARIO = 'contato@igorluciano.com.br';
const EMAIL_REMETENTE = 'contato@igorluciano.com.br';
const NOME_REMETENTE = 'D3 Development, Designer, Dreams';

require_once __DIR__ . '/config.php';

function clean_text($value)
{
    return trim(strip_tags((string) $value));
}

function clean_header_value($value)
{
    return str_replace(array("\r", "\n"), '', clean_text($value));
}

function post_value(array $names)
{
    foreach ($names as $name) {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
    }

    return '';
}

function encode_subject($subject)
{
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function encode_header_name($name)
{
    return '=?UTF-8?B?' . base64_encode($name) . '?=';
}

$nome = clean_header_value(post_value(array('nome', 'txtNome', 'name')));
$email = clean_header_value(post_value(array('email', 'txtEmail')));
$mensagem = clean_text(post_value(array('mensagem', 'txtMsg', 'message')));

$recaptchaToken = isset($_POST['g-recaptcha-response']) ? trim($_POST['g-recaptcha-response']) : '';
if ($recaptchaToken === '') {
    http_response_code(400);
    echo 'Por favor, confirme que você não é um robô.';
    exit;
}

$verifyContext = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => RECAPTCHA_SECRET,
            'response' => $recaptchaToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
    ],
]);
$verifyResult = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $verifyContext);
$verifyJson   = $verifyResult ? json_decode($verifyResult, true) : null;

if (!$verifyJson || empty($verifyJson['success'])) {
    http_response_code(400);
    echo 'Verificação do CAPTCHA falhou. Tente novamente.';
    exit;
}

if ($nome === '' || $email === '' || $mensagem === '') {
    http_response_code(400);
    echo 'Preencha todos os campos obrigatorios.';
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Informe um e-mail valido.';
    exit;
}

$nomeHtml = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$emailHtml = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$mensagemHtml = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

$assunto = encode_subject('Contato do site - Igor Luciano');
$corpo = '<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contato do site</title>
</head>
<body>
    <p>Email enviado pelo site igorluciano.com.br.</p>
    <p><strong>Nome:</strong> ' . $nomeHtml . '</p>
    <p><strong>E-mail:</strong> ' . $emailHtml . '</p>
    <p><strong>Mensagem:</strong></p>
    <p>' . $mensagemHtml . '</p>
</body>
</html>';

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . encode_header_name(NOME_REMETENTE) . ' <' . EMAIL_REMETENTE . '>',
    'Reply-To: ' . encode_header_name($nome) . ' <' . $email . '>',
    'Return-Path: ' . EMAIL_REMETENTE,
    'X-Mailer: PHP/' . phpversion(),
);

$headers = implode("\r\n", $headers);
$envelopeSender = '-f' . EMAIL_REMETENTE;

if (mail(EMAIL_DESTINATARIO, $assunto, $corpo, $headers, $envelopeSender)) {
    echo 'Mensagem enviada com sucesso.';
    exit;
}

http_response_code(500);
echo 'Nao foi possivel enviar a mensagem. Verifique se o e-mail contato@igorluciano.com.br esta ativo na hospedagem.';
