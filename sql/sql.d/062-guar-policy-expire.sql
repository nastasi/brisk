-- add current terms of service version accepted by users
ALTER TABLE #PFX#users DROP COLUMN tos_vers;
ALTER TABLE #PFX#users ADD COLUMN tos_vers text DEFAULT '';  -- current accepted terms of service version

-- add reason field for disabled users
ALTER TABLE #PFX#users DROP COLUMN disa_reas;
ALTER TABLE #PFX#users ADD COLUMN disa_reas integer DEFAULT 0;  -- current disable reason

