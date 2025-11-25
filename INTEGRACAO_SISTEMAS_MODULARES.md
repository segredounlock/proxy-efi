# 🔧 GUIA DE INTEGRAÇÃO - SISTEMAS MODULARES

**Data:** 25/11/2024  
**Bot:** @Bypasa12_bot  
**Arquivos Criados:**
- `referral_system.php` - Sistema completo de indicações
- `broadcast_system.php` - Sistema de broadcast separado

---

## 📁 ESTRUTURA DE ARQUIVOS

```
/a12/
├── api_telegram.php          (arquivo principal - será modificado)
├── config.php                 (configurações)
├── referral_system.php        (🆕 novo arquivo)
├── broadcast_system.php       (🆕 novo arquivo)
├── bot_data/
│   ├── users.json
│   ├── referrals.json         (🆕 novo arquivo)
│   ├── referral_rewards.json  (🆕 novo arquivo)
│   └── referral_balance_history.json (🆕 novo arquivo)
└── bot_logs/
    ├── broadcast.log
    └── debug.log
```

---

## 🔄 PASSO 1: INCLUIR OS MÓDULOS NO api_telegram.php

Adicione logo após as configurações (linha ~88):

```php
// ==================== INCLUIR MÓDULOS EXTERNOS ====================
require_once __DIR__ . '/referral_system.php';
require_once __DIR__ . '/broadcast_system.php';
```

---

## 🗑️ PASSO 2: REMOVER CÓDIGO DE BROADCAST DO api_telegram.php

**DELETAR** as seguintes funções (elas estão agora em `broadcast_system.php`):

1. `is_broadcast_running()` (linha ~130)
2. `create_broadcast_lock()` (linha ~153)
3. `remove_broadcast_lock()` (linha ~165)
4. `get_broadcast_lock_info()` (linha ~172)
5. `cmd_broadcast()` (linha ~1235)
6. `cmd_broadcast_status()` (linha ~1477)
7. `cmd_broadcast_cancel()` (linha ~1506)

**MANTER APENAS AS DEFINIÇÕES** no topo:
```php
define('BROADCAST_LOCK_FILE', DATA_DIR . '/broadcast.lock');
define('LOG_BROADCAST', LOGS_DIR . '/broadcast.log');
define('BROADCAST_LOCK_TIMEOUT', 600);
```

---

## ➕ PASSO 3: ADICIONAR COMANDOS DE INDICAÇÃO

No switch de comandos (linha ~1900), adicione:

```php
case '/indicar':
case '/indicar@Bypasa12_bot':
    cmd_indicar($chat_id);
    break;

case '/meusaldo':
case '/meusaldo@Bypasa12_bot':
    cmd_meusaldo($chat_id);
    break;
```

---

## 🔄 PASSO 4: MODIFICAR O COMANDO /start

Localize a função que trata o `/start` (linha ~1901) e modifique para detectar códigos de indicação:

**ANTES:**
```php
case '/start':
    $user = get_user($chat_id);
    // ... código existente
```

**DEPOIS:**
```php
case '/start':
    $user = get_user($chat_id);
    
    // 🎁 DETECTAR CÓDIGO DE INDICAÇÃO
    $parts = explode(' ', $text);
    if (isset($parts[1]) && str_starts_with($parts[1], 'REF')) {
        $referral_code = $parts[1];
        handle_referral_start($chat_id, $referral_code);
    }
    
    // ... resto do código existente
```

---

## 💰 PASSO 5: MARCAR INDICAÇÃO COMO COMPLETA

Quando um usuário faz uma compra (função que processa ordem confirmada), adicione:

```php
// Após processar ordem bem-sucedida
complete_referral($chat_id);
```

**Localizar em:** função de confirmação de ordem/pagamento (procure por onde é atualizado `total_orders` ou similar)

---

## 📊 PASSO 6: ATUALIZAR MENU /help

Adicione os novos comandos na lista de ajuda:

