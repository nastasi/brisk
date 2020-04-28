--
-- ttype = 1 -> certified
-- ttype = 2 -> guaranteed
-- ttype = 3 -> authorized

ALTER TABLE #PFX#bin5_matches DROP COLUMN ttype;
ALTER TABLE #PFX#bin5_matches ADD COLUMN ttype integer DEFAULT 2;

