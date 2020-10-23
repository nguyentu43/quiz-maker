-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 29, 2018 at 05:59 PM
-- Server version: 5.7.23-0ubuntu0.18.04.1
-- PHP Version: 7.2.9-1+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_teacher`
--

CREATE TABLE `active_teacher` (
  `user_id` int(11) NOT NULL,
  `state` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `active_teacher`
--

INSERT INTO `active_teacher` (`user_id`, `state`) VALUES
(29, 1),
(50, 1);

-- --------------------------------------------------------

--
-- Table structure for table `answer`
--

CREATE TABLE `answer` (
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `response` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `answer`
--

INSERT INTO `answer` (`result_id`, `question_id`, `response`) VALUES
(20, 90, '{\"data\": [{\"id\": 1, \"data\": [\"Insert Picture\"]}, {\"id\": 0, \"data\": [\"Insert Shape\", \"Font Color\", \"Font Size\"]}], \"point\": 0}'),
(20, 91, NULL),
(20, 94, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `user_id`, `category_name`) VALUES
(21, 29, 'Word'),
(22, 29, 'Excel');

-- --------------------------------------------------------

--
-- Table structure for table `category_test`
--

CREATE TABLE `category_test` (
  `id` int(11) NOT NULL,
  `name` varchar(5000) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `category_test`
--

INSERT INTO `category_test` (`id`, `name`) VALUES
(3, 'Tin học văn phòng'),
(5, 'Lập trình'),
(6, 'Mạng máy tính');

-- --------------------------------------------------------

--
-- Table structure for table `forgot_password`
--

CREATE TABLE `forgot_password` (
  `user_id` int(11) NOT NULL,
  `code` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `question_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `question_type_id` int(11) DEFAULT NULL,
  `question_text` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `question_options` json DEFAULT NULL,
  `question_settings` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`question_id`, `user_id`, `category_id`, `question_type_id`, `question_text`, `question_options`, `question_settings`) VALUES
