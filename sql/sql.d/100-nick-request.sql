--
--  Nickname requested at signup: the player plays at once with a
--  temporary nickname (users.type flag USER_FLAG_TY_NICKTMP, 0x100000)
--  and the requested one waits here for a check in usermgmt.php
--
ALTER TABLE #PFX#users ADD COLUMN IF NOT EXISTS nick_req text DEFAULT NULL;
