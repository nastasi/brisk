-- add current licence version accepted by users
ALTER TABLE #PFX#users DROP COLUMN lice_vers;
ALTER TABLE #PFX#users ADD COLUMN lice_vers text DEFAULT '';  -- current accepted licence version
