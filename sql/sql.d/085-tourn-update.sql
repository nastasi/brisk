--  from:
--    INSERT INTO #PFX#bin5_tournaments (code, active, name) VALUES (1, 1, 'normal match');
--    INSERT INTO #PFX#bin5_tournaments (code, active, name) VALUES (2, 1, 'special match');

UPDATE #PFX#bin5_tournaments SET (name) = ('old rules: with draw') WHERE code = 1;
UPDATE #PFX#bin5_tournaments SET (name) = ('new rules: without draw') WHERE code = 2;
INSERT INTO #PFX#bin5_tournaments (code, active, name) VALUES (3, 1, 'special match');
