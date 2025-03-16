-- Add downpayment column to clinic_records table
ALTER TABLE clinic_records 
ADD COLUMN downpayment DECIMAL(10,3) DEFAULT 0.000 AFTER total_amount;

-- Add downpayment column to equipment table if not exists
ALTER TABLE equipment 
ADD COLUMN downpayment DECIMAL(10,3) DEFAULT 0.000 AFTER total_cost;
