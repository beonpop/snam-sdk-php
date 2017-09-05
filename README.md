# SNAM SDK PHP (Beta)


## Instalação

Por ser uma versão beta, a mesma ainda não está disponivel no getcomposer, então adicione ao composer.json
```sh
 "require": {
        "beonpop/snam-sdk-php" : "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/beonpop/snam-sdk-php",
            "branch": "master"
        }
    ]
```

Atualize o composer
```sh
  composer update
```

## Exemplo de Uso

> **Note:** This version of the Facebook SDK for PHP requires PHP 5.4 or greater.

Simple GET example of a user's profile.

```php
require_once __DIR__ . '/vendor/autoload.php';

$client = new SNAMClient\SNAM([
    "app_id" => '<APP-ID>',
    "app_token" => '<APP-TOKEN>',
    "app" => '<APLICATIVO>',
    "version" => 'v2.1'
]);

$r = $client->request('GET', 'version');

var_dump($r->content());
```
