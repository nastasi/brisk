-- add guarantee of a user
ALTER TABLE #PFX#users DROP COLUMN guar_code;
ALTER TABLE #PFX#users ADD COLUMN guar_code integer DEFAULT -1;  -- who guaranted this user
