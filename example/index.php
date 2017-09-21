<?php
/**
 * Exemplo de Uso para Cadastro de AuthId com Suporte a Social Login
 *
 * php -S localhost:8001
 */

print "<pre>";

// habilita todos os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/vendor/autoload.php";

$connections = [];

// Instancia a classe do SNAM
$client = new SNAMClient\SNAM([
    "app_id" =>'<app-id>',
    "app_token" => '<app-token>',
    "app" => '<app>',
    "version" => 'v2.1'
]);

//Obtem a versao, ja testando a conectividade
$r = $client->request('GET', 'version');

printf(" Teste SNAM API Versão %s <br>", $r->content());
printf("%'.80s<br>", '.');

/**
 * Conexao com Facebook
 *
 */

// Obtem a conexao com Facebook
$facebook = $client->getConnection('facebook');

// se a autenticao ainda nao foi feita, obtem a autorizacao
if (!$facebook->isOAuth()) {

    // obtem metodo de OAuth do Facebook
    $authFacebook = $facebook->getOAuth();

    // define a url de callback, o mesmo deve ser configurado em nas
    // URIs de redirecionamento do OAuth válidos em
    // https://developers.facebook.com/apps/<app id>/fb-login/settings/
    $authFacebook->setRedirectUri('http://localhost:8001/');
    
    // verifica, se o acesso nao e um retorno do Facebook, exibe a url de autenticacao
    // caso contrario registra o usurio
    if ($authFacebook->isGetCode() !== true) {
        
        printf(
            '<a href="%s"> Login Facebook </a> <br>',
            $authFacebook->getAuthorizationUrl()
        );

    } else {

        // registra o usuario autenticao
        $facebook->registerOAuth();

        // caso queira registrar uma pagina, exibe para cliente escolher qual pagina
        // e envia a mesma para registro, nesse exemplo estamos pegando a primeira
        // $accounts = $authFacebook->getAccounts();
        // $c = array_shift($accounts);
        // $facebook->registerAccount($c);

    }

} else {

    printf('Logado no Facebook como %s <br>', $facebook->owner['name']);
}

if (!empty($facebook->owner)) {
    array_push($connections, $facebook->owner);
}

// O processo das Outras Redes Sociais e igual ao do Facebook
$twitter = $client->getConnection('twitter');
if (!$twitter->isOAuth()) {
    $authTwitter = $twitter->getOAuth();
    $authTwitter->setRedirectUri('http://localhost:8001/');
    if ($authTwitter->isGetCode() !== true) {
        printf(
            '<a href="%s"> Login Twitter </a> <br>',
            $authTwitter->getAuthorizationUrl()
        );
    } else {
        $twitter->registerOAuth();
    }
} else {
    printf('Logado no Twitter como %s <br>', $twitter->owner['name']);
}

if (!empty($twitter->owner)) {
    array_push($connections, $twitter->owner);
}


$instagram = $client->getConnection('instagram');
if (!$instagram->isOAuth()) {
    $authInstagram = $instagram->getOAuth();
    $authInstagram->setRedirectUri('http://localhost:8001/');
    if ($authInstagram->isGetCode() !== true) {
        printf(
            '<a href="%s"> Login Instagram </a> <br>',
            $authInstagram->getAuthorizationUrl()
        );
    } else {
        $instagram->registerOAuth();
    }
} else {
    printf('Logado no Instagram como %s <br>', $instagram->owner['name']);
}

if (!empty($instagram->owner)) {
    array_push($connections, $instagram->owner);
}

if (!empty($connections)) {
    
    $data = [
        "name" => "Cleber Rodrigues",
        "external_id" => uniqid(),
        "email" => "cleber@cleberar.com",
        "connections" => $connections
    ];


    $r = $client->request('POST', 'auth', $data);
    var_dump($r->code());
    var_dump($r->message());
    var_dump($r->content());
}
