# 📊 Comparação: Query de Receita Total

## ❌ Query ANTERIOR (Com Problema)

```php
$k_revenue = (float)($pdo->query("
  SELECT COALESCE(SUM(COALESCE(final_price_cents, price_cents))/100, 0) AS s 
  FROM orders 
  WHERE status IN ('delivered','completed','paid')
")->fetch()['s'] ?? 0);
```

### Problemas Identificados:

| Problema | Descrição | Impacto |
|----------|-----------|---------|
| **COALESCE aninhado** | `COALESCE(final_price_cents, price_cents)` pode retornar 0 ou valores inválidos | Soma valores incorretos |
| **Falta validação** | Não verifica se os valores são > 0 | Pode contar registros com valor 0 |
| **NULL não tratado** | NULL é tratado implicitamente pelo COALESCE | Comportamento inconsistente |

### Exemplos de Casos Problemáticos:

```sql
-- Pedido com final_price_cents = 0 e price_cents = 5000
-- RESULTADO ANTERIOR: Soma 0 (incorreto, deveria somar 5000)

-- Pedido com final_price_cents = NULL e price_cents = 0  
-- RESULTADO ANTERIOR: Soma 0 (correto, mas por acaso)

-- Pedido com final_price_cents = -100 e price_cents = 3000
-- RESULTADO ANTERIOR: Soma -100 (incorreto, valores negativos deviam ser ignorados)
```

---

## ✅ Query CORRIGIDA

```php
$revenueResult = $pdo->query("
  SELECT 
    COALESCE(
      SUM(
        CASE 
          WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
          WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
          ELSE 0
        END
      ) / 100, 
      0
    ) AS s 
  FROM orders 
  WHERE status IN ('delivered','completed','paid')
");
$k_revenue = (float)($revenueResult->fetch()['s'] ?? 0);
```

### Melhorias Implementadas:

| Melhoria | Implementação | Benefício |
|----------|---------------|-----------|
| **CASE explícito** | `CASE WHEN ... THEN ... END` | Lógica clara e previsível |
| **Validação de NULL** | `IS NOT NULL` antes de usar | Evita erros de NULL |
| **Validação de valor** | `> 0` garante valores positivos | Ignora zeros e negativos |
| **Prioridade clara** | final_price_cents primeiro | Usa sempre o valor mais atual |

### Comportamento com os Mesmos Casos:

```sql
-- Pedido com final_price_cents = 0 e price_cents = 5000
-- RESULTADO CORRIGIDO: Soma 5000 (correto! Usa price_cents)

-- Pedido com final_price_cents = NULL e price_cents = 0  
-- RESULTADO CORRIGIDO: Soma 0 (correto, ambos inválidos)

-- Pedido com final_price_cents = -100 e price_cents = 3000
-- RESULTADO CORRIGIDO: Soma 3000 (correto! Ignora negativo, usa price_cents)
```

---

## 🔍 Teste de Validação

Execute esta query para comparar os dois métodos:

```sql
SELECT 
  'Método Antigo' AS metodo,
  COALESCE(SUM(COALESCE(final_price_cents, price_cents))/100, 0) AS receita
FROM orders 
WHERE status IN ('delivered','completed','paid')

UNION ALL

SELECT 
  'Método Corrigido' AS metodo,
  COALESCE(
    SUM(
      CASE 
        WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
        WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
        ELSE 0
      END
    ) / 100, 
    0
  ) AS receita
FROM orders 
WHERE status IN ('delivered','completed','paid');
```

**Resultado esperado:**
- Se houver diferença, o "Método Corrigido" está ignorando valores inválidos
- O valor correto é o do "Método Corrigido"

---

## 📋 Debug de Pedidos

Para identificar pedidos que causam diferença:

```sql
SELECT 
  id,
  status,
  price_cents,
  final_price_cents,
  -- Valor usado pelo método antigo
  COALESCE(final_price_cents, price_cents) as metodo_antigo,
  -- Valor usado pelo método corrigido
  CASE 
    WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
    WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
    ELSE 0
  END as metodo_corrigido,
  -- Diferença
  (CASE 
    WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
    WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
    ELSE 0
  END) - COALESCE(final_price_cents, price_cents) as diferenca
FROM orders 
WHERE status IN ('delivered','completed','paid')
  AND (CASE 
    WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
    WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
    ELSE 0
  END) != COALESCE(final_price_cents, price_cents)
ORDER BY created_at DESC;
```

---

## 🎯 Conclusão

| Aspecto | Anterior | Corrigido |
|---------|----------|-----------|
| **Precisão** | ⚠️ Média | ✅ Alta |
| **Validação** | ❌ Implícita | ✅ Explícita |
| **Manutenção** | ⚠️ Difícil | ✅ Fácil |
| **Confiabilidade** | ⚠️ Inconsistente | ✅ Consistente |

**Recomendação:** Use sempre o método corrigido para garantir cálculos precisos e confiáveis.
