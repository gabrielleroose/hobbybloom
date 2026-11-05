-- HobbyBloom demo account seed
--
-- Creates (or resets) the shared read-only demo account plus three sample
-- "friend" accounts so the dashboard, circles, activity feed and calendar
-- have something to show.
--
-- Safe to re-run: it only deletes and recreates accounts whose google_id
-- starts with 'hobbybloom-demo'. Real users are never touched.
-- Circles and modules are looked up by name, so it works regardless of IDs.
--
-- Run:  mysql <connection flags> <database> < database/demo_seed.sql

SET NAMES utf8mb4;
START TRANSACTION;

-- 1. Remove any previous demo data ------------------------------------------
-- module_user_favorite has no ON DELETE CASCADE, so clear it first.
DELETE f FROM module_user_favorite f
  JOIN users u ON f.uid = u.id
 WHERE u.google_id LIKE 'hobbybloom-demo%';
-- circle_messages for the built-in "General" chat etc. cascade with the user.
DELETE FROM users WHERE google_id LIKE 'hobbybloom-demo%';

-- 2. Accounts -----------------------------------------------------------------
INSERT INTO users (google_id, first_name, last_name, username, email, age) VALUES
  ('hobbybloom-demo',          'Sam',    'Rivera', 'SamTriesThings', 'demo@hobbybloom.invalid',          22),
  ('hobbybloom-demo-friend-1', 'Maya',   'Chen',   'MayaMakes',      'maya.demo@hobbybloom.invalid',     21),
  ('hobbybloom-demo-friend-2', 'Jordan', 'Okafor', 'JordanOutdoors', 'jordan.demo@hobbybloom.invalid',   23),
  ('hobbybloom-demo-friend-3', 'Priya',  'Nair',   'PriyaPaints',    'priya.demo@hobbybloom.invalid',    20);

SET @demo   = (SELECT id FROM users WHERE google_id = 'hobbybloom-demo');
SET @maya   = (SELECT id FROM users WHERE google_id = 'hobbybloom-demo-friend-1');
SET @jordan = (SELECT id FROM users WHERE google_id = 'hobbybloom-demo-friend-2');
SET @priya  = (SELECT id FROM users WHERE google_id = 'hobbybloom-demo-friend-3');

-- Circle names exactly as stored (emoji included)
SET @c_garden = (SELECT name FROM circle WHERE name LIKE '%Gardening' LIMIT 1);
SET @c_cook   = (SELECT name FROM circle WHERE name LIKE '%Cooking'   LIMIT 1);
SET @c_paint  = (SELECT name FROM circle WHERE name LIKE '%Painting'  LIMIT 1);
SET @c_hike   = (SELECT name FROM circle WHERE name LIKE '%Hiking'    LIMIT 1);
SET @c_bake   = (SELECT name FROM circle WHERE name LIKE '%Baking'    LIMIT 1);

-- 3. Profiles (hobbies = comma-separated circle names) ------------------------
INSERT INTO user_profiles (user_id, profile_color, hometown, bio, hobbies, last_login, login_streak, is_private) VALUES
  (@demo,   '#1f5077', 'Bloomington, IN',
   'Trying one new thing a month. Currently: not burning eggs and keeping a basil plant alive.',
   CONCAT_WS(', ', @c_garden, @c_cook, @c_paint, @c_hike),
   CURDATE() - INTERVAL 1 DAY, 5, 0),
  (@maya,   '#c2577a', 'Indianapolis, IN',
   'Sourdough evangelist. Will trade bread for plant cuttings.',
   CONCAT_WS(', ', @c_cook, @c_bake, @c_garden),
   CURDATE(), 12, 0),
  (@jordan, '#3f7d4e', 'Columbus, OH',
   'Weekend trail runner, weekday trail planner.',
   CONCAT_WS(', ', @c_hike, @c_garden),
   CURDATE(), 3, 0),
  (@priya,  '#8a5cc2', 'Chicago, IL',
   'Watercolor beginner. Mostly painting my cat.',
   CONCAT_WS(', ', @c_paint, @c_bake),
   CURDATE(), 8, 0);

