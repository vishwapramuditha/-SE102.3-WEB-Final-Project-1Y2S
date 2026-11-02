-- ===============================================================
-- NSBM NewsDesk - Campus News Management System
-- Module: Web and Mobile Application Development (SE102.3)
-- Batch: 26.1 IFSL | Faculty of Computing | NSBM Green University
-- Database Schema and Sample Data
-- ===============================================================

CREATE DATABASE IF NOT EXISTS `nsbm_newsdesk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nsbm_newsdesk`;

-- Disable foreign key checks for clean teardown/import
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `category_subscriptions`;
DROP TABLE IF EXISTS `bookmarks`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `articles`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- 1. Table structure for table `users`
-- ---------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `student_id` VARCHAR(50) NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
  `department` VARCHAR(100) DEFAULT 'Faculty of Computing',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 2. Table structure for table `categories`
-- ---------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `badge_color` VARCHAR(20) DEFAULT '#006a4e',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 3. Table structure for table `articles`
-- ---------------------------------------------------------------
CREATE TABLE `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `category_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `status` ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
  `admin_feedback` TEXT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_breaking` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_articles_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 4. Table structure for table `announcements`
-- ---------------------------------------------------------------
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `author_id` INT NOT NULL,
  `expiry_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 5. Table structure for table `comments`
-- ---------------------------------------------------------------
CREATE TABLE `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment_text` TEXT NOT NULL,
  `status` ENUM('approved', 'pending', 'hidden') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 6. Table structure for table `bookmarks`
-- ---------------------------------------------------------------
CREATE TABLE `bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_bookmark` (`user_id`, `article_id`),
  CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookmarks_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 7. Table structure for table `category_subscriptions`
-- ---------------------------------------------------------------
CREATE TABLE `category_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_category` (`user_id`, `category_id`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================================
-- Sample Seed Data
-- Passwords:
-- admin@nsbm.ac.lk   => admin123  (BCrypt hash)
-- student@nsbm.ac.lk => student123(BCrypt hash)
-- ===============================================================

INSERT INTO `users` (`id`, `full_name`, `student_id`, `email`, `password`, `role`, `department`) VALUES
(1, 'NSBM NewsDesk Admin', 'ADMIN-001', 'admin@nsbm.ac.lk', '$2y$10$wT331Nvyh.84eE2YyT9/eOf1uE5gB91kX2GgT9Z8H5aFv49YqB5a6', 'admin', 'Administration'),
(2, 'Kavindu Perera', 'NSBM/SOC/26.1/1042', 'student@nsbm.ac.lk', '$2y$10$o1f1Rk7t8tUj5w47gXG2A.v2a0P9E7M0kM0XJ2Yy6R9W6qG7n9Z6G', 'student', 'Faculty of Computing'),
(3, 'Dinithi Silva', 'NSBM/FOB/26.1/2055', 'dinithi@nsbm.ac.lk', '$2y$10$o1f1Rk7t8tUj5w47gXG2A.v2a0P9E7M0kM0XJ2Yy6R9W6qG7n9Z6G', 'student', 'Faculty of Business'),
(4, 'Tharindu Fernando', 'NSBM/FOE/25.2/0890', 'tharindu@nsbm.ac.lk', '$2y$10$o1f1Rk7t8tUj5w47gXG2A.v2a0P9E7M0kM0XJ2Yy6R9W6qG7n9Z6G', 'student', 'Faculty of Engineering');

-- Note: The hash for 'admin123' and 'student123' will also be verified / generated identically by PHP password_hash().

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `badge_color`) VALUES
(1, 'Academic', 'academic', 'Lectures, curriculum updates, research grants, and exam schedules', '#006a4e'),
(2, 'Events', 'events', 'Campus cultural celebrations, hackathons, guest seminars, and workshops', '#059669'),
(3, 'Sports', 'sports', 'Inter-university tournaments, athletics, cricket, and university league updates', '#d97706'),
(4, 'General', 'general', 'Campus facilities, green initiatives, student council updates, and public news', '#2563eb');

