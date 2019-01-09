
# Pollus Watchdog
Uma classe simples de detecção à ataques bruteforce e abuso de funcionalidades.

O Watchdog observa ações sensíveis de seu sistema e registra em um banco de dados. Quando estas ações excederem os limites aceitáveis, o endereço IP ou o ID de sessão serão considerados "suspeitos", possibilitando que seu sistema possa tomar ações como a exibição de captchas ou métodos de verificação como SMS ou e-mail.

Caso as ações indesejadas continuem ocorrendo mesmo com as verificações que foram implantadas, o Watchdog irá aplicar um banimento, no qual seu sistema poderá tomar medidas mais rígidas, como simplesmente ignorar todas as requisições para determinada funcionalidade por um período de tempo.

**Observações:** Esta classe apenas auxilia na detecção de ataques e abusos de funcionalidade, porém não toma qualquer ação como captchas ou verificações adicionais. Estas devem ser implementadas por sua própria aplicação.

**Utilização**

    composer require pollus/watchdog

Crie a tabela "watchdog_logs" com a seguinte estrutura:

```sql
CREATE TABLE IF NOT EXISTS `watchdog_logs` 
(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(200) NOT NULL,
  `session_id` varchar(512) DEFAULT NULL,
  `ip_address` varchar(40) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

**Inicialização**

```php
require_once(__DIR__."/../vendor/autoload.php");

use Pollus\Watchdog\Watchdog;
use Pollus\Watchdog\Adapters\DatabaseAdapter;
use Pollus\HttpClientFingerprint\HttpClientFingerprint;

// Conexão com o banco
$pdo = new PDO("mysql:host=127.0.0.1;dbname=database_name", "user", "password");

// Adapter para o banco de dados
$adapter = new DatabaseAdapter($pdo, "watchdog_logs");

// Identificação do endereço IP e Session ID
$fingerprint = new HttpClientFingerprint();

// Opções da classe (opcional)
$options = 
[
    "suspect_counter" => 3,
    "ban_enabled" => true,
    "ban_counter" => 5,
    "ban_time" => 10,
    "find_time" => 15,
    "ip_lookup" => true,
    "session_lookup" => true,
];

$login_watchdog = new Watchdog("login", $adapter, $fingerprint, $options);
```

**Exemplo de Watchdog para proteger um formulário de login**

```php

if ($login_watchdog->isBanned())
{
    die("Você está temporariamente banido!");
}
else if ($login_watchdog->isSuspect())
{
    // [..] Verificação de Captcha
}

if ($user->checkPassword() === false)
{
    // Registra que houve uma tentativa inválida de login
    $login_watchdog->log();
}
else
{
    // Logado
}
```

**Opções**
    
    "suspect_counter": int, default (5)
    Define o número de ações que serão toleradas na janela de análise antes de sinalizar como "suspeito"

    "ban_enabled": bool, default (true)
    Habilita o banimento

    "ban_counter": int, default (20)
    Define o número de ações que serão toleradas na janela de análise antes que uma proibição seja instituída. Este valor é dado em minutos.

    "ban_time": int, default (10)
    Define o tempo que o banimento irá durar. Este valor é dado em minutos.

    "find_time": int, default (15)
    Define a janela de análise. Este valor é dado em minutos.

    "ip_lookup": bool, default (true)
    Habilita a verificação de endereço IP.

    "session_lookup": bool, default (true)
    Habilita a verificação do ID da sessão

**To do:**
- Documentação completa (incluindo uma versão em Inglês)
- Mais testes

# MIT License

Copyright (c) 2018 Renan Cavalieri

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
