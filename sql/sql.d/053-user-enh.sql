ALTER TABLE #PFX#users ADD COLUMN last_dona timestamp DEFAULT to_timestamp(0)  -- last donate
ALTER TABLE #PFX#users ADD COLUMN supp_comp text      DEFAULT 'ff0000ffff00'   -- fg/bg supporter color