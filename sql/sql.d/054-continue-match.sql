DROP TABLE #PFX#bin5_table_order;
CREATE TABLE #PFX#bin5_table_order (
       mcode  integer REFERENCES #PFX#bin5_matches (code) ON DELETE cascade ON UPDATE cascade,
       ucode  integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade, 
       pos    integer
       );

ALTER TABLE #PFX#bin5_matches ADD COLUMN mult_next  integer DEFAULT -1;  -- next multiplier
ALTER TABLE #PFX#bin5_matches ADD COLUMN mazzo_next integer DEFAULT -1;  -- next card shaker
       
ALTER TABLE #PFX#bin5_games ADD COLUMN asta_pnt     integer DEFAULT -1;  -- curr bet points
ALTER TABLE #PFX#bin5_games ADD COLUMN pnt          integer DEFAULT -1;  -- curr points made
ALTER TABLE #PFX#bin5_games ADD COLUMN asta_win     integer DEFAULT -1;  -- curr caller id
ALTER TABLE #PFX#bin5_games ADD COLUMN friend       integer DEFAULT -1;  -- curr callee id
ALTER TABLE #PFX#bin5_games ADD COLUMN mazzo        integer DEFAULT -1;  -- curr card shaker
ALTER TABLE #PFX#bin5_games ADD COLUMN mult         integer DEFAULT -1;  -- curr multiplier
