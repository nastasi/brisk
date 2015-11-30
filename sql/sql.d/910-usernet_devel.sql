-- primary owner 10101
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10102;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10102, 4, 2, 5);
DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10103;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10103, 5, 2, 3);
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

DELETE FROM  #PFX#usersnet WHERE owner = 10101 AND target = 10107;
INSERT INTO  #PFX#usersnet (owner, target, friend, skill, trust) VALUES (10101, 10107, 3, 3, 4);

SELECT * FROM bsk_usersnet_wideskill;
SELECT * FROM bsk_usersnet_narrowskill;

