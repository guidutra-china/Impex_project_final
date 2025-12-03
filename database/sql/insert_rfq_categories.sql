-- Insert RFQ/Project Expense Categories (English)
-- Run this SQL directly in your database if the seeder fails due to existing categories

-- First, check if the parent category exists
SELECT * FROM financial_categories WHERE code = 'RFQ-EXPENSES';

-- If it doesn't exist, insert the parent category
INSERT INTO financial_categories (name, code, description, type, is_system, is_active, sort_order, created_at, updated_at)
SELECT 'RFQ/Project Expenses', 'RFQ-EXPENSES', 'Specific expenses for RFQs and import/export projects', 'expense', 1, 1, 250, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXPENSES');

-- Get the parent_id for subcategories
SET @parent_id = (SELECT id FROM financial_categories WHERE code = 'RFQ-EXPENSES');

-- Insert subcategories (only if they don't exist)
INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Tests and Certifications', 'RFQ-EXP-TESTS', 'Quality tests, certifications, technical reports', 'expense', @parent_id, 1, 251, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-TESTS');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Business Travel', 'RFQ-EXP-TRAVEL', 'Supplier visits, trade shows, factory inspections', 'expense', @parent_id, 1, 252, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-TRAVEL');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Third-Party Services', 'RFQ-EXP-THIRD-PARTY', 'Customs brokers, consultants, translators, lawyers', 'expense', @parent_id, 1, 253, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-THIRD-PARTY');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Bank Costs', 'RFQ-EXP-BANK', 'International transfers, letters of credit, foreign exchange', 'expense', @parent_id, 1, 254, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-BANK');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Samples', 'RFQ-EXP-SAMPLES', 'Sample shipping, prototypes', 'expense', @parent_id, 1, 255, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-SAMPLES');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Documentation', 'RFQ-EXP-DOCS', 'Legalization, apostille, certificates of origin', 'expense', @parent_id, 1, 256, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-DOCS');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Temporary Storage', 'RFQ-EXP-STORAGE', 'Warehouse before final shipment', 'expense', @parent_id, 1, 257, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-STORAGE');

INSERT INTO financial_categories (name, code, description, type, parent_id, is_active, sort_order, created_at, updated_at)
SELECT 'Specific Insurance', 'RFQ-EXP-INSURANCE', 'RFQ/project specific insurance', 'expense', @parent_id, 1, 258, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM financial_categories WHERE code = 'RFQ-EXP-INSURANCE');

-- Verify insertion
SELECT * FROM financial_categories WHERE code LIKE 'RFQ-%' ORDER BY sort_order;
