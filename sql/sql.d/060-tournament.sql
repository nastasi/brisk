DROP TABLE #PFX#bin5_tournaments;
CREATE TABLE #PFX#bin5_tournaments (
       code   SERIAL PRIMARY KEY,
       active integer,
       name   text
       );

-- add tournaments and field in the bin5_matches table
INSERT INTO #PFX#bin5_tournaments (code, active, name) VALUES (1, 1, 'normal match');
INSERT INTO #PFX#bin5_tournaments (code, active, name) VALUES (2, 1, 'special match');
ALTER SEQUENCE #PFX#bin5_tournaments_code_seq RESTART WITH 3;

ALTER TABLE #PFX#bin5_matches DROP COLUMN tcode;
ALTER TABLE #PFX#bin5_matches ADD COLUMN tcode integer DEFAULT 1;

ALTER TABLE #PFX#bin5_matches DROP CONSTRAINT #PFX#bin5_matches_tcode_fkey;
ALTER TABLE #PFX#bin5_matches ADD FOREIGN KEY (tcode) REFERENCES #PFX#bin5_tournaments(code) ON UPDATE cascade ON DELETE cascade;

ALTER TABLE #PFX#bin5_games DROP COLUMN act;
ALTER TABLE #PFX#bin5_games ADD COLUMN act    integer;  -- end reason of the game
