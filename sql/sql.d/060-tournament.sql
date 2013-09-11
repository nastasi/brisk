DROP TABLE #PFX#bin5_tournaments;
CREATE TABLE #PFX#bin5_tournaments (
       code   SERIAL PRIMARY KEY,
       name   text
       );

-- add tournaments and field in the bin5_matches table
INSERT INTO #PFX#bin5_tournaments (code, name) VALUES (1, 'normal match');
ALTER SEQUENCE #PFX#bin5_tournaments_code_seq RESTART WITH 2;

ALTER TABLE #PFX#bin5_matches DROP COLUMN tourn;
ALTER TABLE #PFX#bin5_matches ADD COLUMN tourn integer DEFAULT 1;

ALTER TABLE #PFX#bin5_matches DROP CONSTRAINT #PFX#bin5_matches_tourn_fkey;
ALTER TABLE #PFX#bin5_matches ADD FOREIGN KEY (tourn) REFERENCES #PFX#bin5_tournaments(code) ON UPDATE cascade ON DELETE cascade;