(86, 29, 21, 4, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div><img src=\"http://quiz.test/uploads/29/t%E1%BA%A3i%20xu%E1%BB%91ng.jpg\" alt=\"tải xuống\" /> Gh&eacute;p chức năng</div>\n</body>\n</html>', '[{\"id\": 6, \"index\": \"A\", \"source\": {\"id\": 7, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ctrl + S&nbsp;</div>\\n</body>\\n</html>\"}, \"target\": {\"id\": 8, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Lưu file</div>\\n</body>\\n</html>\"}}, {\"id\": 9, \"index\": \"B\", \"source\": {\"id\": 10, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ctrl + N</div>\\n</body>\\n</html>\"}, \"target\": {\"id\": 11, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Tạo file</div>\\n</body>\\n</html>\"}}, {\"id\": 30, \"index\": \"C\", \"source\": {\"id\": 31, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ctrl + F</div>\\n</body>\\n</html>\"}, \"target\": {\"id\": 32, \"data\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>T&igrave;m kiếm</div>\\n</body>\\n</html>\"}}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(87, 29, 21, 3, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>Sắp xếp thứ tự c&aacute;c dung lượng theo thứ tự từ thấp đến cao</div>\n</body>\n</html>', '[{\"id\": 0, \"index\": \"A\", \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>bit</div>\\n</body>\\n</html>\"}, {\"id\": 1, \"index\": \"B\", \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>byte</div>\\n</body>\\n</html>\"}, {\"id\": 2, \"index\": \"C\", \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>kilobyte</div>\\n</body>\\n</html>\"}, {\"id\": 3, \"index\": \"D\", \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>megabyte</div>\\n</body>\\n</html>\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(88, 29, 21, 1, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>N&uacute;t lệnh Insert d&ugrave;ng để l&agrave;m g&igrave;?</div>\n</body>\n</html>', '[{\"id\": 2, \"index\": \"A\", \"is_correct\": false, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Xo&aacute;</div>\\n</body>\\n</html>\"}, {\"id\": 1, \"index\": \"B\", \"is_correct\": true, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ch&egrave;n</div>\\n</body>\\n</html>\"}, {\"id\": 3, \"index\": \"C\", \"is_correct\": false, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Định dạng</div>\\n</body>\\n</html>\"}, {\"id\": 4, \"index\": \"D\", \"is_correct\": false, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>T&igrave;m kiếm</div>\\n</body>\\n</html>\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Phản hồi trả lời c&acirc;u hỏi</div>\\n</body>\\n</html>\"}'),
(89, 29, 21, 2, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>L&agrave;m thế n&agrave;o để lưu văn bản</div>\n</body>\n</html>', '[{\"id\": 1, \"index\": \"A\", \"is_correct\": true, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ctrl + S</div>\\n</body>\\n</html>\"}, {\"id\": 2, \"index\": \"B\", \"is_correct\": true, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>File -&gt; Save</div>\\n</body>\\n</html>\"}, {\"id\": 3, \"index\": \"C\", \"is_correct\": false, \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>Ctrl + Z</div>\\n</body>\\n</html>\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(90, 29, 21, 5, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>Ph&acirc;n loại chức năng theo mục</div>\n</body>\n</html>', '[{\"id\": 0, \"index\": \"A\", \"group_text\": \"Chèn\", \"group_items\": \"Insert Picture;Insert Shape\"}, {\"id\": 1, \"index\": \"B\", \"group_text\": \"Định dạng\", \"group_items\": \"Font Size;Font Color\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(91, 29, 21, 6, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>N&uacute;t lệnh để lưu</div>\n</body>\n</html>', '[{\"id\": 0, \"index\": \"A\", \"option_text\": \"File->Save\"}, {\"id\": 1, \"index\": \"B\", \"option_text\": \"Ctrl+S\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(92, 29, 21, 7, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>N&ecirc;u c&aacute;c bước mở văn bản Word</div>\n</body>\n</html>', '[]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\", \"word_count\": \"100\"}'),
(93, 29, 21, 8, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>H&atilde;y điền đ&uacute;ng nội dung theo y&ecirc;u cầu</div>\n</body>\n</html>', '[{\"id\": 0, \"index\": \"A\", \"fill_words\": [\"Microsoft Word\", \"soạn thảo\"], \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div><input class=\\\"input_fill_word\\\"/> l&agrave; phần mềm <input class=\\\"input_fill_word\\\"/> văn bản</div>\\n</body>\\n</html>\"}, {\"id\": 1, \"index\": \"B\", \"fill_words\": [\"Format\"], \"option_text\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n<div>N&uacute;t lệnh <input class=\\\"input_fill_word\\\"/> d&ugrave;ng để định dạng&nbsp;</div>\\n</body>\\n</html>\"}]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\"}'),
(94, 29, 21, 9, '<!DOCTYPE html>\n<html>\n<head>\n</head>\n<body>\n<div>Chụp m&agrave;n h&igrave;nh soạn thảo</div>\n</body>\n</html>', '[]', '{\"point\": \"1\", \"feedback\": \"<!DOCTYPE html>\\n<html>\\n<head>\\n</head>\\n<body>\\n\\n</body>\\n</html>\", \"file_size\": \"25\", \"file_type\": \"*\", \"file_count\": \"5\"}');

-- --------------------------------------------------------

--
-- Table structure for table `question_type`
--

CREATE TABLE `question_type` (
  `question_type_id` int(11) NOT NULL,
  `question_type_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `question_type`
--

INSERT INTO `question_type` (`question_type_id`, `question_type_name`) VALUES
(1, 'Một lựa chọn'),
(2, 'Nhiều lựa chọn'),
(3, 'Sắp xếp'),
(4, 'Nối cột'),
(5, 'Phân loại'),
(6, 'Trả lời ngắn'),
(7, 'Đoạn văn'),
(8, 'Điền khuyết'),
(9, 'Nộp file');

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `result_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `time_start` datetime DEFAULT NULL,
  `time_submit` datetime DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `ip_address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `fullname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `information` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `result`
--

INSERT INTO `result` (`result_id`, `user_id`, `test_id`, `time_start`, `time_submit`, `count`, `ip_address`, `fullname`, `information`) VALUES
(20, 29, 15, '2018-11-21 19:55:01', '2018-11-21 19:55:43', 0, '192.168.10.1', 'Giáo viên 1', '');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `name`) VALUES
(1, 'Người ra đề'),
(2, 'Người làm bài'),
(3, 'Người quản trị hệ thống');

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE `test` (
  `test_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `test_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `modified_date` datetime DEFAULT NULL,
  `is_private` tinyint(4) NOT NULL,
  `is_enable` tinyint(4) NOT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `attemps` int(11) NOT NULL,
  `category_test_id` int(11) DEFAULT NULL,
  `shuffle` tinyint(1) NOT NULL DEFAULT '0',
  `test_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `is_login` tinyint(1) NOT NULL DEFAULT '1',
  `random_from_category` json DEFAULT NULL,
  `show_point` tinyint(1) NOT NULL DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `img` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `test`
--

INSERT INTO `test` (`test_id`, `user_id`, `test_name`, `description`, `created_date`, `modified_date`, `is_private`, `is_enable`, `time_limit`, `attemps`, `category_test_id`, `shuffle`, `test_code`, `is_login`, `random_from_category`, `show_point`, `start_time`, `end_time`, `img`) VALUES
(15, 29, 'Đề thi kiểm tra 15 phút', '', '2018-11-15 02:11:40', '2018-11-19 18:32:47', 0, 1, 15, 3, 3, 1, 'TIWR5L4XDM7N', 1, '{\"21\": 2}', 1, '2018-11-19 18:32:00', '2018-11-19 18:32:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `test_question`
--

CREATE TABLE `test_question` (
  `test_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `index_question` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `test_question`
--

INSERT INTO `test_question` (`test_id`, `question_id`, `index_question`) VALUES
(15, 91, 1);

--
-- Triggers `test_question`
--
DELIMITER $$
CREATE TRIGGER `set_index_question` BEFORE INSERT ON `test_question` FOR EACH ROW begin
	DECLARE index_q int;
    set index_q = (select test_question.index_question from test_question where test_question.test_id = new.test_id order by test_question.index_question desc limit 1);
    if index_q is null THEN
    	set new.index_question = 1;
    else
    	set new.index_question = index_q + 1;
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `username` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT NULL,
  `verification_code` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `role_id`, `username`, `password`, `email`, `fullname`, `is_active`, `verification_code`) VALUES
(16, 3, 'admin', '$2y$10$dRyrUJKYdhBycmVom1M.XuL5tnY1aK8nv5STG2Wka6XBWuQmHyzk.', '10quiz.web@gmail.com', 'Admin', 1, NULL),
(29, 1, 'giaovien1', '$2y$10$mWzRsSNQBfyFN4FXbnhmvujfjOcgIxSwtccFvzMD.py/BAOMyILau', 'giaovien1@example.com', 'Giáo viên 1', 1, 'ac5f403b997848e4ffd0be203d27d53c'),
(34, 2, 'hocsinh1', '$2y$10$IgFD/bVZyfTRL8A3m91kRu4BreCGcY9gm99AQQfiPru1un3jqD7di', 'hocsinh1@example.com', 'Học sinh 1', 1, '1e058a99037755ff2e42737d03dc8fe3'),
(50, 1, 'nguyentu', '$2y$10$nFEFMnObu3HcsmAqRTLc1.a/z.g/nvj5ou6cUQl5qNa2cRvGEQ6Lm', 'ngoctu.tu1@gmail.com', 'Nguyễn Ngọc Tú', 1, NULL);

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `add_active_teacher` AFTER INSERT ON `user` FOR EACH ROW if new.role_id = 1 then
	BEGIN
    	INSERT into active_teacher values(new.user_id, 0);
        INSERT into category(category_name, user_id) values('Chưa phân loại', new.user_id);
    end;
end if
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_teacher`
--
ALTER TABLE `active_teacher`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `answer`
--
ALTER TABLE `answer`
  ADD PRIMARY KEY (`result_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `FK_category_user` (`user_id`);

--
-- Indexes for table `category_test`
--
ALTER TABLE `category_test`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forgot_password`
--
ALTER TABLE `forgot_password`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `FK_category_question` (`category_id`),
  ADD KEY `FK_question_q_type` (`question_type_id`),
  ADD KEY `FK_user_question` (`user_id`);

--
-- Indexes for table `question_type`
--
ALTER TABLE `question_type`
  ADD PRIMARY KEY (`question_type_id`);

--
-- Indexes for table `result`
--
ALTER TABLE `result`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `FK_test_result` (`test_id`),
  ADD KEY `FK_user_result` (`user_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `FK_test_user` (`user_id`),
  ADD KEY `fk_test_category` (`category_test_id`);
ALTER TABLE `test` ADD FULLTEXT KEY `fulltext_test` (`test_name`,`description`);

--
-- Indexes for table `test_question`
--
ALTER TABLE `test_question`
  ADD PRIMARY KEY (`test_id`,`question_id`),
  ADD KEY `FK_test_question2` (`question_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `FK_role_user` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `category_test`
--
ALTER TABLE `category_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `question_type`
--
ALTER TABLE `question_type`
  MODIFY `question_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `result`
--
ALTER TABLE `result`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `test`
--
ALTER TABLE `test`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_teacher`
--
ALTER TABLE `active_teacher`
  ADD CONSTRAINT `active_teacher_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `answer`
--
ALTER TABLE `answer`
  ADD CONSTRAINT `answer_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `result` (`result_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `answer_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `FK_category_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forgot_password`
--
ALTER TABLE `forgot_password`
  ADD CONSTRAINT `forgot_password_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FK_category_question` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_question_q_type` FOREIGN KEY (`question_type_id`) REFERENCES `question_type` (`question_type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_user_question` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `FK_test_result` FOREIGN KEY (`test_id`) REFERENCES `test` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_user_result` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test`
--
ALTER TABLE `test`
  ADD CONSTRAINT `FK_test_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_test_category` FOREIGN KEY (`category_test_id`) REFERENCES `category_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test_question`
--
ALTER TABLE `test_question`
  ADD CONSTRAINT `FK_test_question` FOREIGN KEY (`test_id`) REFERENCES `test` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_test_question2` FOREIGN KEY (`question_id`) REFERENCES `question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `FK_role_user` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
