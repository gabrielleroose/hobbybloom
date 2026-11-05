-- Adds the one column Google Calendar sync needs that Railway is missing.
--
-- Google_calendar_sync.php stores each HobbyBloom event's Google Calendar ID
-- in events.gc_event_id so it can update the event instead of duplicating it.
--
-- Checked against the Railway schema on 2026-11-05:
--   users.gc_access_token / gc_refresh_token / gc_token_expires  -> already exist
--   events.gc_event_id                                           -> MISSING (added here)
--
-- Safe to run once. Check first with:  SHOW COLUMNS FROM events LIKE 'gc_event_id';

ALTER TABLE events
    ADD COLUMN gc_event_id VARCHAR(255) NULL;
