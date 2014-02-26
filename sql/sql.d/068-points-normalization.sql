--
-- points normalization to be able to calculate placing
--
UPDATE #PFX#bin5_points AS p SET pts = pts / (2 ^ g.mult) FROM #PFX#bin5_games AS g WHERE g.code = p.gcode; --MF
UPDATE #PFX#bin5_points AS p SET pts = pts * (2 ^ g.mult) FROM #PFX#bin5_games AS g WHERE g.code = p.gcode; --MB
