-- primary owner 10101
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10102;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10102, 4, 2, 5);
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10103;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10103, 5, 2, 3);
--   doub
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10113;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10113, 5, 2, 5);

-- primary owner: discarded target
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10104;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10104, 1, 2, 5);

-- secondary owners for 10101
DELETE FROM  #PFX#usersnet WHERE owner = 10102 AND target = 10105;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10102, 10105, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10105;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10105, 4, 3, 4);
DELETE FROM  #PFX#usersnet WHERE owner = 10104 AND target = 10105;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10104, 10105, 4, 5, 4);
DELETE FROM  #PFX#usersnet WHERE owner = 10113 AND target = 10105;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10113, 10105, 4, 2, 5);

DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10107;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10107, 3, 3, 4);

-- check sit permission
--  bosi
DELETE FROM  #PFX#usersnet WHERE owner = 10102 AND target = 10109;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10102, 10109, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10109;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10109, 4, 2, 3);

--  bono
DELETE FROM  #PFX#usersnet WHERE owner = 10102 AND target = 10110;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10102, 10110, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10110;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10110, 1, 2, 3);

--  nosi
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10111;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10111, 1, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10102 AND target = 10111;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10102, 10111, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10111;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10111, 4, 2, 3);

--  sino
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10112;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10112, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10102 AND target = 10112;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10102, 10112, 4, 2, 3);
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10112;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10112, 1, 2, 3);

-- bonoonly
DELETE FROM  #PFX#usersnet WHERE owner = 10103 AND target = 10115;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10103, 10115, 1, 0, 0);

SELECT * FROM #PFX#usersnet WHERE owner = 10101 OR owner IN (10102, 10103, 10113) ORDER BY target;

SELECT * FROM #PFX#usersnet_wideskill ORDER BY owner, target;

SELECT * FROM #PFX#usersnet_narrowskill ORDER BY owner, target;

SELECT owner, target, skill, 1 AS count, us.login as login  FROM #PFX#usersnet, #PFX#users as us WHERE owner = 10101 AND us.code = target AND friend > 2 ORDER BY target;

SELECT ns.*, us.login AS login FROM #PFX#usersnet_narrowskill as ns, #PFX#users AS us WHERE owner = 10101
       AND ns.target NOT IN (SELECT target FROM #PFX#usersnet WHERE owner = 10101)
AND us.code = ns.target UNION SELECT owner, target, skill, 1 AS count
-- , 0 AS black
, us.login as login  FROM #PFX#usersnet, #PFX#users as us WHERE owner = 10101 AND us.code = target AND friend > 2 ORDER BY target;

SELECT us.login, pa.* FROM #PFX#usersnet_party pa, #PFX#users as us WHERE pa.target = us.code AND pa.owner = 10101;

SELECT * FROM #PFX#usersnet_widefriend ORDER BY owner, target, friend;
SELECT * FROM #PFX#usersnet_narrowfriend ORDER BY owner, target, friend;
