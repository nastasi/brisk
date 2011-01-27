-- TODO
--   DONE - CASCADE ON DELETE of orders related tables
--   DONE - STATUS on orders

DROP TABLE #PFX#users;
CREATE TABLE #PFX#users (
                        code  integer PRIMARY KEY,
                        login text,
                        pass  text,
                        email text,
                        type  integer
                        );

DROP TABLE #PFX#groups;
CREATE TABLE #PFX#groups (
                        code  integer PRIMARY KEY,
                        gname text
                        );
