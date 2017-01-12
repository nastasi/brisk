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
                       -- 1"black", 2"unknown", 3"test", 4"friend", 5"bff"
       skill      float,                               -- skill level
       trust      float,                               -- auth
       ctime      timestamp DEFAULT now(),             -- creation time
       mtime      timestamp DEFAULT to_timestamp(0)    -- modification time
       );

DROP INDEX IF EXISTS #PFX#usersnet_owner_idx;
DROP INDEX IF EXISTS #PFX#usersnet_target_idx;
DROP INDEX IF EXISTS #PFX#usersnet_owner_target_idx;

CREATE INDEX #PFX#usersnet_owner_idx ON #PFX#usersnet (owner);
CREATE INDEX #PFX#usersnet_target_idx ON #PFX#usersnet (target);
CREATE UNIQUE INDEX #PFX#usersnet_owner_target_idx ON #PFX#usersnet (owner, target);

DROP VIEW #PFX#usersnet_widefriend;
CREATE VIEW #PFX#usersnet_widefriend
    AS SELECT un.owner, ur.target, ur.friend, count(*) as count
        FROM #PFX#usersnet AS un, #PFX#usersnet AS ur
        WHERE un.target = ur.owner AND un.friend >= 4  -- 'un' is, at least, our friend
        GROUP BY un.owner, ur.target, ur.friend;

DROP VIEW #PFX#usersnet_narrowfriend;
CREATE VIEW #PFX#usersnet_narrowfriend
    AS SELECT un.owner, ur.target, ur.friend, count(*) as count
        FROM #PFX#usersnet AS un, #PFX#usersnet AS ur
        WHERE un.target = ur.owner AND un.friend >= 5  -- 'un' is, at least, our friend
        GROUP BY un.owner, ur.target, ur.friend;

DROP VIEW #PFX#usersnet_wideskill;
CREATE VIEW #PFX#usersnet_wideskill
    AS SELECT un.owner, ur.target, SUM(ur.skill * un.trust) / SUM(un.trust) as skill, count(*) as count
        FROM #PFX#usersnet AS un, #PFX#usersnet AS ur
        WHERE un.target = ur.owner AND un.friend >= 4   -- 'un' is, at least, our friend
            AND ur.friend > 2
        GROUP BY un.owner, ur.target;

DROP VIEW #PFX#usersnet_narrowskill;
CREATE VIEW #PFX#usersnet_narrowskill
    AS SELECT
        un.owner AS owner
        , ur.target AS target
        , SUM(ur.skill * un.trust) / SUM(un.trust) AS skill
        , COUNT(*) AS count
        FROM #PFX#usersnet AS un, #PFX#usersnet as ur
        WHERE un.target = ur.owner AND un.friend = 5    -- 'ur' owner must be bbf of un.owner
            AND ur.friend > 2
    GROUP BY un.owner, ur.target;

DROP VIEW #PFX#usersnet_narrowblack;
CREATE VIEW #PFX#usersnet_narrowblack
    AS SELECT DISTINCT un.owner AS owner, ur.target AS target, 1 AS black_cnt
        FROM #PFX#usersnet AS un, #PFX#usersnet as ur
        WHERE un.target = ur.owner
            AND un.friend = 5              -- ur owner must be bbf of un.owner
            AND ur.friend = 1;             -- ur must be blacked

DROP VIEW #PFX#usersnet_party;
CREATE VIEW #PFX#usersnet_party
    AS (SELECT ns.*, nb.black_cnt FROM #PFX#usersnet_narrowskill AS ns
        LEFT JOIN #PFX#usersnet_narrowblack AS nb
            USING (owner, target)
            -- all except targets managed directly by the owner
            WHERE black_cnt is null
                AND target NOT IN (SELECT target FROM #PFX#usersnet AS du WHERE du.owner = ns.owner)
        UNION ALL
            (SELECT owner, target, skill, 1 AS count, null AS black_cnt
                FROM #PFX#usersnet
                WHERE friend > 2))
        ORDER BY target;
