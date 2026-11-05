-- Adds the columns Google Calendar sync needs.
-- calendar.php, Google_calendar_callback.php and Google_calendar_sync.php
-- read and write these, but they were never added to build.sql.
--
-- Only run this against a database that does NOT already have them
-- (check first with: SHOW COLUMNS FROM users LIKE 'gc_%';).

ALTER TABLE users
    ADD COLUMN gc_access_token  TEXT NULL,
    ADD COLUMN gc_refresh_token TEXT NULL,
    ADD COLUMN gc_token_expires INT  NULL;

ALTER TABLE events
    ADD COLUMN gc_event_id VARCHAR(255) NULL;
