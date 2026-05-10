-- Restores the 28 tables removed by drop_unused_schema_tables.sql.
-- Recreated from foreign-key relationships + column types aligned to current ibrcn core tables.
-- Data that was in those tables cannot be recovered without a database backup.

USE ibrcn;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tax_rates (
  tax_rate_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  region_code VARCHAR(64) NOT NULL,
  rate_percent DECIMAL(6,3) NOT NULL,
  effective_from DATE NOT NULL,
  PRIMARY KEY (tax_rate_id),
  KEY idx_tax_region (region_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS archive_events (
  archive_event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_table VARCHAR(128) DEFAULT NULL,
  source_id BIGINT UNSIGNED DEFAULT NULL,
  event_type VARCHAR(64) DEFAULT NULL,
  payload LONGTEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (archive_event_id),
  KEY idx_ae_table (source_table, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS archive_notifications (
  archive_notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  recipient_hint VARCHAR(255) DEFAULT NULL,
  payload LONGTEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (archive_notification_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS badges (
  badge_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (badge_id),
  UNIQUE KEY uq_badges_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS niche_circles (
  niche_circle_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (niche_circle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS book_clubs (
  book_club_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  founder_user_id INT UNSIGNED NOT NULL,
  anchor_book_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (book_club_id),
  KEY idx_bc_founder (founder_user_id),
  KEY idx_bc_book (anchor_book_id),
  CONSTRAINT book_clubs_ibfk_1 FOREIGN KEY (founder_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT book_clubs_ibfk_2 FOREIGN KEY (anchor_book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS club_memberships (
  membership_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_club_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (membership_id),
  UNIQUE KEY uq_club_user (book_club_id, user_id),
  CONSTRAINT club_memberships_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE,
  CONSTRAINT club_memberships_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS club_milestones (
  milestone_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_club_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  achieved_at DATETIME DEFAULT NULL,
  PRIMARY KEY (milestone_id),
  KEY idx_milestone_club (book_club_id),
  CONSTRAINT club_milestones_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS discussions (
  discussion_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_club_id INT UNSIGNED NOT NULL,
  title VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (discussion_id),
  KEY idx_discussions_club (book_club_id),
  CONSTRAINT discussions_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS discussion_posts (
  post_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  discussion_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id),
  KEY idx_posts_discussion (discussion_id),
  KEY idx_posts_user (user_id),
  CONSTRAINT discussion_posts_ibfk_1 FOREIGN KEY (discussion_id) REFERENCES discussions (discussion_id) ON DELETE CASCADE,
  CONSTRAINT discussion_posts_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS events (
  event_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  organizer_user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_events_store (store_id),
  KEY idx_events_org (organizer_user_id),
  CONSTRAINT events_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE CASCADE,
  CONSTRAINT events_ibfk_2 FOREIGN KEY (organizer_user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS tickets (
  ticket_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  purchaser_user_id INT UNSIGNED NOT NULL,
  attendee_user_id INT UNSIGNED NOT NULL,
  seat_label VARCHAR(64) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ticket_id),
  KEY idx_tickets_event (event_id),
  KEY idx_tickets_purchaser (purchaser_user_id),
  KEY idx_tickets_attendee (attendee_user_id),
  CONSTRAINT tickets_ibfk_1 FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE,
  CONSTRAINT tickets_ibfk_2 FOREIGN KEY (purchaser_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT tickets_ibfk_3 FOREIGN KEY (attendee_user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS event_signing_slots (
  signing_slot_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  ticket_id INT UNSIGNED NOT NULL,
  slot_starts_at DATETIME DEFAULT NULL,
  PRIMARY KEY (signing_slot_id),
  KEY idx_ess_event (event_id),
  KEY idx_ess_ticket (ticket_id),
  CONSTRAINT event_signing_slots_ibfk_1 FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE,
  CONSTRAINT event_signing_slots_ibfk_2 FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS waitlist (
  waitlist_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (waitlist_id),
  UNIQUE KEY uq_waitlist (event_id, user_id),
  CONSTRAINT waitlist_ibfk_1 FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE,
  CONSTRAINT waitlist_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS staff_picks (
  staff_pick_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  note VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (staff_pick_id),
  UNIQUE KEY uq_pick_store_book (store_id, book_id),
  CONSTRAINT staff_picks_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE CASCADE,
  CONSTRAINT staff_picks_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pre_orders (
  pre_order_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  store_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (pre_order_id),
  KEY idx_pre_user (user_id),
  KEY idx_pre_book (book_id),
  KEY idx_pre_store (store_id),
  CONSTRAINT pre_orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT pre_orders_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE,
  CONSTRAINT pre_orders_ibfk_3 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reading_history (
  reading_history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  finished_at DATETIME DEFAULT NULL,
  PRIMARY KEY (reading_history_id),
  KEY idx_rh_user (user_id),
  KEY idx_rh_book (book_id),
  CONSTRAINT reading_history_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT reading_history_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS recommendations (
  recommendation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  score DECIMAL(6,3) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (recommendation_id),
  KEY idx_rec_user (user_id),
  KEY idx_rec_book (book_id),
  CONSTRAINT recommendations_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT recommendations_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reading_challenges (
  challenge_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  target_books INT UNSIGNED NOT NULL DEFAULT 1,
  progress INT UNSIGNED NOT NULL DEFAULT 0,
  ends_at DATE DEFAULT NULL,
  PRIMARY KEY (challenge_id),
  KEY idx_challenge_user (user_id),
  CONSTRAINT reading_challenges_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS lending (
  lending_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_club_id INT UNSIGNED NOT NULL,
  lender_user_id INT UNSIGNED NOT NULL,
  borrower_user_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  due_at DATE DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (lending_id),
  KEY idx_lending_club (book_club_id),
  KEY idx_lending_lender (lender_user_id),
  KEY idx_lending_borrower (borrower_user_id),
  KEY idx_lending_book (book_id),
  CONSTRAINT lending_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE,
  CONSTRAINT lending_ibfk_2 FOREIGN KEY (lender_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT lending_ibfk_3 FOREIGN KEY (borrower_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT lending_ibfk_4 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS holds (
  hold_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  inventory_id INT UNSIGNED NOT NULL,
  placed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (hold_id),
  UNIQUE KEY uq_hold_user_inv (user_id, inventory_id),
  CONSTRAINT holds_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT holds_ibfk_2 FOREIGN KEY (inventory_id) REFERENCES store_inventory (inventory_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS disputes (
  dispute_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  opened_by_user_id INT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'Open',
  details TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (dispute_id),
  KEY idx_disputes_order (order_id),
  KEY idx_disputes_user (opened_by_user_id),
  CONSTRAINT disputes_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
  CONSTRAINT disputes_ibfk_2 FOREIGN KEY (opened_by_user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS sustainability_log (
  sustainability_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  metric_code VARCHAR(64) NOT NULL,
  metric_value DECIMAL(12,4) NOT NULL,
  logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sustainability_log_id),
  KEY idx_sust_order (order_id),
  CONSTRAINT sustainability_log_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vote_suggestions (
  suggestion_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_club_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  suggested_by_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (suggestion_id),
  KEY idx_vs_club (book_club_id),
  KEY idx_vs_book (book_id),
  KEY idx_vs_user (suggested_by_user_id),
  CONSTRAINT vote_suggestions_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE,
  CONSTRAINT vote_suggestions_ibfk_2 FOREIGN KEY (suggested_by_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT vote_suggestions_ibfk_3 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS votes (
  vote_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  suggestion_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  value TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (vote_id),
  UNIQUE KEY uq_vote (suggestion_id, user_id),
  CONSTRAINT votes_ibfk_1 FOREIGN KEY (suggestion_id) REFERENCES vote_suggestions (suggestion_id) ON DELETE CASCADE,
  CONSTRAINT votes_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS annotations (
  annotation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_annotation_id INT UNSIGNED DEFAULT NULL,
  book_club_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (annotation_id),
  KEY idx_ann_parent (parent_annotation_id),
  KEY idx_ann_club (book_club_id),
  KEY idx_ann_book (book_id),
  KEY idx_ann_user (user_id),
  CONSTRAINT annotations_ibfk_1 FOREIGN KEY (book_club_id) REFERENCES book_clubs (book_club_id) ON DELETE CASCADE,
  CONSTRAINT annotations_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT annotations_ibfk_3 FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE,
  CONSTRAINT annotations_ibfk_4 FOREIGN KEY (parent_annotation_id) REFERENCES annotations (annotation_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS circle_memberships (
  circle_membership_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  niche_circle_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (circle_membership_id),
  UNIQUE KEY uq_circle_user (niche_circle_id, user_id),
  CONSTRAINT circle_memberships_ibfk_1 FOREIGN KEY (niche_circle_id) REFERENCES niche_circles (niche_circle_id) ON DELETE CASCADE,
  CONSTRAINT circle_memberships_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS badge_awards (
  badge_award_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (badge_award_id),
  KEY idx_awards_user (user_id),
  KEY idx_awards_badge (badge_id),
  CONSTRAINT badge_awards_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT badge_awards_ibfk_2 FOREIGN KEY (badge_id) REFERENCES badges (badge_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
