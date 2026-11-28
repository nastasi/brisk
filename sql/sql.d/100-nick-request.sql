--
--  Easy signup: the account is made at once with the nickname the player
--  chose as its login (users.login, its identity), and plays under a
--  temporary nickname until a moderator approves the chosen one in
--  usermgmt.php. The temporary nickname lives here, one row per account
--  that still has it; users is not changed. Approving the nickname deletes
--  the row; the row goes away with its account too (ON DELETE CASCADE).
--
--  USER_FLAG_TY_NICKTMP (0x1000000) is not stored in users.type: the code
--  adds it only to the flags sent to the players (User::flags_client()).
--
--  The name the other players see is users_vis.vis_login: the temporary
--  nickname while there is one, the login otherwise. Everything public
--  (room, tables, rankings) uses it; logs, mails and bans use the login.
--  PostgreSQL expands "u.*" when the view is made: a column added later to
--  users shows in the view only after running this file again.
--
--  A database that ran an older version of this file has to drop what it
--  made by hand, before running this one:
--    ALTER TABLE #PFX#users DROP COLUMN IF EXISTS nick_req;
--    DROP TABLE IF EXISTS #PFX#users_nickreq;
--    UPDATE #PFX#users SET type = type & ~(CAST (X'00100000' as integer));
--  (its pending signups have the temporary nickname as login: they have
--  to be fixed by hand or signed up again)
--
CREATE TABLE IF NOT EXISTS #PFX#users_nicktmp (
       ucode      integer PRIMARY KEY REFERENCES #PFX#users (code) ON DELETE CASCADE,
       nick       text NOT NULL,
       ctime      timestamp DEFAULT now() NOT NULL
       );
CREATE UNIQUE INDEX IF NOT EXISTS #PFX#users_nicktmp_nick ON #PFX#users_nicktmp (lower(nick));

CREATE OR REPLACE VIEW #PFX#users_vis AS
    SELECT u.*, t.nick AS nick_tmp, coalesce(t.nick, u.login) AS vis_login
        FROM #PFX#users AS u LEFT JOIN #PFX#users_nicktmp AS t ON t.ucode = u.code;
