-- add current licence version accepted by users
ALTER TABLE #PFX#users DROP COLUMN lice_vers;
ALTER TABLE #PFX#users ADD COLUMN lice_vers text DEFAULT '';  -- current accepted licence version

-- add reason field for disabled users
ALTER TABLE #PFX#users DROP COLUMN disa_reas;
ALTER TABLE #PFX#users ADD COLUMN disa_reas integer DEFAULT 0;  -- current disable reason

