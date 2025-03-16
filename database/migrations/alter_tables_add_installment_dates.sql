-- Add columns to equipment table
ALTER TABLE equipment 
ADD COLUMN installment_start_date DATE DEFAULT CURRENT_DATE,
ADD COLUMN installment_months INT DEFAULT 12;

-- Add columns to clinic_records table
ALTER TABLE clinic_records 
ADD COLUMN payment_start_date DATE DEFAULT CURRENT_DATE,
ADD COLUMN payment_months INT DEFAULT 12;