```php
function cmd_help($chat_id) {
    $msg = "📚 <b>COMANDOS DISPONÍVEIS</b>\n\n";
    $msg .= "🎮 <b>USUÁRIO:</b>\n";
    $msg .= "/start - Iniciar bot\n";
    $msg .= "/help - Esta mensagem\n";
    $msg .= "/saldo - Ver seus créditos\n";
    $msg .= "/indicar - Sistema de indicações 🆕\n";
    $msg .= "/meusaldo - Ver saldo de indicações 🆕\n\n";
    
    // ... resto dos comandos
}
```

---

## 🧪 PASSO 7: TESTAR O SISTEMA

### Teste 1: Gerar Código de Indicação
```
/indicar
```
**Resultado esperado:** Bot retorna seu código único (ex: REF000123AB4C)

### Teste 2: Usar Código (Novo Usuário)
```
/start REF000123AB4C
```
**Resultado esperado:** Mensagem de boas-vindas com indicação registrada

### Teste 3: Ver Saldo
```
/meusaldo
```
**Resultado esperado:** Saldo atual e histórico de transações

### Teste 4: Broadcast (Admin)
```
/broadcast Teste de mensagem
```
**Resultado esperado:** Broadcast enviado, admins não recebem

### Teste 5: Status Broadcast
```
/broadcast_status
```
**Resultado esperado:** Status do broadcast em andamento (ou nenhum)

---

## ⚙️ CONFIGURAÇÃO DE RECOMPENSAS

Edite `referral_system.php` para ajustar recompensas (linha ~30):

```php
$default_rewards = [
    1 => ['credits' => 5.00, 'description' => 'R$ 5,00 - Primeira indicação'],
    3 => ['credits' => 10.00, 'description' => 'R$ 10,00 - Três indicações'],
    5 => ['credits' => 20.00, 'description' => 'R$ 20,00 - Cinco indicações'],
    10 => ['credits' => 50.00, 'description' => 'R$ 50,00 - Dez indicações'],
    // Adicione mais marcos conforme necessário
];
```

---

## 🔒 SEGURANÇA E PROTEÇÕES

### Sistema de Indicações:
✅ Usuário não pode indicar a si mesmo  
✅ Usuário só pode ser indicado uma vez  
✅ Indicação só completa após primeira compra  
✅ Histórico completo de transações  

### Sistema de Broadcast:
✅ Proteção anti-loop (admins não recebem)  
✅ Proteção anti-duplicação de mensagens  
✅ Sistema de LOCK (apenas um broadcast por vez)  
✅ Timeout automático (10 minutos)  
✅ Cancelamento manual via comando  

---

## 📝 LOGS E MONITORAMENTO

### Logs de Indicações:
```
bot_logs/debug.log
```
**Buscar por:** `REFERRAL`, `REFERRAL_REWARD`, `REFERRAL_COMPLETED`

### Logs de Broadcast:
```
bot_logs/broadcast.log
```
**Formato detalhado** com timestamp, admin, total enviado, falhas

---

## 🚀 PRÓXIMOS PASSOS (OPCIONAL)

1. **Admin Panel:** Criar página web para gerenciar recompensas
2. **Relatórios:** Gerar estatísticas de indicações por período
3. **Notificações:** Avisar indicador quando indicado faz compra
4. **Leaderboard:** Ranking dos maiores indicadores
5. **Bônus Especiais:** Campanhas temporárias com recompensas extras

---

## ❓ TROUBLESHOOTING

### Problema: Comando /indicar não funciona
**Solução:** Verificar se `referral_system.php` foi incluído corretamente

### Problema: Indicação não registra
**Solução:** Verificar logs em `debug.log` procurando por `REFERRAL_ERROR`

### Problema: Broadcast enviando para admins
**Solução:** Verificar array `ADMIN_IDS` no `config.php`

### Problema: Recompensa não dada automaticamente
**Solução:** Verificar se `complete_referral()` está sendo chamado após compra

---

## 📞 SUPORTE

Para dúvidas ou problemas, verifique:
1. Logs em `bot_logs/`
2. Arquivos JSON em `bot_data/`
3. Permissões de escrita nas pastas

---

**🎉 Sistema pronto para uso!**

Após integração, o bot terá:
- ✅ Sistema completo de indicações
- ✅ Sistema de recompensas automático
- ✅ Broadcast organizado e seguro
- ✅ Código modular e manutenível
