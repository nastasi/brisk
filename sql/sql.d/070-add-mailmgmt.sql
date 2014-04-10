--
--  Table to manage mails sent to users
--
DROP TABLE #PFX#mails;
CREATE TABLE #PFX#mails (
       code       SERIAL PRIMARY KEY,
       ucode      integer REFERENCES #PFX#users (code) ON DELETE cascade ON UPDATE cascade,
       type       integer,                           -- type of email
       tstamp     timestamp DEFAULT to_timestamp(0), -- insert time
       subj       text,                              -- email subject
       body_txt   text,                              -- email body (text version)
       body_htm   text,                              -- email body (html version)
       hash       text);                             -- reference hash to complete registration