INSERT INTO `articles` (`id`, `title`, `slug`, `excerpt`, `content`, `category_id`, `author_id`, `image_url`, `status`, `admin_feedback`, `is_featured`, `is_breaking`, `views_count`, `published_at`) VALUES
(1, 'NSBM Inaugurates Next-Generation AI & Cloud Computing Research Centre', 'nsbm-inaugurates-ai-cloud-computing-research-centre', 'The Faculty of Computing has officially opened the doors to its state-of-the-art AI laboratory equipped with high-performance GPU clusters.', '<p>NSBM Green University took a giant leap in technological education today with the official inauguration of the Next-Generation Artificial Intelligence and Cloud Computing Research Centre located within the Faculty of Computing complex.</p><p>The Vice-Chancellor, alongside distinguished industry partners and deans of faculties, unveiled the facility designed to support advanced undergraduate research, deep learning experiments, and industry-sponsored innovations. The centre houses specialized high-performance computing clusters and dedicated collaborative workstations for computing undergraduates.</p><p>During his address, the Dean emphasized that this facility will directly empower 1st and 2nd year undergraduates to tackle real-world challenges ranging from computer vision to autonomous robotic solutions. Students will have round-the-clock supervised laboratory access beginning from next Monday.</p>', 1, 1, 'assets/images/sample-ai-lab.svg', 'approved', NULL, 1, 1, 1420, NOW() - INTERVAL 1 DAY),

(2, 'NSBM Green Wave Hackathon 2026: Registrations Open for All Faculties', 'nsbm-green-wave-hackathon-2026-registrations-open', 'Join the largest 24-hour inter-faculty hackathon tackling sustainability and intelligent campus solutions with prize pools exceeding LKR 500,000.', '<p>The Computing Society of NSBM (FOC) in collaboration with IEEE Student Branch has announced the flagship annual hackathon: <strong>Green Wave Hackathon 2026</strong>.</p><p>This year’s theme centers around Sustainable Cities, Smart Logistics, and Campus Carbon Reduction. Teams comprising 3 to 4 undergraduates from any faculty can participate. Mentorship sessions from industry leaders will be conducted leading up to the final demo day.</p><p>Key dates:<br>- Registration deadline: October 25, 2026<br>- Preliminary idea submission: November 02, 2026<br>- Grand 24-hour Finale: November 15, 2026 at the Main Auditorium.</p>', 2, 1, 'assets/images/sample-hackathon.svg', 'approved', NULL, 1, 0, 895, NOW() - INTERVAL 2 DAY),

(3, 'NSBM Green Lions Clinch Victory at the Inter-University Cricket Championship', 'nsbm-green-lions-clinch-cricket-championship', 'An exhilarating final over sees NSBM secure the championship trophy against formidable opponents with a stellar all-round team display.', '<p>The NSBM Green Lions cricket team brought home the coveted championship trophy following a high-stakes final match yesterday afternoon at the NSBM Grounds.</p><p>Chasing a target of 178 runs, the Lions navigated an intense chase, finishing with 2 balls to spare. Vice-Captain and third-year Business student Mahela guided the middle order with a match-winning half-century, while first-year computing bowler Sahan took four decisive wickets during the powerplay.</p><p>The Department of Physical Education congratulated the squad and announced a campus victory parade scheduled for this Friday at 3:00 PM.</p>', 3, 1, 'assets/images/sample-cricket.svg', 'approved', NULL, 0, 0, 620, NOW() - INTERVAL 3 DAY),

(4, 'Phase 4 Solar Canopy Expansion Completed Across North Parking Pavilion', 'phase-4-solar-canopy-expansion-completed', 'Continuing the commitment to 100% renewable energy, NSBM activates its newest photovoltaic solar parking shelter.', '<p>As part of NSBM Green University’s ongoing decarbonization masterplan, Phase 4 of the rooftop and parking canopy solar installation has been successfully commissioned.</p><p>The installation contributes an additional 250 kWp of clean electricity to the university micro-grid, offsetting campus peak energy demand during daytime lecture hours. Digital energy monitoring displays have been placed in the student lounge so undergraduates can observe real-time renewable generation statistics.</p>', 4, 1, 'assets/images/sample-solar.svg', 'approved', NULL, 0, 0, 410, NOW() - INTERVAL 5 DAY),

