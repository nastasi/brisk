--
--  Table to manage users trust network
--
DROP TABLE IF EXISTS #PFX#usersnet;
CREATE TABLE #PFX#usersnet (
       owner      integer REFERENCES #PFX#users (code)
                  ON DELETE cascade ON UPDATE cascade, -- network owner
       inher      integer NULL REFERENCES #PFX#users (code)
                  ON DELETE cascade ON UPDATE cascade, -- inheriting user
       target     integer REFERENCES #PFX#users (code)
                  ON DELETE cascade ON UPDATE cascade, -- evaluated user
       friend     integer,                             -- friendship level
                       -- 1"black", 2"unknown", 3"test", 4"friend", 5"bff"
       skill      float,                               -- skill level
       trust      float,                               -- auth
       ctime      timestamp DEFAULT now(),             -- creation time
       mtime      timestamp DEFAULT to_timestamp(0)    -- modification time
       );

DROP INDEX IF EXISTS #PFX#usersnet_owner_idx;
DROP INDEX IF EXISTS #PFX#usersnet_target_idx;
DROP INDEX IF EXISTS #PFX#usersnet_owner_target_idx;
DROP INDEX IF EXISTS #PFX#usersnet_owner_inher_idx;
DROP INDEX IF EXISTS #PFX#usersnet_owner_target_inher_idx;

CREATE INDEX #PFX#usersnet_owner_idx ON #PFX#usersnet (owner);
CREATE INDEX #PFX#usersnet_target_idx ON #PFX#usersnet (target);
CREATE INDEX #PFX#usersnet_owner_target_idx ON #PFX#usersnet (owner, target);
CREATE INDEX #PFX#usersnet_owner_inher_idx ON #PFX#usersnet (owner,inher);
CREATE UNIQUE INDEX #PFX#usersnet_owner_target_inher_idx ON #PFX#usersnet (owner, target, inher);

DROP VIEW #PFX#usersnet_wideskill;
CREATE VIEW #PFX#usersnet_wideskill
    AS SELECT un.owner, ur.target, SUM(ur.skill * un.trust) / SUM(un.trust) as skill, count(*) as count
        FROM bsk_usersnet AS un, bsk_usersnet AS ur
        WHERE un.owner = 10101                           -- owner is xxx
            AND un.target = ur.owner AND un.friend >= 4  -- 'un' is, at least, our friend
        GROUP BY un.owner, ur.target;

DROP VIEW #PFX#usersnet_narrowskill;
CREATE VIEW #PFX#usersnet_narrowskill
    AS SELECT un.owner, ur.target, SUM(ur.skill * un.trust) / SUM(un.trust) as skill, count(*) as count
        FROM bsk_usersnet AS un, bsk_usersnet AS ur      -- 'un' primary records, 'ur' inheriting records
        WHERE un.owner = 10101                           -- owner is xxx
            AND un.target = ur.owner AND un.friend = 5   -- 'un' is, at least, our friend
        GROUP BY un.owner, ur.target;

