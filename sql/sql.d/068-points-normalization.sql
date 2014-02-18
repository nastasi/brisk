--
-- points normalization to be able to calculate placing
--
UPDATE bsk_bin5_points AS p SET pts = pts / (2 ^ g.mult) FROM bsk_bin5_games AS g WHERE g.code = p.gcode; --MF
UPDATE bsk_bin5_points AS p SET pts = pts * (2 ^ g.mult) FROM bsk_bin5_games AS g WHERE g.code = p.gcode; --MB