(5, 'Robotics Club Unveils Autonomous Campus Delivery Rover Prototype', 'robotics-club-unveils-delivery-rover-prototype', 'Student-built robotic assistant designed to transport laboratory components and library books across student walkways.', '<p>The student-run Robotics & Mechatronics Club demonstrated their prototype autonomous carrier rover today in front of the Faculty of Engineering.</p><p>Using LiDAR sensors and onboard edge microcontrollers, the vehicle safely navigates the green campus pathways, dodging pedestrians and obstacle paths. The team aims to conduct pilot test runs delivering reserved reference textbooks between the main library and faculty study pavilions next semester.</p>', 2, 2, 'assets/images/sample-robot.svg', 'approved', 'Excellent contribution! Approved for public feed.', 0, 0, 315, NOW() - INTERVAL 1 DAY),

(6, 'Annual Inter-Faculty Badminton Tournament Fixtures Announced', 'annual-inter-faculty-badminton-tournament-fixtures', 'Over 60 student athletes across all faculties set to compete in singles and doubles categories at the NSBM Indoor Sports Complex.', '<p>The NSBM Sports Council has released the official schedule for the 2026 Inter-Faculty Badminton Championship.</p><p>Matches will commence on Monday morning at 9:00 AM inside the Multipurpose Gymnasium. All students are invited to cheer on their faculty squads. Spectator seating will be open free of charge with student ID verification.</p>', 3, 2, 'assets/images/sample-badminton.svg', 'pending', NULL, 0, 0, 12, NOW()),

(7, 'Call for Papers: 4th International Undergrad Research Symposium (IURS 2026)', 'call-for-papers-iurs-2026', 'Undergraduate researchers are encouraged to submit peer-reviewed extended abstracts across computing, management, and technology.', '<p>The Centre for Postgraduate & Undergraduate Research has officially opened submissions for IURS 2026. Students who have conducted original research in their 1st, 2nd, or 3rd year modules can submit work under faculty mentorship.</p>', 1, 3, 'assets/images/sample-research.svg', 'rejected', 'Please revise the abstract submission link and specify the word limit before resubmitting.', 0, 0, 5, NOW()),

(8, 'NSBM Green University Town Phase II: Faculty of Medicine Groundbreaking Ceremony', 'nsbm-phase-2-faculty-of-medicine-groundbreaking', 'NSBM Green University marks a historic milestone with the official foundation stone laying for its new Medical Faculty complex and 500-bed green teaching hospital at Pitipana, Homagama.', '<p>NSBM Green University witnessed a historic milestone today as the foundation stone was ceremoniously laid for the Phase II expansion project: the state-of-the-art Faculty of Medicine complex and university teaching hospital at the Pitipana, Homagama premises.</p><p>The landmark ceremony was attended by the Vice-Chancellor, alongside senior government dignitaries, healthcare administrators, and faculty deans. The multi-billion-rupee expansion will introduce cutting-edge clinical simulation laboratories, digital anatomy suites, biochemistry research facilities, and specialized operating theater simulation modules for medical undergraduates.</p><p>Emphasizing sustainability in healthcare education, the new complex is designed to be Sri Lanka’s first green-certified medical campus, incorporating natural cross-ventilation, rooftop solar arrays, and high-efficiency medical waste processing. The first intake of medical undergraduates is scheduled following Sri Lanka Medical Council (SLMC) accreditation next academic year.</p>', 4, 1, 'assets/images/sample-medical.svg', 'approved', NULL, 0, 1, 1580, NOW() - INTERVAL 8 HOUR),

