-- TODO
--   DONE - CASCADE ON DELETE of orders related tables
--   DONE - STATUS on orders

DROP TABLE #PFX#users;
CREATE TABLE #PFX#users (
                        code  SERIAL PRIMARY KEY,
                        login text UNIQUE,
                        pass  text,
                        email text UNIQUE,
                        type  integer
                        );

DROP TABLE #PFX#groups;
CREATE TABLE #PFX#groups (
                        code  SERIAL PRIMARY KEY,
                        gname text
                        );
