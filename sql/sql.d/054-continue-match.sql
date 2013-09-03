DROP TABLE #PFX#bin5_table_order;
CREATE TABLE #PFX#bin5_table_order (
       mcode  integer REFERENCES #PFX#bin5_matches (code) ON DELETE cascade ON UPDATE cascade,
       ucode  integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade, 
       pos    integer
       );

ALTER TABLE #PFX#bin5_matches ADD COLUMN mult_next  integer DEFAULT 1;   -- current multiplier
ALTER TABLE #PFX#bin5_matches ADD COLUMN mazzo_next integer;             -- current card shaker
       
