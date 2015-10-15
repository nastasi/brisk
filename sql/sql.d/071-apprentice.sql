--
--  Table to manage mails sent to users
--
DROP TABLE #PFX#selfreg_chk;
CREATE TABLE #PFX#selfreg_chk (
       ip         integer,                           -- ip v4 address
       atime      timestamp DEFAULT to_timestamp(0)  -- access time
       );
