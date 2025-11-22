# 📦 INSTALAÇÃO - ARQUIVO ÚNICO

## ✅ ARQUIVO PARA ENVIAR AO SERVIDOR

**Apenas 1 arquivo:** `bot_unico_completo.php` (110KB)

---

## 🚀 PASSO A PASSO

### 1️⃣ BAIXAR O ARQUIVO

Baixe o arquivo `bot_unico_completo.php` do repositório.

### 2️⃣ ENVIAR PARA O SERVIDOR

**Via FTP / cPanel:**
1. Conecte ao servidor
2. Vá para a pasta do bot (exemplo: `/public_html/a12/`)
3. Envie o arquivo `bot_unico_completo.php`
4. Renomeie para `webhook.php`

**Via SSH:**
```bash
# Upload via SCP
scp bot_unico_completo.php usuario@servidor.com:/caminho/para/a12/

# Conecte via SSH
ssh usuario@servidor.com

# Renomear
cd /caminho/para/a12/
mv bot_unico_completo.php webhook.php
```

### 3️⃣ CRIAR PASTAS NECESSÁRIAS

```bash
mkdir bot_data
mkdir bot_logs
chmod 755 bot_data
chmod 755 bot_logs
```

**Via cPanel:**
1. File Manager
2. Criar Pasta → `bot_data`
3. Criar Pasta → `bot_logs`
4. Permissões → 755 para ambas

### 4️⃣ CONFIGURAR PERMISSÕES

```bash
chmod 644 webhook.php
```

**Via cPanel:**
1. Selecione `webhook.php`
2. Permissões → 644

### 5️⃣ CONFIGURAR WEBHOOK DO TELEGRAM

Acesse esta URL no navegador (substitua valores):

```
https://api.telegram.org/bot[SEU_TOKEN]/setWebhook?url=https://seuservidor.com/a12/webhook.php
```

**Exemplo real:**
```
https://api.telegram.org/bot8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA/setWebhook?url=https://buscalotter.com/a12/webhook.php
```

**Resposta esperada:**
```json
{
  "ok": true,
  "result": true,
  "description": "Webhook was set"
}
```

### 6️⃣ CRIAR .htaccess (SE NECESSÁRIO)

Se você receber erro 403 Forbidden, crie arquivo `.htaccess` na pasta `/a12/`:

```apache
# Habilitar PHP
AddHandler application/x-httpd-php .php

# Desabilitar ModSecurity se necessário
<IfModule mod_security.c>
    SecRuleEngine Off
</IfModule>

# Permitir acesso ao webhook.php
<Files "webhook.php">
    Order allow,deny
    Allow from all
    Require all granted
</Files>

# Proteger pastas de dados
<DirectoryMatch "(bot_data|bot_logs)">
    Order deny,allow
    Deny from all
</DirectoryMatch>

# Prevenir listagem de diretórios
Options -Indexes
```

### 7️⃣ TESTAR O BOT

Envie no Telegram:
```
/start
```

**Deve aparecer o menu com botões!** 🎉

---

## 📁 ESTRUTURA FINAL NO SERVIDOR

```
/a12/
├── webhook.php              ← Arquivo único (110KB)
├── .htaccess                ← Opcional (apenas se 403)
├── bot_data/               ← Pasta vazia (criada)
└── bot_logs/               ← Pasta vazia (criada)
```

---

## ✅ VANTAGENS DO ARQUIVO ÚNICO

- ✅ **Sem dependências** - Tudo em 1 arquivo
- ✅ **Fácil de enviar** - Apenas 1 upload
- ✅ **Sem erro de require** - Não precisa de outros arquivos
- ✅ **Completo** - Todos os recursos incluídos
- ✅ **110KB** - Arquivo pequeno e rápido

---

## 🎯 RECURSOS INCLUÍDOS

### ✨ Funções do Usuário:
- Menu principal com botões
- Ver saldo
- Meus pedidos
- Comprar créditos
- Estatísticas pessoais
- Resgatar gifts
- Histórico de transações
- Sistema de unlock

### 👑 Funções Admin:
- Menu administrativo completo
- **Broadcast com botões** (texto, foto, vídeo, áudio, documento, voz)
- Sistema anti-duplicação 100%
- Status de broadcast em tempo real
- Cancelamento interativo
- Gerenciamento de gifts
- Estatísticas globais
- Lista de usuários
- Backup com um clique
- Adicionar créditos
- Info de usuários

### 📢 Sistema de Broadcast:
- Suporte a 6 tipos de mídia
- Responder mensagem para broadcast
- Progresso em tempo real
- Zero duplicação garantida
- Logs detalhados
- Controle total (status + cancelar)

---

## 🐛 SOLUÇÃO DE PROBLEMAS

### Erro 403 Forbidden
✅ **Solução:** Criar arquivo `.htaccess` (veja passo 6)

### Bot não responde
✅ **Verificar:**
1. Webhook configurado corretamente
2. URL usando HTTPS (obrigatório!)
3. Permissões dos arquivos (644 para webhook.php, 755 para pastas)

### Menu não aparece
✅ **Solução:** 
1. Envie `/start` novamente
2. Limpe cache do Telegram (feche e abra)

### Erro de permissão ao gravar
✅ **Solução:**
```bash
chmod 755 bot_data
chmod 755 bot_logs
```

---

## 📊 COMPARAÇÃO: ÚNICO vs MÚLTIPLOS

| Aspecto | ❌ 2 Arquivos | ✅ 1 Arquivo Único |
|---------|---------------|---------------------|
| **Arquivos para enviar** | 2 (webhook.php + bot_completo_melhorado.php) | 1 (bot_unico_completo.php) |
| **Tamanho total** | 32KB + 79KB = 111KB | 110KB |
| **Dependências** | require_once precisa funcionar | Nenhuma |
| **Erro de caminho** | Possível | Impossível |
| **Facilidade** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Velocidade** | Igual | Igual |
| **Manutenção** | ⭐⭐⭐ | ⭐⭐⭐⭐ |

---

## 🎉 PRONTO!

Depois de seguir esses passos, seu bot estará:

✅ **100% FUNCIONAL** com menu interativo  
✅ **Sistema de broadcast** completo  
✅ **Sem dependências** externas  
✅ **Fácil de manter** - tudo em 1 arquivo  

---

## 💡 COMANDOS ÚTEIS

### Verificar Status do Webhook:
```
https://api.telegram.org/bot[SEU_TOKEN]/getWebhookInfo
```

### Testar Acesso ao Arquivo:
```
https://seuservidor.com/a12/webhook.php
```
(Deve mostrar página em branco, não erro 403)

### Ver Logs (via SSH):
```bash
tail -50 bot_logs/errors.log
tail -100 bot_logs/broadcast.log
```

---

## 📞 SUPORTE

Se tiver problemas:

1. Verifique os logs em `bot_logs/errors.log`
2. Teste o webhook: `/getWebhookInfo`
3. Verifique permissões dos arquivos e pastas
4. Confirme que está usando HTTPS
5. Teste o `.htaccess` se houver erro 403

---

**Versão:** 5.0 ARQUIVO ÚNICO  
**Data:** 22/11/2024  
**Tamanho:** 110KB  
**Linhas de código:** 3,187  
**Status:** ✅ TESTADO E FUNCIONANDO