-- 4. Circle memberships -------------------------------------------------------
INSERT IGNORE INTO circle_members (circle_id, user_id, role)
SELECT c.circle_id, m.uid, 'member'
  FROM circle c
  JOIN (
        SELECT @demo AS uid, @c_garden AS cname UNION ALL
        SELECT @demo,  @c_cook   UNION ALL
        SELECT @demo,  @c_paint  UNION ALL
        SELECT @demo,  @c_hike   UNION ALL
        SELECT @maya,  @c_cook   UNION ALL
        SELECT @maya,  @c_bake   UNION ALL
        SELECT @maya,  @c_garden UNION ALL
        SELECT @jordan,@c_hike   UNION ALL
        SELECT @jordan,@c_garden UNION ALL
        SELECT @priya, @c_paint  UNION ALL
        SELECT @priya, @c_bake
       ) m ON m.cname = c.name;

-- 5. Follows ------------------------------------------------------------------
INSERT INTO user_follows (follower_id, followed_id, status, created_at) VALUES
  (@demo,   @maya,   'accepted', NOW() - INTERVAL 20 DAY),
  (@maya,   @demo,   'accepted', NOW() - INTERVAL 20 DAY),
  (@demo,   @jordan, 'accepted', NOW() - INTERVAL 12 DAY),
  (@jordan, @demo,   'accepted', NOW() - INTERVAL 11 DAY),
  (@demo,   @priya,  'accepted', NOW() - INTERVAL 6 DAY),
  (@priya,  @demo,   'pending',  NOW() - INTERVAL 1 DAY);   -- shows a follow request

-- 6. Circle chat --------------------------------------------------------------
INSERT INTO circle_messages (hobby_name, user_id, message, created_at)
SELECT hobby, uid, msg, ts FROM (
  SELECT @c_garden AS hobby, @jordan AS uid, 'First frost is coming next week. Bring the potted herbs inside!' AS msg, NOW() - INTERVAL 3 DAY AS ts UNION ALL
  SELECT @c_garden, @demo,  'Thanks! My basil survived its first month 🌿 small wins.', NOW() - INTERVAL 3 DAY + INTERVAL 2 HOUR UNION ALL
  SELECT @c_garden, @maya,  'Basil loves a sunny windowsill. Pinch the top leaves so it gets bushy.', NOW() - INTERVAL 2 DAY UNION ALL
  SELECT @c_cook,   @maya,  'Anyone want to do a Sunday meal-prep call? I''m making a big pot of chili.', NOW() - INTERVAL 2 DAY UNION ALL
  SELECT @c_cook,   @demo,  'In! Just finished the Making Eggs module, so I can at least bring breakfast 🍳', NOW() - INTERVAL 1 DAY UNION ALL
  SELECT @c_paint,  @priya, 'Tip from today: let the first wash dry completely before adding detail.', NOW() - INTERVAL 1 DAY UNION ALL
  SELECT @c_paint,  @demo,  'That explains my muddy skies 😅 trying again tonight.', NOW() - INTERVAL 20 HOUR UNION ALL
  SELECT @c_hike,   @jordan,'Planning a fall-colors hike at Brown County State Park. Calendar invite incoming!', NOW() - INTERVAL 10 HOUR UNION ALL
  SELECT 'General', @maya,  'Welcome to HobbyBloom, everyone! What are you learning this week?', NOW() - INTERVAL 4 DAY UNION ALL
  SELECT 'General', @demo,  'Eggs, watercolor, and how not to kill plants. Going well so far!', NOW() - INTERVAL 4 DAY + INTERVAL 1 HOUR
) t WHERE t.hobby IS NOT NULL;

-- 7. Module progress ----------------------------------------------------------
SET @m_eggs = (SELECT id FROM module WHERE name = 'Making Eggs'  ORDER BY id LIMIT 1);
SET @m_sql  = (SELECT id FROM module WHERE name = 'Intro to SQL' ORDER BY id LIMIT 1);

