--
--  Table to manage mails sent to users
--
DROP TABLE IF EXISTS #PFX#selfreg_chk;
CREATE TABLE #PFX#selfreg_chk (
       ip         integer,                           -- ip v4 address
       atime      timestamp DEFAULT to_timestamp(0)  -- access time
       );

--
--  Add counters to show how many matches and games are played
--
ALTER TABLE #PFX#users DROP COLUMN match_cnt;
ALTER TABLE #PFX#users ADD COLUMN match_cnt integer DEFAULT 0;
ALTER TABLE #PFX#users DROP COLUMN game_cnt;
ALTER TABLE #PFX#users ADD COLUMN game_cnt integer DEFAULT 0;
