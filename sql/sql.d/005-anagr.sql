--
-- Populate users db.
--

-- macro for user_flag bit-field
-- define(USER_FLAG_TY_ALL,   0x000f0000); // done
-- define(USER_FLAG_TY_NORM,  0x00010000); // done
-- define(USER_FLAG_TY_SUPER, 0x00020000); // done

INSERT INTO #PFX#users VALUES (1, 'uno', md5('one'),   'uno@pluto.com', CAST (X'00020000' as integer));
INSERT INTO #PFX#users VALUES (2, 'due', md5('two'),   'due@pluto.com', CAST (X'00010000' as integer));
INSERT INTO #PFX#users VALUES (3, 'tre', md5('thr'),   'tre@pluto.com', CAST (X'00010000' as integer));
INSERT INTO #PFX#users VALUES (4, 'qua', md5('for'),   'qua@pluto.com', CAST (X'00010000' as integer));
INSERT INTO #PFX#users VALUES (5, 'cin', md5('fiv'),   'cin@pluto.com', CAST (X'00010000' as integer));

