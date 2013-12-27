ALTER TABLE #PFX#users DROP CONSTRAINT #PFX#users_guar_code_fkey;
ALTER TABLE #PFX#users ADD CONSTRAINT #PFX#users_guar_code_fkey FOREIGN KEY (guar_code) REFERENCES #PFX#users(code) MATCH FULL;

ALTER TABLE #PFX#users ALTER COLUMN guar_code DROP DEFAULT; --MF