(9, 'NSBM 9th Annual General Convocation: Over 2,500 Undergraduates Conferred Degrees', 'nsbm-9th-annual-general-convocation-conferred-degrees', 'Celebrations unfold at the NSBM Main Auditorium as graduands across Faculty of Computing, Business, and Engineering receive degrees in partnership with Plymouth University and Victoria University.', '<p>The grand auditorium of NSBM Green University shone in academic regalia this morning as the 9th Annual General Convocation commenced, conferring degrees upon more than 2,500 triumphant undergraduates and postgraduates across three faculties.</p><p>Graduands from the Faculty of Computing, Faculty of Business, and Faculty of Engineering received globally accredited degrees offered in partnership with the University of Plymouth (UK) and Victoria University (Australia). Academic excellence was celebrated with gold medals awarded for highest aggregate scores in Software Engineering, Data Science, and International Management.</p><p>In his convocation keynote, the Chancellor commended the graduating batch for their resilience, agility, and demonstrated leadership, highlighting that over 92% of graduates had already secured employment offers through the university career placement network prior to convocation day.</p>', 1, 1, 'assets/images/sample-convocation.svg', 'approved', NULL, 1, 0, 2140, NOW() - INTERVAL 2 DAY),

(10, 'NSBM Industry Partnership Summit: Over 45 Tech Titans Sign Direct Placement MOUs', 'nsbm-industry-partnership-summit-45-tech-mous', 'Leading tech firms including Virtusa, IFS, WSO2, Dialog Axiata, and Sysco LABS establish dedicated student internship pipelines and innovation incubation labs at NSBM.', '<p>Solidifying its bridge between academia and corporate enterprise, NSBM Green University hosted the 2026 Industry Partnership Summit at the Faculty of Computing concourse, formalizing collaboration agreements with over 45 leading multinational and national employers.</p><p>Executives and talent acquisition directors from enterprise software giants such as Virtusa, IFS, WSO2, Sysco LABS, MillenniumIT ESP, and Dialog Axiata signed Memorandums of Understanding (MOUs) establishing fast-tracked undergraduate internship opportunities, dedicated mentoring clinics, and technology sponsorship programs.</p><p>As part of this initiative, several partner companies will establish collaborative sandbox labs within the NSBM Innovation Centre, enabling computing and business students to develop production-grade solutions on industry-standard platforms while completing their second and third year course modules.</p>', 1, 1, 'assets/images/sample-careerfair.svg', 'approved', NULL, 0, 0, 1120, NOW() - INTERVAL 3 DAY),

(11, 'NSBM Siyapatheterna 2026: Grand Cultural Extravaganza Celebrates National Heritage', 'nsbm-siyapatheterna-2026-grand-cultural-extravaganza', 'Over 3,000 undergraduates gather at the Student Activity Centre for the annual cultural fiesta showcasing traditional folk music, Angampora martial arts, and multi-ethnic harmony.', '<p>The sprawling green grounds and Student Activity Centre of NSBM resonated with vibrant traditional rhythms, vibrant silk costumes, and communal joy during the grand staging of <strong>Siyapatheterna 2026</strong>, NSBM’s signature annual Sinhala and Tamil cultural festival.</p><p>The day-long celebration featured traditional games including Kotta Pora, Tug of War, Kana Mutti Bindeema, and Raban drumming competitions where student delegations from all faculties joined together in high spirits. Special cultural dance troupes and an Angampora martial arts exhibition staged by undergraduate clubs drew massive applause from staff and visitors alike.</p><p>The evening culminated with an open-air cultural fusion concert headlined by university musical bands and renowned folk artists, celebrating the unity and cultural richness of the NSBM student body.</p>', 2, 2, 'assets/images/sample-cultural.svg', 'approved', 'Approved. Fantastic coverage of campus student traditions.', 0, 0, 1750, NOW() - INTERVAL 4 DAY),

(12, 'NSBM Green Lions Triumph at All-Island Inter-University Swimming Championship', 'nsbm-green-lions-triumph-inter-university-swimming', 'The Green Lions aquatic team captures 14 gold medals and the overall team shield at the newly commissioned NSBM Olympic-standard 50m Swimming Pool Complex.', '<p>The NSBM Green Lions aquatic squad delivered a commanding performance over the weekend, emerging as overall champions at the All-Island Inter-University Aquatic Championship held at the NSBM Olympic-standard Swimming Pool Complex in Pitipana.</p><p>Competing against 12 state and private university swim teams, the Green Lions secured 14 gold, 8 silver, and 5 bronze medals. Second-year computing undergraduate and national swimmer Akalanka set two new championship records in the 100m Freestyle and 50m Butterfly events.</p><p>The Director of Physical Education commended the squad’s rigorous early-morning training regime and reiterated NSBM’s dedication to providing world-class sports facilities that empower student athletes to excel academically and athletically on international stages.</p>', 3, 4, 'assets/images/sample-swimming.svg', 'approved', NULL, 0, 0, 890, NOW() - INTERVAL 4 DAY),

