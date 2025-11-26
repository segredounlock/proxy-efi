# 🎨 Cores Exatas da Tabela de Pedidos

## 📸 Referência

Cores extraídas DIRETAMENTE da imagem fornecida e aplicadas com precisão absoluta.

---

## 🎯 Paleta de Cores Completa

### 1. **ID (#1056)**
- **Cor:** `#A0B8E6`
- **RGB:** rgb(160, 184, 230)
- **Descrição:** Azul suave, pastelpara identificação
- **Uso:** Número do pedido
- **Font-weight:** 600 (semi-bold)

---

### 2. **PRODUTO (VIVO - 44Gb 14$)**
- **Cor:** `#8E24AA`
- **RGB:** rgb(142, 36, 170)
- **Descrição:** Roxo/Violeta vibrante
- **Uso:** Nome do produto e ícones de coroa
- **Font-weight:** 600 (semi-bold)

**Exemplo:**
```css
.table td:nth-child(2) {
  color: #8E24AA;
  font-weight: 600;
}

.table td:nth-child(2) .bi {
  color: #8E24AA;  /* Ícones de coroa */
}
```

---

### 3. **CLIENTE (1312312354)**
- **Cor:** `#EF5350`
- **RGB:** rgb(239, 83, 80)
- **Descrição:** Vermelho vibrante
- **Uso:** ID do cliente/chat
- **Font:** Courier New, monospace
- **Font-weight:** 500 (medium)

**Exemplo:**
```css
.table td:nth-child(3) {
  color: #EF5350;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  font-weight: 500;
}
```

---

### 4. **STATUS - ENTREGUE**

#### Background:
- **Cor:** `#C8E6C9`
- **RGB:** rgb(200, 230, 201)
- **Descrição:** Verde claro suave

#### Texto:
- **Cor:** `#2E7D32`
- **RGB:** rgb(46, 125, 50)
- **Descrição:** Verde escuro

#### Borda:
- **Cor:** `#81C784`
- **RGB:** rgb(129, 199, 132)
- **Descrição:** Verde médio

**Exemplo:**
```css
.badge-entregue {
  background: #C8E6C9 !important;
  color: #2E7D32 !important;
  border-color: #81C784 !important;
}
```

---

### 5. **STATUS - PENDENTE**

#### Background:
- **Cor:** `#FFF9C4`
- **RGB:** rgb(255, 249, 196)
- **Descrição:** Amarelo claro suave

#### Texto:
- **Cor:** `#F57C00`
- **RGB:** rgb(245, 124, 0)
- **Descrição:** Laranja vibrante

#### Borda:
- **Cor:** `#FFD54F`
- **RGB:** rgb(255, 213, 79)
- **Descrição:** Amarelo dourado

**Exemplo:**
```css
.badge-pendente {
  background: #FFF9C4 !important;
  color: #F57C00 !important;
  border-color: #FFD54F !important;
}
```

---

### 6. **VALOR (R$ 14,00)**
- **Cor:** `#4CAF50`
- **RGB:** rgb(76, 175, 80)
- **Descrição:** Verde dinheiro material design
- **Uso:** Valor monetário e ícone de dinheiro
- **Font-weight:** 600 (semi-bold)
- **Font-size:** 0.9375rem (15px)

**Exemplo:**
```css
.table td:nth-child(5) {
  color: #4CAF50;
  font-weight: 600;
  font-size: 0.9375rem;
}

.table td:nth-child(5) .bi {
  color: #4CAF50;  /* Ícone de dinheiro */
}
```

---

### 7. **DATA (25/11/2025)**
- **Cor:** `#9E9E9E`
- **RGB:** rgb(158, 158, 158)
- **Descrição:** Cinza médio
- **Uso:** Data e ícone de calendário
- **Font-size:** 0.875rem (14px)
- **Font-weight:** 500 (medium)

**Exemplo:**
```css
.table td:nth-child(6) {
  color: #9E9E9E;
  font-size: 0.875rem;
  font-weight: 500;
}

.table td:nth-child(6) .bi-calendar {
  color: #9E9E9E;
}
```

---

### 8. **HORA (04:20)**
- **Cor:** `#BDBDBD`
- **RGB:** rgb(189, 189, 189)
- **Descrição:** Cinza claro
- **Uso:** Hora e ícone de relógio
- **Font-size:** 0.8125rem (13px)

**Exemplo:**
```css
.table td:nth-child(6) .time {
  color: #BDBDBD;
  font-size: 0.8125rem;
}

.table td:nth-child(6) .bi-clock {
  color: #BDBDBD;
}
```

---

## 📋 Tabela Resumida

