-- add tourn_pts to bin5_games table
ALTER TABLE #PFX#bin5_games DROP COLUMN tourn_pts;
ALTER TABLE #PFX#bin5_games ADD COLUMN tourn_pts integer DEFAULT -1;  -- points at the beginning of the hand

