-- Add installment_start_date column to monthly_installments table
ALTER TABLE `monthly_installments` 
ADD COLUMN `installment_start_date` date DEFAULT NULL AFTER `record_id`;

-- Update existing records to use their first due_date as installment_start_date
UPDATE `monthly_installments` 
SET `installment_start_date` = (
    SELECT MIN(due_date) 
    FROM (SELECT * FROM `monthly_installments`) AS temp 
    WHERE temp.record_type = monthly_installments.record_type 
    AND temp.record_id = monthly_installments.record_id
);

-- Add installment_start_date column to equipment table
ALTER TABLE `equipment` 
ADD COLUMN `installment_start_date` date DEFAULT NULL AFTER `contract_end_date`;

-- Update existing equipment records to use contract_start_date as installment_start_date
UPDATE `equipment` 
SET `installment_start_date` = contract_start_date 
WHERE payment_type = 'installment';
