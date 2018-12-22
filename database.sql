SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `bans` (
  `ban_id` int(10) UNSIGNED NOT NULL,
  `ip` text COLLATE utf8_bin NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `forums` (
  `forum_id` int(10) UNSIGNED NOT NULL,
  `title` text COLLATE utf8_bin NOT NULL,
  `slug` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `invites` (
  `invite_id` int(10) UNSIGNED NOT NULL,
  `invite_code` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `modlog` (
  `action_id` int(10) UNSIGNED NOT NULL,
  `mod_id` int(10) UNSIGNED NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `text_sample` text CHARACTER SET utf8 NOT NULL,
  `ip` text CHARACTER SET utf8 NOT NULL,
  `reason` text CHARACTER SET utf8 NOT NULL,
  `ban_id` int(10) UNSIGNED NOT NULL,
  `unlawful` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `recipient_session_id` text COLLATE utf8_bin NOT NULL,
  `recipient_user_id` int(10) NOT NULL COMMENT '-1 means session-only notification',
  `time` int(10) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `text` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `posts` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `forum_id` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `parent_topic` int(10) UNSIGNED NOT NULL,
  `creation_time` int(10) UNSIGNED NOT NULL,
  `deleted_by` int(10) UNSIGNED NOT NULL,
  `ip` text COLLATE utf8_bin NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_id` text COLLATE utf8_bin NOT NULL,
  `display_username` tinyint(1) NOT NULL DEFAULT '0',
  `ord` bigint(20) UNSIGNED NOT NULL,
  `order_in_topic` int(10) UNSIGNED NOT NULL,
  `reply_to` int(10) UNSIGNED NOT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `title` text COLLATE utf8_bin NOT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `flag` text COLLATE utf8_bin NOT NULL,
  `file_url` text COLLATE utf8_bin NOT NULL,
  `thumb_url` text COLLATE utf8_bin NOT NULL,
  `thumb_w` int(10) UNSIGNED NOT NULL,
  `thumb_h` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` text NOT NULL,
  `password_hash` text NOT NULL,
  `registration_time` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `bans`
  ADD PRIMARY KEY (`ban_id`);

ALTER TABLE `forums`
  ADD PRIMARY KEY (`forum_id`);

ALTER TABLE `invites`
  ADD PRIMARY KEY (`invite_id`);

ALTER TABLE `modlog`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `post_id` (`post_id`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `parent_topic` (`parent_topic`),
  ADD KEY `forum_id` (`forum_id`),
  ADD KEY `ord` (`ord`),
  ADD KEY `creation_time` (`creation_time`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `bans`
  MODIFY `ban_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `forums`
  MODIFY `forum_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `invites`
  MODIFY `invite_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `modlog`
  MODIFY `action_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `posts`
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;