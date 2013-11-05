DROP TABLE #PFX#bin5_table_orders;
CREATE TABLE #PFX#bin5_table_orders (
       mcode  integer REFERENCES #PFX#bin5_matches (code) ON DELETE cascade ON UPDATE cascade,
       pos    integer,
       ucode  integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade
       );

ALTER TABLE #PFX#bin5_matches DROP COLUMN mult_next;
ALTER TABLE #PFX#bin5_matches ADD COLUMN mult_next  integer DEFAULT -1;  -- next multiplier

ALTER TABLE #PFX#bin5_matches DROP COLUMN mazzo_next;
ALTER TABLE #PFX#bin5_matches ADD COLUMN mazzo_next integer DEFAULT -1;  -- next card shaker

       
ALTER TABLE #PFX#bin5_games DROP COLUMN asta_pnt;
ALTER TABLE #PFX#bin5_games ADD COLUMN asta_pnt     integer DEFAULT -1;  -- curr bet points

ALTER TABLE #PFX#bin5_games DROP COLUMN pnt;
ALTER TABLE #PFX#bin5_games ADD COLUMN pnt          integer DEFAULT -1;  -- curr points made

ALTER TABLE #PFX#bin5_games DROP COLUMN asta_win;
ALTER TABLE #PFX#bin5_games ADD COLUMN asta_win     integer DEFAULT -1;  -- curr caller id

ALTER TABLE #PFX#bin5_games DROP COLUMN friend;
ALTER TABLE #PFX#bin5_games ADD COLUMN friend       integer DEFAULT -1;  -- curr callee id

ALTER TABLE #PFX#bin5_games DROP COLUMN mazzo;
ALTER TABLE #PFX#bin5_games ADD COLUMN mazzo        integer DEFAULT -1;  -- curr card shaker

ALTER TABLE #PFX#bin5_games DROP COLUMN mult;
ALTER TABLE #PFX#bin5_games ADD COLUMN mult         integer DEFAULT -1;  -- curr multiplier
