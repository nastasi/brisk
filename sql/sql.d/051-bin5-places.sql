--
-- try to start with a single table for all placings
--
-- tri = 0     lo = 0
-- mon = 2     hi = 1
-- wee = 4
--

DROP TABLE IF EXISTS #PFX#bin5_places_mtime;
CREATE TABLE #PFX#bin5_places_mtime (
       code   int,
       mtime  timestamp
);
INSERT INTO #PFX#bin5_places_mtime (code, mtime) VALUES (0, now());

DROP TABLE IF EXISTS #PFX#bin5_places;
CREATE TABLE #PFX#bin5_places (
       type   integer,
       rank   integer,
       ucode  integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade, 
       login  text,
       pts    integer,
       games  integer,
       score  float
);
