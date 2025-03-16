ALTER TABLE equipment 
ADD COLUMN IF NOT EXISTS maintenance_schedule VARCHAR(50) DEFAULT NULL AFTER contract_file;
