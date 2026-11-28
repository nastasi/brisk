--
--  "Forgotten password?" (forgotten_password.php, new_password.php):
--  links sent by e-mail (only the sha256 of the code)
--
--  A database that ran an older version of this file has to change it by hand:
--    ALTER TABLE #PFX#passwd_reset RENAME COLUMN impronta TO code_hash;
--    ALTER TABLE #PFX#passwd_reset RENAME COLUMN creato TO ctime;
--    ALTER TABLE #PFX#passwd_reset RENAME COLUMN usato TO used;
--    DROP TABLE IF EXISTS #PFX#users_passwd_backup;
--
CREATE TABLE IF NOT EXISTS #PFX#passwd_reset (
       code_hash  text PRIMARY KEY,                        -- sha256 of the code of the link
       login      text NOT NULL,
       ip         text,
       ctime      timestamp DEFAULT now() NOT NULL,
       used       timestamp                                -- NULL until the link is used
       );
CREATE INDEX IF NOT EXISTS #PFX#passwd_reset_login ON #PFX#passwd_reset (login, ctime);