INSERT INTO log (mid, uid, last_visited, times_visited, complete, feedback)
SELECT mid, uid, lv, tv, c, fb FROM (
  SELECT @m_eggs AS mid, @demo AS uid, CURDATE() - INTERVAL 1 DAY AS lv, 4 AS tv, 1 AS c, 'Over-easy finally clicked!' AS fb UNION ALL
  SELECT @m_sql,  @demo,   CURDATE() - INTERVAL 2 DAY, 2, 0, NULL UNION ALL
  SELECT @m_eggs, @maya,   CURDATE() - INTERVAL 5 DAY, 3, 1, NULL UNION ALL
  SELECT @m_eggs, @jordan, CURDATE() - INTERVAL 1 DAY, 1, 0, NULL UNION ALL
  SELECT @m_sql,  @priya,  CURDATE() - INTERVAL 3 DAY, 2, 1, NULL
) t WHERE t.mid IS NOT NULL;

INSERT INTO module_user_completion (mid, uid, is_complete)
SELECT mid, uid, c FROM (
  SELECT @m_eggs AS mid, @demo AS uid, 1 AS c UNION ALL
  SELECT @m_sql,  @demo,  0 UNION ALL
  SELECT @m_eggs, @maya,  1 UNION ALL
  SELECT @m_sql,  @priya, 1
) t WHERE t.mid IS NOT NULL;

INSERT INTO module_user_favorite (mid, uid, is_favorite)
SELECT @m_eggs, @demo, 1 FROM DUAL WHERE @m_eggs IS NOT NULL;

INSERT INTO module_comments (module_id, user_id, comment_text, created_at)
SELECT mid, uid, txt, ts FROM (
  SELECT @m_eggs AS mid, @maya AS uid, 'The ice-bath trick for peeling is a game changer.' AS txt, NOW() - INTERVAL 5 DAY AS ts UNION ALL
  SELECT @m_eggs, @demo,  'Stage 4 quiz got me the first time. Over-easy ≠ over-hard!', NOW() - INTERVAL 1 DAY UNION ALL
  SELECT @m_sql,  @priya, 'Great intro. Would love a stage on JOINs next.', NOW() - INTERVAL 3 DAY
) t WHERE t.mid IS NOT NULL;

-- 8. Calendar -----------------------------------------------------------------
INSERT INTO events (title, event_date, event_time, description, location, created_by, created_at) VALUES
  ('Watercolor practice night', CURDATE() + INTERVAL 2 DAY, '19:00:00',
   'Trying Priya''s wet-on-dry tip. Bring a sky reference photo.', 'Herman B Wells Library, Bloomington', @demo, NOW() - INTERVAL 2 HOUR),
  ('Sunday meal prep', CURDATE() + INTERVAL 4 DAY, '15:00:00',
   'Chili, rice, and roasted veggies for the week.', 'Maya''s apartment', @maya, NOW() - INTERVAL 5 HOUR),
  ('Fall colors hike', CURDATE() + INTERVAL 9 DAY, '09:30:00',
   'Easy 5-mile loop. Carpool from campus at 8:30.', 'Brown County State Park', @jordan, NOW() - INTERVAL 10 HOUR);

SET @e_paint = (SELECT id FROM events WHERE created_by = @demo   AND title = 'Watercolor practice night');
SET @e_prep  = (SELECT id FROM events WHERE created_by = @maya   AND title = 'Sunday meal prep');
SET @e_hike  = (SELECT id FROM events WHERE created_by = @jordan AND title = 'Fall colors hike');

INSERT INTO event_invites (event_id, user_id, status) VALUES
  (@e_paint, @priya, 'accepted'),
  (@e_prep,  @demo,  'accepted'),
  (@e_hike,  @demo,  'pending'),
  (@e_hike,  @maya,  'accepted');

COMMIT;