(13, 'Faculty of Computing Launches "CodeSprint X" National Algorithmic Hackathon', 'faculty-of-computing-launches-codesprint-x-hackathon', 'The IEEE Student Branch and Computing Society announce a 48-hour competitive algorithmic programming challenge open to all Sri Lankan university undergraduates.', '<p>The Faculty of Computing in partnership with the IEEE Student Branch and Computing Society of NSBM has officially unveiled <strong>CodeSprint X</strong>, a prestigious 48-hour algorithmic and competitive programming marathon.</p><p>Open to undergraduate teams from across Sri Lanka, CodeSprint X tests contestants in dynamic programming, advanced graph theory, distributed systems, and real-time backend API optimization. Top-tier industry judges and alumni software architects from Silicon Valley and Europe will mentor teams during the elimination stages.</p><p>The competition boasts a total cash prize pool exceeding LKR 600,000 alongside guaranteed direct interview opportunities for software engineering internships. Team registrations are open on the student portal until November 10, 2026.</p>', 2, 2, 'assets/images/sample-hackathon.svg', 'approved', NULL, 0, 0, 1340, NOW() - INTERVAL 5 DAY),

(14, 'NSBM Green Campus Wins National Energy Efficiency Leadership Gold Award', 'nsbm-green-campus-wins-energy-efficiency-gold-award', 'Sri Lanka Sustainable Energy Authority honors NSBM for its 100% LED architectural infrastructure, smart bio-digesters, and self-sufficient microgrid solar network.', '<p>In recognition of its pioneering environmental sustainability initiatives, NSBM Green University was bestowed with the coveted Gold Trophy at the National Energy Efficiency Awards organized by the Sri Lanka Sustainable Energy Authority and Ministry of Power and Energy.</p><p>The university was praised for its holistic approach to green campus management, including an on-site 1.2 MW rooftop solar microgrid, sensor-automated LED lighting systems, and closed-loop rainwater retention lakes that satisfy 100% of campus landscape irrigation requirements.</p><p>Accepting the award on behalf of the university, the Vice-Chancellor reaffirmed NSBM’s commitment to achieving net-zero operational carbon emissions by 2028, serving as a national model for eco-conscious higher education in the South Asian region.</p>', 4, 1, 'assets/images/sample-solar.svg', 'approved', NULL, 0, 0, 940, NOW() - INTERVAL 6 DAY),

(15, 'Undergraduate Research on Edge AI for Tea Crop Disease Wins IEEE ICTer Best Paper', 'undergraduate-research-edge-ai-tea-crop-disease-wins-best-paper', 'Faculty of Computing 3rd-year students develop lightweight convolutional neural network running on edge microcontrollers to detect tea blister blight with 98.4% accuracy.', '<p>A research team comprising third-year Software Engineering undergraduates from the Faculty of Computing has clinched the prestigious Best Paper Award at the 24th International Conference on Advances in ICT for Emerging Regions (IEEE ICTer 2026).</p><p>The winning paper, titled <em>“Low-Power Edge Neural Vision for Early Tea Blister Blight Detection in High-Altitude Plantations”</em>, presents an optimized deep learning model capable of running inference on low-cost edge microcontrollers without requiring active cloud connectivity.</p><p>Conducted in collaboration with the Tea Research Institute of Sri Lanka (TRI) and supervised by senior faculty lecturers, the prototype was field-tested across 15 hectares of tea estates in Nuwara Eliya, demonstrating a field accuracy of 98.4% in real-time leaf diagnosis.</p>', 1, 3, 'assets/images/sample-ai-lab.svg', 'approved', 'Phenomenal undergraduate achievement. Proudly published on news portal.', 0, 0, 1670, NOW() - INTERVAL 7 DAY),

