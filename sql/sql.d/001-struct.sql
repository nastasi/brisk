-- TODO
--   DONE - CASCADE ON DELETE of orders related tables
--   DONE - STATUS on orders

DROP TABLE IF EXISTS #PFX#users;
CREATE TABLE #PFX#users (
       code   SERIAL PRIMARY KEY,
       login  text UNIQUE,
       pass   text,
       email  text UNIQUE,
       type   integer,
       tsusp  timestamp DEFAULT to_timestamp(0), -- disable timeout
       mtime  timestamp DEFAULT to_timestamp(0)  -- last access
       );

DROP TABLE IF EXISTS #PFX#groups;
CREATE TABLE #PFX#groups (
       code   SERIAL PRIMARY KEY,
       name   text
       );


