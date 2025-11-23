# 🎁 Sistema de Auto-Gift Automático

## 📋 Visão Geral

O Sistema de Auto-Gift é um recurso avançado que permite a **geração e distribuição automática** de gifts (códigos de presente) para todos os usuários do bot em intervalos configuráveis.

### ✨ Principais Recursos

- ✅ **Geração Automática**: Cria gifts automaticamente em intervalos definidos
- ✅ **Broadcast Automático**: Envia os gifts para todos os usuários cadastrados
- ✅ **Altamente Configurável**: Controle total sobre intervalo, quantidade, tipo e valor
- ✅ **Proteção contra Admins**: Admins não recebem os broadcasts automáticos
- ✅ **Logs Detalhados**: Registro completo de todas as execuções
- ✅ **Estatísticas**: Acompanhamento de execuções e gifts enviados
- ✅ **Controle via Bot**: Todos os comandos disponíveis no Telegram

---

## 🚀 Instalação e Configuração

### 1️⃣ Arquivos do Sistema

O sistema é composto por 3 arquivos principais:

```
📁 bot_telegram/
├── 📄 api_telegram_FINAL.php      (Bot principal com comandos)
├── 📄 auto_gift_cron.php          (Script de execução automática)
└── 📄 setup_autogift_cron.sh      (Script de instalação do cron)
```

### 2️⃣ Instalação do Cron Job

**Método Automático (Recomendado):**

```bash
cd /var/www/html  # ou diretório do seu bot
./setup_autogift_cron.sh
```

O script irá:
- ✅ Verificar se o PHP está instalado
- ✅ Verificar se os arquivos necessários existem
- ✅ Configurar o cron job automaticamente
- ✅ Confirmar a instalação

**Método Manual:**

Se preferir configurar manualmente, edite o crontab:

```bash
crontab -e
```

Adicione a linha:

```cron
* * * * * /usr/bin/php /var/www/html/auto_gift_cron.php >> /var/www/html/bot_logs/auto_gift.log 2>&1
```

> **Nota**: O cron executa a cada minuto, mas o próprio script controla o intervalo real baseado na configuração.

### 3️⃣ Verificar Instalação

```bash
# Ver cron jobs ativos
crontab -l

# Ver log em tempo real
tail -f /var/www/html/bot_logs/auto_gift.log
```

---

## 🎮 Comandos do Bot

### 📊 Ver Configuração Atual

```
/autogift_config
```

**Mostra:**
- Status (ativo/desativado)
- Intervalo de tempo
- Quantidade de gifts por execução
- Modo e valor dos gifts
- Estatísticas de uso
- Próxima execução programada

---

### ✅ Ativar Sistema

```
/autogift_start
```

**Ativa o sistema automático**. A partir deste momento, o cron irá:
- Verificar a cada minuto se chegou o intervalo configurado
- Gerar os gifts automaticamente
- Enviar para todos os usuários
- Notificar os admins sobre o sucesso/falha

---

### 🛑 Desativar Sistema

```
/autogift_stop
```

**Desativa o sistema**. O cron continua rodando mas não executará mais ações até ser reativado.

---

### ⚙️ Configurar Parâmetros

```
/autogift_set [intervalo] [quantidade] [modo] [valor] [usos]
```

#### 📝 Parâmetros:

| Parâmetro | Descrição | Valores | Exemplo |
|-----------|-----------|---------|---------|
| **intervalo** | Minutos entre execuções | 5 - 1440 | 60 |
| **quantidade** | Número de gifts por execução | 1 - 10 | 1 |
| **modo** | Tipo de gift | credit, auto | credit |
| **valor** | Valor do gift | $5.00 ou 7d, 15d, 30d | 5.00 |
| **usos** | Quantas vezes pode ser usado | 1 - 100 | 1 |

#### 🔹 Exemplos:

**Exemplo 1: Gift de crédito a cada 1 hora**
```
/autogift_set 60 1 credit 5.00 1
```
→ A cada 60 minutos, gera 1 gift de $5.00 com 1 uso

**Exemplo 2: Múltiplos gifts a cada 2 horas**
```
/autogift_set 120 3 credit 10.00 1
```
→ A cada 2 horas, gera 3 gifts de $10.00 com 1 uso cada

