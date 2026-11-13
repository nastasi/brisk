--
--  "Password dimenticata?" (password_dimenticata.php, password_nuova.php):
--  links sent by e-mail (only the sha256 of the code) and copy of the old passwords
--
CREATE TABLE IF NOT EXISTS #PFX#pw_reset (
       impronta   text PRIMARY KEY,                        -- sha256 of the code of the link
       login      text NOT NULL,
       ip         text,
       creato     timestamp DEFAULT now() NOT NULL,
       usato      timestamp                                -- NULL until the link is used
       );
CREATE INDEX IF NOT EXISTS #PFX#pw_reset_login ON #PFX#pw_reset (login, creato);

CREATE TABLE IF NOT EXISTS #PFX#users_pw_backup (
       ts         timestamp DEFAULT now() NOT NULL,
       login      text,
       code       integer,
       old_pass   text,
       ip         text
       );
