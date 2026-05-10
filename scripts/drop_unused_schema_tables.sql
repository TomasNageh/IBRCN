-- Removes experimental tables not referenced by the IBRCN PHP app (excluding vendor/).
-- Pair script: restore_dropped_experimental_tables.sql brings them back (structure only).
-- Core tables kept: users, stores, books, store_inventory, cart_items, orders, order_items,
-- audit_log, notifications, reading_clubs, club_members, club_member_current_read, club_discussion_threads.
-- Also drops legacy compat-only tables from config/legacy_bookengine_compat.sql: signup, member.

USE ibrcn;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
  archive_events,
  archive_notifications,
  discussion_posts,
  disputes,
  sustainability_log,
  vote_suggestions,
  votes,
  event_signing_slots,
  tickets,
  waitlist,
  annotations,
  badge_awards,
  badges,
  club_memberships,
  club_milestones,
  discussions,
  lending,
  circle_memberships,
  niche_circles,
  reading_challenges,
  reading_history,
  recommendations,
  staff_picks,
  pre_orders,
  holds,
  book_clubs,
  events,
  tax_rates,
  signup,
  `member`;

SET FOREIGN_KEY_CHECKS = 1;
