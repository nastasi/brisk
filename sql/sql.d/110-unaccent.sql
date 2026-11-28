--
--  Easy signup: unaccent() to compare the nicknames apart from the accents
--  (nick_is_free() and nick_fun_free_pairs() in Obj/dbase_pgsql.phh), so
--  that "bia" cannot be registered when "bià" exists. It is an official
--  extension of PostgreSQL: it needs a superuser (or the owner of the
--  database) once.
--
CREATE EXTENSION IF NOT EXISTS unaccent;
