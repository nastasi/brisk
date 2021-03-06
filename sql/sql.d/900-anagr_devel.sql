--
-- Populate users db.
--

-- macro for user_flag bit-field
-- define('USER_FLAG_TY_ALL',     0xff0000); // done
-- define('USER_FLAG_TY_NORM',    0x010000); // done
-- define('USER_FLAG_TY_SUPER',   0x020000); // done
-- define('USER_FLAG_TY_CERT',    0x040000); // done
-- define('USER_FLAG_TY_APPR',    0x080000); // done
-- define('USER_FLAG_TY_FIRONLY', 0x200000); // done
-- define('USER_FLAG_TY_ADMIN',   0x400000); // done
-- define('USER_FLAG_TY_DISABLE', 0x800000); // done


DELETE FROM #PFX#users WHERE code = 10101;
DELETE FROM #PFX#users WHERE guar_code = 10101 AND code != 10101;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10101, 'uno', md5('one'), 'uno@pluto.com', CAST (X'00450000' as integer), 10101);
DELETE FROM #PFX#users WHERE code = 10102;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10102, 'due', md5('two'), 'due@pluto.com', CAST (X'00010000' as integer), 10101);
DELETE FROM #PFX#users WHERE code = 10103;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10103, 'tre', md5('thr'), 'tre@pluto.com', CAST (X'00030000' as integer), 10102);
DELETE FROM #PFX#users WHERE code = 10104;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10104, 'qua', md5('for'), 'qua@pluto.com', CAST (X'00010000' as integer), 10102);
DELETE FROM #PFX#users WHERE code = 10105;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10105, 'cin', md5('fiv'), 'cin@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10106;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10106, 'sei', md5('six'), 'sei@pluto.com', CAST (X'00210000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10107;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10107, E'"/\\''|', md5('sev'), 'sev@pluto.com', CAST (X'00210000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10108;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10108, E'appr', md5('appr'), 'appr@pluto.com', CAST (X'00080000' as integer), 10103);

DELETE FROM #PFX#users WHERE code = 10109;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10109, 'bosi', md5('bosi'), 'bosi@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10110;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10110, 'bono', md5('bono'), 'bono@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10111;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10111, 'nosi', md5('nosi'), 'nosi@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10112;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10112, 'sino', md5('sino'), 'sino@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10113;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10113, 'doub', md5('boub'), 'doub@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10114;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10114, 'selfguar', md5('self'), 'self@pluto.com', CAST (X'00010000' as integer), 10103);
DELETE FROM #PFX#users WHERE code = 10115;
INSERT INTO #PFX#users (code, login, pass, email, type, guar_code) VALUES (10115, 'bonoonly', md5('bonoonly'), 'bonoonly@pluto.com', CAST (X'00010000' as integer), 10101);

ALTER SEQUENCE #PFX#users_code_seq RESTART WITH 10116;
