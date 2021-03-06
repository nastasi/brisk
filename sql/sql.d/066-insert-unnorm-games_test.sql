DELETE FROM #PFX#bin5_matches WHERE code = 100 OR code = 101;

INSERT INTO #PFX#bin5_matches (code, ttok, tidx, mazzo_next, mult_next) VALUES (100, 'normalize_points', 2, 1, 1) RETURNING *;
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (100, 10101, 0);
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (100, 10102, 1);
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (100, 10103, 2);
INSERT INTO #PFX#bin5_games (code, mcode, tstamp, act, asta_pnt, pnt, asta_win, friend, mazzo, mult)
                      VALUES (200, 100, to_timestamp(#NOW# - 1000), 2, 60, 0, -1, -1, 0, 0) RETURNING *;
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (200, 10101, 0);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (200, 10102, 0);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (200, 10103, 0);
UPDATE #PFX#bin5_matches SET (mazzo_next, mult_next) = (2, 0) WHERE code = 100;
INSERT INTO #PFX#bin5_games (code, mcode, tstamp, act, asta_pnt, pnt, asta_win, friend, mazzo, mult)
                      VALUES (201, 100, to_timestamp(#NOW# - 900), 0, 61, 37, 2, 1, 1, 1) RETURNING *;
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (201, 10101, 2);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (201, 10102, -2);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (201, 10103, -4);


INSERT INTO #PFX#bin5_matches (code, ttok, tidx, mazzo_next, mult_next) VALUES (101, 'normalize_points2', 2, 1, 0) RETURNING *;
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (101, 10101, 0);
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (101, 10102, 1);
INSERT INTO #PFX#bin5_table_orders (mcode, ucode, pos) VALUES (101, 10103, 2);
INSERT INTO #PFX#bin5_games (code, mcode, tstamp, act, asta_pnt, pnt, asta_win, friend, mazzo, mult)
                      VALUES (202, 101, to_timestamp(#NOW# - 800), 2, 60, 0, -1, -1, 0, 0) RETURNING *;
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (202, 10101, 0);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (202, 10102, 0);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (202, 10103, 0);
UPDATE #PFX#bin5_matches SET (mazzo_next, mult_next) = (2, 0) WHERE code = 100;
INSERT INTO #PFX#bin5_games (code, mcode, tstamp, act, asta_pnt, pnt, asta_win, friend, mazzo, mult)
                      VALUES (203, 101, to_timestamp(#NOW# - 700), 0, 81, 37, 2, 1, 1, 1) RETURNING *;
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (203, 10101, 6);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (203, 10102, -6);
INSERT INTO #PFX#bin5_points (gcode, ucode, pts) VALUES (203, 10103, -12);