(16, 'NSBM Career Fair 2026: 60+ Multinational Employers Host On-Campus Interviews', 'nsbm-career-fair-2026-multinational-employers-on-campus', 'More than 1,200 final-year undergraduates interview for software engineering, cloud architecture, business analytics, and marketing roles across the campus concourse.', '<p>The NSBM Career Guidance Unit in collaboration with all faculty academic boards hosted the university’s flagship <strong>Career Fair 2026</strong>, drawing more than 60 multinational and national employers to the Pitipana campus for two days of direct recruitment.</p><p>Over 1,200 final-year students from Computing, Business, and Engineering participated in speed interviewing rounds, portfolio showcases, and technical assessments for software developer, DevOps engineer, financial analyst, and marketing management associate roles.</p><p>According to initial data from participating employers, over 380 letters of intent and fast-tracked final interview invitations were extended during the event, reflecting the stellar reputation and industry-readiness of NSBM undergraduates.</p>', 2, 1, 'assets/images/sample-careerfair.svg', 'approved', NULL, 0, 0, 1210, NOW() - INTERVAL 8 DAY);

INSERT INTO `announcements` (`id`, `title`, `content`, `priority`, `author_id`, `expiry_date`, `is_active`) VALUES
(1, 'Final Semester Examination Timetable Published on Student Portal', 'All undergraduates of Batch 25 and Batch 26 are requested to review the verified examination timetables and room allocations on LMS.', 'urgent', 1, '2026-11-30', 1),
(2, 'Main University Library Extended Hours During Revision Weeks', 'The Main Library will remain open until 10:00 PM on weekdays and until 7:00 PM on weekends throughout the upcoming exam revision period.', 'high', 1, '2026-11-20', 1),
(3, 'Campus Shuttle Bus Schedule Updates for Morning Peak Routes', 'Additional morning shuttle services from Makumbura Multimodal Transport Hub have been scheduled at 7:30 AM, 7:50 AM, and 8:15 AM daily.', 'normal', 1, '2026-12-31', 1);

INSERT INTO `comments` (`id`, `article_id`, `user_id`, `comment_text`, `status`, `created_at`) VALUES
(1, 1, 2, 'Super proud to see our Faculty of Computing getting this GPU cluster! Can we access it during weekend study hours?', 'approved', NOW() - INTERVAL 18 HOUR),
(2, 1, 3, 'Incredible step forward! Can business analytics students also utilize this lab for big data research?', 'approved', NOW() - INTERVAL 12 HOUR),
(3, 2, 2, 'Looking for one more frontend developer to join our hackathon team. Hit me up if you are in Batch 26.1 Computing!', 'approved', NOW() - INTERVAL 1 DAY),
(4, 3, 4, 'What a nail-biting match! Well played Green Lions, that final over bowling was legendary.', 'approved', NOW() - INTERVAL 2 DAY),
(5, 8, 3, 'A green teaching hospital right at Pitipana is a monumental step for NSBM. Proud to witness Phase 2 commencement!', 'approved', NOW() - INTERVAL 6 HOUR),
(6, 9, 2, 'Congratulations to all our graduating seniors! Seeing Batch 22 and 23 walk that stage is huge inspiration for us.', 'approved', NOW() - INTERVAL 1 DAY),
(7, 10, 4, 'Virtusa and WSO2 having dedicated labs on campus is such a game changer for undergraduate internships.', 'approved', NOW() - INTERVAL 2 DAY),
(8, 12, 2, '14 gold medals! The Olympic pool facilities at NSBM are definitely the best in any Sri Lankan university.', 'approved', NOW() - INTERVAL 3 DAY),
(9, 13, 3, 'Registered our Computing team for CodeSprint X! Ready for the 48-hour challenge!', 'approved', NOW() - INTERVAL 4 DAY),
(10, 15, 2, 'Huge kudos to the edge AI research team! Proving that computing undergraduates can publish world-class IEEE research.', 'approved', NOW() - INTERVAL 5 DAY);

INSERT INTO `bookmarks` (`user_id`, `article_id`) VALUES
(2, 1),
(2, 2),
(2, 8),
(2, 9),
(2, 13),
(3, 8),
(3, 10),
(3, 15);

INSERT INTO `category_subscriptions` (`user_id`, `category_id`) VALUES
(2, 1),
(2, 2),
(3, 2),
(3, 3);

