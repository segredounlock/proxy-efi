#!/bin/bash
###############################################################################
# AUTO-GIFT CRON SETUP
# Script para configurar execução automática do sistema de auto-gifts
# 
# Este script adiciona uma entrada no crontab para executar o auto_gift_cron.php
# a cada minuto. O próprio script PHP controla o intervalo real de execução.
#
# Versão: 1.0
# Data: 2025-11-23
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Diretório do bot
BOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_SCRIPT="${BOT_DIR}/auto_gift_cron.php"
LOG_FILE="${BOT_DIR}/bot_logs/auto_gift.log"

echo -e "${BLUE}╔═══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                                                       ║${NC}"
echo -e "${BLUE}║         SETUP AUTO-GIFT CRON SYSTEM                   ║${NC}"
echo -e "${BLUE}║                                                       ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar se o script PHP existe
if [ ! -f "$PHP_SCRIPT" ]; then
    echo -e "${RED}✗ Erro: Arquivo auto_gift_cron.php não encontrado!${NC}"
    echo -e "${YELLOW}  Localização esperada: $PHP_SCRIPT${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Script PHP encontrado: $PHP_SCRIPT${NC}"

# Verificar se PHP está instalado
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ Erro: PHP não está instalado!${NC}"
    echo -e "${YELLOW}  Instale o PHP primeiro: sudo apt-get install php-cli${NC}"
    exit 1
fi

PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1-2)
echo -e "${GREEN}✓ PHP instalado: versão $PHP_VERSION${NC}"

# Criar entrada do cron
CRON_COMMAND="* * * * * /usr/bin/php $PHP_SCRIPT >> $LOG_FILE 2>&1"

echo ""
echo -e "${YELLOW}Configurando cron job...${NC}"
echo ""

# Verificar se já existe
if crontab -l 2>/dev/null | grep -q "auto_gift_cron.php"; then
    echo -e "${YELLOW}⚠ Entrada do cron já existe!${NC}"
    echo ""
    echo -e "${BLUE}Entrada atual:${NC}"
    crontab -l | grep "auto_gift_cron.php"
    echo ""
    
    read -p "Deseja substituir? (s/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}Operação cancelada.${NC}"
        exit 0
    fi
    
    # Remover entrada antiga
    crontab -l 2>/dev/null | grep -v "auto_gift_cron.php" | crontab -
    echo -e "${GREEN}✓ Entrada antiga removida${NC}"
fi

# Adicionar nova entrada
(crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                       ║${NC}"
    echo -e "${GREEN}║     ✓ CRON JOB CONFIGURADO COM SUCESSO!              ║${NC}"
    echo -e "${GREEN}║                                                       ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Configuração:${NC}"
    echo -e "  ${GREEN}Frequência:${NC} A cada 1 minuto (o script controla o intervalo real)"
    echo -e "  ${GREEN}Script:${NC} $PHP_SCRIPT"
    echo -e "  ${GREEN}Log:${NC} $LOG_FILE"
    echo ""
    echo -e "${BLUE}Próximos passos:${NC}"
    echo -e "  1. Use o comando ${YELLOW}/autogift_config${NC} no bot para ver a configuração"
    echo -e "  2. Use ${YELLOW}/autogift_set${NC} para definir intervalo e parâmetros"
    echo -e "  3. Use ${YELLOW}/autogift_start${NC} para ativar o sistema"
    echo -e "  4. Use ${YELLOW}/autogift_test${NC} para testar manualmente"
    echo ""
    echo -e "${BLUE}Para verificar o cron:${NC}"
    echo -e "  ${YELLOW}crontab -l${NC}"
    echo ""
    echo -e "${BLUE}Para ver o log em tempo real:${NC}"
    echo -e "  ${YELLOW}tail -f $LOG_FILE${NC}"
    echo ""
    echo -e "${GREEN}Sistema pronto para uso!${NC}"
    echo ""
else
    echo ""
    echo -e "${RED}✗ Erro ao configurar cron job${NC}"
    echo -e "${YELLOW}Configure manualmente com: crontab -e${NC}"
    echo -e "${YELLOW}E adicione a linha:${NC}"
    echo -e "${BLUE}$CRON_COMMAND${NC}"
    exit 1
fi