**Exemplo 3: Gift de plano a cada 30 minutos**
```
/autogift_set 30 1 auto 7d 1
```
→ A cada 30 minutos, gera 1 gift de 7 dias com 1 uso

**Exemplo 4: Gift com múltiplos usos**
```
/autogift_set 60 1 credit 2.00 5
```
→ A cada 60 minutos, gera 1 gift de $2.00 que pode ser usado 5 vezes

---

### 🧪 Teste Manual

```
/autogift_test
```

**Executa o sistema imediatamente** (independente do intervalo configurado).

Útil para:
- ✅ Testar após mudanças de configuração
- ✅ Verificar se está funcionando corretamente
- ✅ Debug de problemas

---

## 📊 Configuração Detalhada

### Arquivo de Configuração

O sistema cria automaticamente o arquivo:
```
bot_data/auto_gift_config.json
```

#### Estrutura do JSON:

```json
{
  "enabled": false,
  "interval_minutes": 60,
  "gift_quantity": 1,
  "gift_mode": "credit",
  "gift_param": "5.00",
  "gift_uses": 1,
  "broadcast_message": "🎁 <b>GIFT AUTOMÁTICO!</b>\n\nUse o código abaixo para resgatar:\n\n<code>{CODE}</code>\n\n⚡ Válido por tempo limitado!",
  "last_run": null,
  "total_runs": 0,
  "total_gifts_sent": 0
}
```

### Personalizar Mensagem de Broadcast

Você pode editar a mensagem que será enviada modificando o campo `broadcast_message` no arquivo JSON.

**Variável disponível:**
- `{CODE}` - Será substituído pelo código do gift gerado

**Exemplo de mensagem personalizada:**

```json
"broadcast_message": "🎉 <b>SURPRESA!</b>\n\n🎁 Resgate seu presente GRÁTIS:\n<code>{CODE}</code>\n\n⏰ Corra! Válido apenas para os primeiros 100 usuários!\n\n💡 Use /resgatar {CODE}"
```

---

## 📈 Como Funciona

### Fluxo de Execução

```
1. ⏰ CRON executa script a cada 1 minuto
        ↓
2. 🔍 Script verifica se sistema está ATIVADO
        ↓
3. ⏱️ Verifica se chegou o INTERVALO configurado
        ↓
4. 🎁 GERA os gifts conforme quantidade/modo/valor
        ↓
5. 📢 ENVIA broadcast para todos os usuários
        ↓
6. 🚫 BLOQUEIA envio para admins
        ↓
7. 📊 ATUALIZA estatísticas e configuração
        ↓
8. 📧 NOTIFICA admins sobre resultado
```

### Proteção contra Admins

**Os admins NÃO recebem os broadcasts automáticos!**

Isso previne:
- ❌ Admins copiarem e reenviarem mensagens (loop infinito)
- ❌ Admins usarem seus próprios gifts
- ❌ Spam para quem administra o bot

**Mas admins RECEBEM:**
- ✅ Notificação quando um auto-gift é executado
- ✅ Relatório com códigos gerados
- ✅ Estatísticas de envio

---

## 🔍 Monitoramento

### Ver Logs em Tempo Real

```bash
tail -f /var/www/html/bot_logs/auto_gift.log
```

### Exemplo de Log:

```
[2025-11-23 14:00:00] ========== AUTO-GIFT EXECUTION START ==========
[2025-11-23 14:00:00] 🎁 Iniciando geração automática de gifts...
[2025-11-23 14:00:00] 📦 Quantidade: 1
[2025-11-23 14:00:00] 🎯 Modo: credit
[2025-11-23 14:00:00] 💰 Valor: 5.00
[2025-11-23 14:00:00] 🔢 Usos: 1
[2025-11-23 14:00:01] ✅ Gift criado: ABC123XYZ789 | mode:credit | param:5.00 | uses:1
[2025-11-23 14:00:01] ✅ Gifts criados com sucesso: 1
[2025-11-23 14:00:01] 📢 Iniciando broadcast para 497 usuários...
[2025-11-23 14:00:02] 🚫 Admin 1901426549 bloqueado de receber auto-gift
[2025-11-23 14:00:02] ✅ Enviado para 123456789
[2025-11-23 14:00:03] ✅ Enviado para 987654321
[2025-11-23 14:01:30] 📊 Broadcast concluído: 496 enviados, 0 falhas
[2025-11-23 14:01:30] ✅ Execução completa!
[2025-11-23 14:01:30] 📊 Total de execuções até agora: 1
[2025-11-23 14:01:30] 🎁 Total de gifts enviados: 1
[2025-11-23 14:01:30] ========== AUTO-GIFT EXECUTION END ==========
```

