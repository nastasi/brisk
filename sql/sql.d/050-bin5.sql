--
-- briskin5 (bin5) related tables
--

DROP TABLE IF EXISTS #PFX#bin5_matches;
CREATE TABLE #PFX#bin5_matches (
       code   SERIAL PRIMARY KEY,
       ttok   text UNIQUE,              -- token associated to the match
       tidx   integer                   -- table index
       );

DROP TABLE IF EXISTS #PFX#bin5_games;
CREATE TABLE #PFX#bin5_games (
       code   SERIAL PRIMARY KEY,
       mcode  integer REFERENCES #PFX#bin5_matches (code) ON DELETE cascade ON UPDATE cascade,
       tstamp timestamp                 -- end game time
       );
       
DROP TABLE IF EXISTS #PFX#bin5_points;
CREATE TABLE #PFX#bin5_points (
       gcode  integer REFERENCES #PFX#bin5_games (code) ON DELETE cascade ON UPDATE cascade,
       ucode  integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade, 
       pts    integer                   -- points                 
       );