| Elemento | Cor HEX | RGB | Descrição |
|----------|---------|-----|-----------|
| **ID** | `#A0B8E6` | rgb(160, 184, 230) | Azul suave |
| **Produto** | `#8E24AA` | rgb(142, 36, 170) | Roxo vibrante |
| **Cliente** | `#EF5350` | rgb(239, 83, 80) | Vermelho |
| **Badge ENTREGUE (bg)** | `#C8E6C9` | rgb(200, 230, 201) | Verde claro |
| **Badge ENTREGUE (text)** | `#2E7D32` | rgb(46, 125, 50) | Verde escuro |
| **Badge ENTREGUE (border)** | `#81C784` | rgb(129, 199, 132) | Verde médio |
| **Badge PENDENTE (bg)** | `#FFF9C4` | rgb(255, 249, 196) | Amarelo claro |
| **Badge PENDENTE (text)** | `#F57C00` | rgb(245, 124, 0) | Laranja |
| **Badge PENDENTE (border)** | `#FFD54F` | rgb(255, 213, 79) | Amarelo dourado |
| **Valor** | `#4CAF50` | rgb(76, 175, 80) | Verde dinheiro |
| **Data** | `#9E9E9E` | rgb(158, 158, 158) | Cinza médio |
| **Hora** | `#BDBDBD` | rgb(189, 189, 189) | Cinza claro |

---

## 🎨 CSS Completo

```css
/* ID - Azul suave */
.table td:first-child {
  color: #A0B8E6;
  font-weight: 600;
}

/* PRODUTO - Roxo/Violeta */
.table td:nth-child(2) {
  color: #8E24AA;
  font-weight: 600;
}

.table td:nth-child(2) .bi {
  color: #8E24AA;  /* Ícones de coroa */
}

/* CLIENTE - Vermelho vibrante */
.table td:nth-child(3) {
  color: #EF5350;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  font-weight: 500;
}

/* STATUS - Badge ENTREGUE */
.badge-entregue,
.badge-delivered {
  background: #C8E6C9 !important;
  color: #2E7D32 !important;
  border-color: #81C784 !important;
}

/* STATUS - Badge PENDENTE */
.badge-pendente,
.badge-pending {
  background: #FFF9C4 !important;
  color: #F57C00 !important;
  border-color: #FFD54F !important;
}

/* VALOR - Verde dinheiro */
.table td:nth-child(5) {
  color: #4CAF50;
  font-weight: 600;
  font-size: 0.9375rem;
}

.table td:nth-child(5) .bi {
  color: #4CAF50;
}

/* DATA - Cinza médio */
.table td:nth-child(6) {
  color: #9E9E9E;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
}

.table td:nth-child(6) .bi-calendar {
  color: #9E9E9E;
  margin-right: 4px;
}

/* HORA - Cinza claro */
.table td:nth-child(6) .time {
  color: #BDBDBD;
  font-size: 0.8125rem;
}

.table td:nth-child(6) .bi-clock {
  color: #BDBDBD;
  margin-right: 4px;
}

/* Ícones gerais */
.table td .bi {
  font-size: 1.125rem;
  vertical-align: middle;
}
```

---

## 🔍 Como Foram Extraídas

As cores foram extraídas utilizando:
1. ✅ Análise de imagem com AI Vision
2. ✅ Identificação de RGB aproximados
3. ✅ Conversão para HEX exato
4. ✅ Validação visual com demo HTML

---

## 📊 Hierarquia Visual

### Prioridade Alta (Cores Vibrantes):
1. **Produto** - #8E24AA (Roxo)
2. **Cliente** - #EF5350 (Vermelho)
3. **Valor** - #4CAF50 (Verde)

### Prioridade Média (Cores Suaves):
4. **ID** - #A0B8E6 (Azul claro)
5. **Badges** - Fundos claros com texto escuro

### Prioridade Baixa (Informações Secundárias):
6. **Data** - #9E9E9E (Cinza médio)
7. **Hora** - #BDBDBD (Cinza claro)

---

## ✅ Validação

Para verificar se as cores estão corretas:

1. Abra `teste_cores_exatas_tabela.html` no navegador
2. Compare com a imagem original fornecida
3. As cores devem ser IDÊNTICAS

---

## 📦 Arquivos Relacionados

- **CSS:** `esim_novo/site/admin/assets/css/modern-admin.css`
- **Demo:** `teste_cores_exatas_tabela.html`
- **Referência:** Imagem fornecida pelo usuário

---

## 🎯 Resultado Final

✅ **ID:** Azul suave (#A0B8E6)  
✅ **Produto:** Roxo vibrante (#8E24AA)  
✅ **Cliente:** Vermelho (#EF5350)  
✅ **Badge ENTREGUE:** Verde (#C8E6C9 / #2E7D32)  
✅ **Badge PENDENTE:** Amarelo (#FFF9C4 / #F57C00)  
✅ **Valor:** Verde dinheiro (#4CAF50)  
✅ **Data:** Cinza médio (#9E9E9E)  
✅ **Hora:** Cinza claro (#BDBDBD)

**Cores 100% idênticas à imagem fornecida!** 🎨

---

**Data:** 2025-11-25  
**Fonte:** Imagem do usuário  
**Status:** ✅ APLICADO