### Verificar Status do Cron

```bash
# Ver se o cron está rodando
ps aux | grep cron

# Ver últimas execuções
grep "auto_gift_cron.php" /var/log/syslog

# Ver cron jobs do usuário
crontab -l
```

---

## ⚠️ Troubleshooting

### Problema: Cron não executa

**Solução:**
```bash
# Verificar se cron está instalado
sudo service cron status

# Iniciar cron se não estiver rodando
sudo service cron start

# Habilitar cron no boot
sudo systemctl enable cron
```

### Problema: Permissões negadas

**Solução:**
```bash
cd /var/www/html
chmod 755 auto_gift_cron.php
chmod 755 setup_autogift_cron.sh
chmod 777 bot_data/
chmod 777 bot_logs/
```

### Problema: PHP não encontrado

**Solução:**
```bash
# Verificar localização do PHP
which php

# Atualizar caminho no cron
crontab -e
# Usar caminho completo, ex: /usr/bin/php
```

### Problema: Sistema ativo mas não executa

**Verificar:**
1. ✅ O cron está rodando? `crontab -l`
2. ✅ O sistema está ativado? `/autogift_config`
3. ✅ Chegou o intervalo? Verifique "Próxima execução"
4. ✅ Há erros no log? `tail -f bot_logs/auto_gift.log`

---

## 💡 Casos de Uso

### 1. Promoção Diária

```
/autogift_set 1440 1 credit 10.00 1
```
→ A cada 24 horas, envia 1 gift de $10 para todos

### 2. Happy Hour

```
/autogift_set 60 1 credit 5.00 1
```
→ Durante 1 hora específica, envia gifts a cada hora

### 3. Evento Especial

```
/autogift_set 30 3 credit 2.00 1
```
→ Durante evento, envia 3 gifts a cada 30 minutos

### 4. Trial Automático

```
/autogift_set 360 1 auto 7d 1
```
→ A cada 6 horas, envia 1 gift de 7 dias de trial

---

## 📊 Estatísticas e Métricas

O sistema rastreia automaticamente:

- ✅ Total de execuções realizadas
- ✅ Total de gifts gerados
- ✅ Última execução
- ✅ Próxima execução programada
- ✅ Sucessos e falhas de cada execução

Acesse com:
```
/autogift_config
```

---

## 🔐 Segurança

### Proteções Implementadas

1. **Apenas Admins**: Somente administradores podem controlar o sistema
2. **Validação de Parâmetros**: Todos os valores são validados
3. **Rate Limiting**: 100ms entre cada envio de mensagem
4. **Proteção de Admins**: Admins não recebem broadcasts
5. **Logs Seguros**: Todas as ações são registradas
6. **Lock de Execução**: Previne execuções simultâneas

---

## 📞 Suporte

### Comandos Úteis

```
/autogift_config    - Ver configuração completa
/autogift_test      - Testar funcionamento
/help               - Ver todos os comandos
```

### Arquivos de Log

- **Auto-Gift**: `bot_logs/auto_gift.log`
- **Bot Principal**: `bot_logs/bot.log`
- **Broadcast**: `bot_logs/broadcast.log`

---

## 📝 Changelog

### v1.0 (2025-11-23)
- ✅ Implementação inicial do sistema
- ✅ Comandos de controle via bot
- ✅ Script de cron automático
- ✅ Proteção contra admins
- ✅ Logs detalhados
- ✅ Estatísticas completas
- ✅ Script de instalação automática

---

## 🎉 Conclusão

O Sistema de Auto-Gift é uma ferramenta poderosa para:
- 🎯 **Engajar usuários** com gifts regulares
- 📈 **Aumentar retenção** com benefícios automáticos
- ⚡ **Automatizar promoções** sem intervenção manual
- 📊 **Acompanhar métricas** de distribuição

**Use com responsabilidade e aproveite! 🚀**

---

**Versão**: 1.0  
**Data**: 2025-11-23  
**Desenvolvedor**: Claude AI Assistant
