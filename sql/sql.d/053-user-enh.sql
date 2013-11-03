ALTER TABLE #PFX#users DROP COLUMN last_dona;
ALTER TABLE #PFX#users ADD COLUMN last_dona integer DEFAULT 0;             -- last donate

ALTER TABLE #PFX#users DROP COLUMN supp_comp;
ALTER TABLE #PFX#users ADD COLUMN supp_comp text    DEFAULT 'ff0000ffff00'; -- fg/bg supporter color

