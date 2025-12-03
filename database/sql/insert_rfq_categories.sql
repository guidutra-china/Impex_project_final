-- Insert RFQ/Project Expense Categories
-- Run this SQL directly in your database if the seeder fails due to existing categories

-- First, check if the parent category exists
SELECT * FROM financial_categories WHERE code = 'RFQ-EXPENSES';

-- If it doesn't exist, insert the parent category
INSERT INTO financial_categories (name, code, description, type, is_system, is_active, sort_order, created_at, updated_at)
SELECT 'Despesas de RFQ/Projeto', 'RFQ-EXPENSES', 'Despesas específicas de RFQs e projetos de importação/exportação', 'expense', 1, 1, 250, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXPENSES');

-- Get the parent_id for subcategories
SET @parent_id = (SELECT id FROM financial_categories WHERE code = 'RFQ-EXPENSES');

-- Insert subcategories (only if they don't exist)
INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Testes e Certificações', 'RFQ-EXP-TESTS', 'Testes de qualidade, certificações, laudos técnicos', 'expense', @parent_id, 1, 251, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-TESTS');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Viagens de Negócios', 'RFQ-EXP-TRAVEL', 'Visitas a fornecedores, feiras, inspeções de fábrica', 'expense', @parent_id, 1, 252, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-TRAVEL');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Serviços de Terceiros', 'RFQ-EXP-THIRD-PARTY', 'Despachantes, consultores, tradutores, advogados', 'expense', @parent_id, 1, 253, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-THIRD-PARTY');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Custos Bancários', 'RFQ-EXP-BANK', 'Transferências internacionais, cartas de crédito, câmbio', 'expense', @parent_id, 1, 254, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-BANK');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Amostras', 'RFQ-EXP-SAMPLES', 'Envio de amostras, protótipos', 'expense', @parent_id, 1, 255, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-SAMPLES');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Documentação', 'RFQ-EXP-DOCS', 'Legalização, apostilamento, certificados de origem', 'expense', @parent_id, 1, 256, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-DOCS');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Armazenagem Temporária', 'RFQ-EXP-STORAGE', 'Armazém antes de envio final', 'expense', @parent_id, 1, 257, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-STORAGE');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Seguros Específicos', 'RFQ-EXP-INSURANCE', 'Seguros específicos do RFQ/projeto', 'expense', @parent_id, 1, 258, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-INSURANCE');

-- Verify insertion
SELECT * FROM financial_categories WHERE code LIKE 'RFQ-%' ORDER BY sort_order;
