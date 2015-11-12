--
--  Table to manage users trust network
--
DROP TABLE IF EXISTS #PFX#usersnet;
CREATE TABLE #PFX#usersnet (
       owner      integer REFERENCES #PFX#users (code)
                  ON DELETE cascade ON UPDATE cascade, -- network owner
       target     integer REFERENCES #PFX#users (code)
                  ON DELETE cascade ON UPDATE cascade, -- evaluated user
       friend     integer,                             -- friendship level
       skill      integer,                             -- skill level
       trust      integer,                             -- auth
       ctime      timestamp DEFAULT now(),             -- creation time
       mtime      timestamp DEFAULT to_timestamp(0)    -- modification time
       );

DROP INDEX IF EXISTS #PFX#usersnet_owner_idx;
DROP INDEX IF EXISTS #PFX#usersnet_target_idx;
DROP INDEX IF EXISTS #PFX#usersnet_owner_target_idx;
CREATE INDEX #PFX#usersnet_owner_idx ON #PFX#usersnet (owner);
CREATE INDEX #PFX#usersnet_target_idx ON #PFX#usersnet (target);
CREATE UNIQUE INDEX #PFX#usersnet_owner_target_idx ON #PFX#usersnet (owner,target);


