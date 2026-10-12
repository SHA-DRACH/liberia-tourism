-- =====================================================================
--  Liberia Tourism Management System  -  database schema and seed data
--  Import this file in phpMyAdmin (Import tab) or with the mysql client.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS liberia_tourism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE liberia_tourism;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments, bookings, reviews, package_destinations, packages, guides,
                     hotels, events, destinations, counties, enquiries, menu_links, pages,
                     settings, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- USERS: admin, staff and tourist accounts
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(30)  DEFAULT NULL,
    country       VARCHAR(80)  DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','staff','tourist') NOT NULL DEFAULT 'tourist',
    status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COUNTIES: the 15 political subdivisions of Liberia
-- ---------------------------------------------------------------------
CREATE TABLE counties (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80) NOT NULL UNIQUE,
    capital     VARCHAR(80) DEFAULT NULL,
    description TEXT,
    image       VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DESTINATIONS: tourist attractions
-- ---------------------------------------------------------------------
CREATE TABLE destinations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county_id   INT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    category    ENUM('Beach','Nature & Wildlife','Historical','Cultural','Waterfall') NOT NULL,
    summary     VARCHAR(255) DEFAULT NULL,
    description TEXT,
    location    VARCHAR(200) DEFAULT NULL,
    best_time   VARCHAR(120) DEFAULT NULL,
    entry_fee   DECIMAL(10,2) NOT NULL DEFAULT 0,
    image       VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dest_county FOREIGN KEY (county_id) REFERENCES counties(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- HOTELS: accommodation
-- ---------------------------------------------------------------------
CREATE TABLE hotels (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county_id       INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    address         VARCHAR(200) DEFAULT NULL,
    description     TEXT,
    amenities       VARCHAR(255) DEFAULT NULL,
    stars           TINYINT UNSIGNED NOT NULL DEFAULT 3,
    price_per_night DECIMAL(10,2) NOT NULL,
    rooms_available INT UNSIGNED NOT NULL DEFAULT 10,
    phone           VARCHAR(30) DEFAULT NULL,
    image           VARCHAR(255) DEFAULT NULL,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('active','hidden') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_hotel_county FOREIGN KEY (county_id) REFERENCES counties(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- GUIDES: tour guides
-- ---------------------------------------------------------------------
CREATE TABLE guides (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name        VARCHAR(120) NOT NULL,
    phone            VARCHAR(30) DEFAULT NULL,
    email            VARCHAR(150) DEFAULT NULL,
    languages        VARCHAR(200) DEFAULT NULL,
    experience_years TINYINT UNSIGNED NOT NULL DEFAULT 1,
    bio              TEXT,
    image            VARCHAR(255) DEFAULT NULL,
    status           ENUM('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PACKAGES: tour packages (each led by one guide)
-- ---------------------------------------------------------------------
CREATE TABLE packages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guide_id      INT UNSIGNED DEFAULT NULL,
    title         VARCHAR(150) NOT NULL,
    summary       VARCHAR(255) DEFAULT NULL,
    description   TEXT,
    includes      TEXT,
    duration_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
    price         DECIMAL(10,2) NOT NULL,
    max_people    TINYINT UNSIGNED NOT NULL DEFAULT 10,
    image         VARCHAR(255) DEFAULT NULL,
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('active','hidden') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_pkg_guide FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- A package visits many destinations; a destination appears in many packages
CREATE TABLE package_destinations (
    package_id     INT UNSIGNED NOT NULL,
    destination_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (package_id, destination_id),
    CONSTRAINT fk_pd_pkg  FOREIGN KEY (package_id)     REFERENCES packages(id)     ON DELETE CASCADE,
    CONSTRAINT fk_pd_dest FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENTS: festivals and national celebrations
-- ---------------------------------------------------------------------
CREATE TABLE events (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county_id   INT UNSIGNED DEFAULT NULL,
    title       VARCHAR(150) NOT NULL,
    location    VARCHAR(200) DEFAULT NULL,
    event_date  DATE NOT NULL,
    end_date    DATE DEFAULT NULL,
    description TEXT,
    image       VARCHAR(255) DEFAULT NULL,
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_event_county FOREIGN KEY (county_id) REFERENCES counties(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BOOKINGS: a tourist books either a package or a hotel
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference    VARCHAR(20) NOT NULL UNIQUE,
    user_id      INT UNSIGNED NOT NULL,
    booking_type ENUM('package','hotel') NOT NULL,
    package_id   INT UNSIGNED DEFAULT NULL,
    hotel_id     INT UNSIGNED DEFAULT NULL,
    start_date   DATE NOT NULL,
    end_date     DATE DEFAULT NULL,
    people       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    rooms        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    total        DECIMAL(10,2) NOT NULL,
    notes        VARCHAR(500) DEFAULT NULL,
    status       ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bk_user  FOREIGN KEY (user_id)    REFERENCES users(id),
    CONSTRAINT fk_bk_pkg   FOREIGN KEY (package_id) REFERENCES packages(id),
    CONSTRAINT fk_bk_hotel FOREIGN KEY (hotel_id)   REFERENCES hotels(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAYMENTS: simulated payment recorded against a booking
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL UNIQUE,
    method          ENUM('mtn_momo','orange_money','cash') NOT NULL,
    payer_phone     VARCHAR(30) DEFAULT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    transaction_ref VARCHAR(30) NOT NULL,
    status          ENUM('paid','pending') NOT NULL DEFAULT 'pending',
    paid_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REVIEWS: tourist ratings of destinations (shown after approval)
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    destination_id INT UNSIGNED NOT NULL,
    rating         TINYINT UNSIGNED NOT NULL,
    comment        TEXT NOT NULL,
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_user FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
    CONSTRAINT fk_rev_dest FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ENQUIRIES: messages from the contact form
-- ---------------------------------------------------------------------
CREATE TABLE enquiries (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(30) DEFAULT NULL,
    subject    VARCHAR(200) NOT NULL,
    message    TEXT NOT NULL,
    reply      TEXT,
    status     ENUM('new','replied') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTENT MANAGEMENT: everything the admin can edit on the public site
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    skey       VARCHAR(60) PRIMARY KEY,
    svalue     TEXT,
    label      VARCHAR(120) NOT NULL,
    type       ENUM('text','textarea','image','number','url') NOT NULL DEFAULT 'text',
    grp        VARCHAR(40) NOT NULL DEFAULT 'General',
    sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE pages (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug    VARCHAR(80) NOT NULL UNIQUE,
    title   VARCHAR(150) NOT NULL,
    content MEDIUMTEXT,
    status  ENUM('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE menu_links (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label      VARCHAR(60) NOT NULL,
    url        VARCHAR(255) NOT NULL,
    location   ENUM('header','explore','footer') NOT NULL DEFAULT 'header',
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA
--  Places, counties and public holidays are real. Hotels, guides, prices
--  and phone numbers are SAMPLE data for demonstration only.
-- =====================================================================

-- Default accounts (passwords are listed in README.md)
INSERT INTO users (full_name, email, phone, country, password_hash, role) VALUES
('System Administrator', 'admin@visitliberia.test',   '+231 77 000 0001', 'Liberia', '$2y$10$tAHPhfEYfsrfUjG.i9bGTuTKf4e9vlYdB0TcVRldzgQyonf8E7Y4.', 'admin'),
('Front Desk Staff',     'staff@visitliberia.test',   '+231 77 000 0002', 'Liberia', '$2y$10$mJMMOitzRp9ZL10f5YM4reA8RGlVvxXtwZgF9GEoHTdaUq3/5VW.O', 'staff'),
('Demo Tourist',         'tourist@visitliberia.test', '+231 88 000 0003', 'Liberia', '$2y$10$JtGMAZPBfvXxyNbiP5cF6eoaN63x1kxVetk/Jc7zjUR31zjnhzaPy', 'tourist');

INSERT INTO counties (id, name, capital, description) VALUES
(1,  'Bomi',             'Tubmanburg',   'A short drive from Monrovia, known for its former iron ore mines and the striking Blue Lake.'),
(2,  'Bong',             'Gbarnga',      'Central Liberia''s farming heartland and home of Kpatawee Waterfall.'),
(3,  'Gbarpolu',         'Bopolu',       'Densely forested county in the north-west with historic Bopolu town.'),
(4,  'Grand Bassa',      'Buchanan',     'Coastal county with long sandy beaches and the port city of Buchanan.'),
(5,  'Grand Cape Mount', 'Robertsport',  'Surf beaches, Lake Piso and the green slopes of Cape Mount.'),
(6,  'Grand Gedeh',      'Zwedru',       'Forest county in the south-east, a gateway to Liberia''s interior rainforest.'),
(7,  'Grand Kru',        'Barclayville', 'Quiet Atlantic coastline and traditional Kru fishing communities.'),
(8,  'Lofa',             'Voinjama',     'Mountainous northern county, rich in culture and highland scenery.'),
(9,  'Margibi',          'Kakata',       'Rubber country, Marshall''s lagoons and the country''s main international airport.'),
(10, 'Maryland',         'Harper',       'Historic Harper and Cape Palmas at the south-eastern tip of the country.'),
(11, 'Montserrado',      'Bensonville',  'The most populated county and home of the national capital, Monrovia.'),
(12, 'Nimba',            'Sanniquellie', 'Highlands of the Nimba range, with unique wildlife and cool mountain air.'),
(13, 'River Cess',       'Cestos City',  'Unspoiled rivers, forest and coast between Grand Bassa and Sinoe.'),
(14, 'River Gee',        'Fish Town',    'Remote forested county along the Cavalla River basin.'),
(15, 'Sinoe',            'Greenville',   'Home of Sapo National Park, Liberia''s largest protected rainforest.');

INSERT INTO destinations (id, county_id, name, category, summary, description, location, best_time, entry_fee, is_featured) VALUES
(1, 15, 'Sapo National Park', 'Nature & Wildlife',
 'Liberia''s largest protected rainforest, sheltering pygmy hippos, forest elephants and chimpanzees.',
 'Sapo National Park protects one of the largest remaining blocks of the Upper Guinean rainforest in West Africa.\n\nVisitors walk forest trails with local guides, travel by canoe on the Sinoe River and may see monkeys, duikers and many forest birds. The park is one of the few places where the rare pygmy hippopotamus still lives in the wild.\n\nA visit needs planning: roads are rough, especially in the rainy season, and accommodation is basic.',
 'Sinoe County, south-eastern Liberia', 'November to April (dry season)', 20.00, 1),
(2, 2, 'Kpatawee Waterfall', 'Waterfall',
 'A scenic waterfall and picnic spot surrounded by forest and farmland in Bong County.',
 'Kpatawee Waterfall is one of the most visited natural attractions in central Liberia. Water tumbles over a series of rock ledges into clear pools where visitors swim and relax.\n\nThe site is popular for family outings, school trips and weekend picnics. Short trails lead to the upper falls for those who want a longer walk.',
 'Near Gbarnga, Bong County', 'October to February, when the falls are full and the road is passable', 5.00, 1),
(3, 5, 'Robertsport Beach', 'Beach',
 'A laid-back fishing town with some of the best surfing waves in West Africa.',
 'Robertsport sits on a peninsula between the Atlantic Ocean and Lake Piso. Its long point breaks attract surfers from around the world, while calm corners of the bay suit swimmers and beginners.\n\nBeyond the beach you can watch fishermen land their catch in colourful canoes, climb Cape Mount for a view over the coast, or simply enjoy fresh grilled fish at sunset.',
 'Robertsport, Grand Cape Mount County', 'November to April for calm weather; May to September for the biggest surf', 0.00, 1),
(4, 5, 'Lake Piso', 'Nature & Wildlife',
 'Liberia''s largest lake, a wide lagoon rich in birdlife beside Robertsport.',
 'Lake Piso is a large open coastal lagoon linked to the Atlantic. Mangroves, small islands and fishing villages line its shores.\n\nBoat trips on the lake are a peaceful way to see water birds and village life, and the area is recognised as an important wetland.',
 'Grand Cape Mount County', 'November to April', 0.00, 0),
(5, 11, 'Providence Island', 'Historical',
 'The landing place of the first freed settlers in 1822 and the birthplace of modern Liberia.',
 'Providence Island lies in the Mesurado River in the heart of Monrovia. It is where freed people of colour from the United States first settled in 1822, an event that led to the founding of Liberia.\n\nThe island is an important national heritage site. A guided visit explains the meeting of settlers and indigenous people and the story of the nation''s beginnings.',
 'Mesurado River, Monrovia', 'All year', 5.00, 1),
(6, 12, 'East Nimba Nature Reserve', 'Nature & Wildlife',
 'Cool mountain forest on the Nimba range, with wildlife found nowhere else.',
 'The Nimba mountains rise on the border of Liberia, Guinea and Côte d''Ivoire. The Liberian side is protected as the East Nimba Nature Reserve.\n\nHikers enjoy cool air, mountain views and forest trails. The range is known for rare plants and animals, and community guides from nearby towns lead the walks.',
 'Near Yekepa, Nimba County', 'November to March', 10.00, 1),
(7, 1, 'Blue Lake', 'Nature & Wildlife',
 'A deep blue lake in a former iron ore mine, ringed by steep green hills.',
 'Blue Lake formed in an old open-pit iron ore mine near Tubmanburg. The still, deep water reflects the sky and gives the lake its name.\n\nIt is an easy day trip from Monrovia and a favourite place for picnics and photography.',
 'Tubmanburg, Bomi County', 'November to April', 3.00, 0),
(8, 9, 'Monkey Island, Marshall', 'Nature & Wildlife',
 'River islands near Marshall where a colony of chimpanzees can be watched from a boat.',
 'Close to the town of Marshall, a group of small mangrove islands is home to chimpanzees that were retired from medical research. Caretakers bring them food by boat each day.\n\nVisitors travel by canoe or motorboat and watch the chimpanzees from the water. For safety, nobody lands on the islands.',
 'Marshall, Margibi County', 'All year; mornings and late afternoons are best', 10.00, 1),
(9, 4, 'Buchanan Beaches', 'Beach',
 'Wide, quiet beaches beside Liberia''s second port city.',
 'Buchanan offers long stretches of sand with far fewer visitors than the beaches near Monrovia. Local restaurants serve fresh seafood and the town has a relaxed, friendly feel.\n\nIt makes a good overnight stop on the road to the south-east.',
 'Buchanan, Grand Bassa County', 'November to April', 0.00, 0),
(10, 11, 'Liberia National Museum', 'Cultural',
 'The national collection of masks, historic photographs and cultural objects in Monrovia.',
 'The National Museum on Broad Street presents Liberia''s story through traditional masks, musical instruments, historic documents and photographs.\n\nIt is the best place to start a visit to the country, giving context to the many ethnic groups and the history you will meet on your travels.',
 'Broad Street, Monrovia', 'All year', 5.00, 0),
(11, 10, 'Harper and Cape Palmas', 'Historical',
 'A historic seaside town with old settler architecture and a lighthouse on the cape.',
 'Harper stands on Cape Palmas near the border with Côte d''Ivoire. The town is known for its nineteenth-century houses, palm-lined streets and calm beaches.\n\nWalk to the lighthouse for a view of the coast, then visit nearby fishing villages and Lake Shepherd.',
 'Harper, Maryland County', 'December to April', 0.00, 0),
(12, 11, 'Ducor Hill, Monrovia', 'Historical',
 'The highest point in central Monrovia, with sweeping views over the city and the ocean.',
 'Ducor Hill rises above the oldest part of Monrovia. From the top you can see the Atlantic, the Mesurado River, the Freeport and the whole peninsula.\n\nThe hill carries a national monument and the shell of the once-famous Ducor Hotel, a reminder of the city''s past.',
 'Mamba Point, Monrovia', 'All year; go late afternoon for the sunset', 0.00, 0);

INSERT INTO hotels (id, county_id, name, address, description, amenities, stars, price_per_night, rooms_available, phone, is_featured) VALUES
(1, 11, 'Mesurado Bay Hotel',         'Mamba Point, Monrovia',      'Comfortable city hotel with ocean views, close to embassies, restaurants and Providence Island.', 'Wi-Fi, Air conditioning, Restaurant, Pool, Airport shuttle', 4, 120.00, 40, '+231 77 100 2001', 1),
(2, 5,  'Cape Mount Surf Lodge',      'Beachfront, Robertsport',    'Simple beachfront cabins a few steps from the surf, with board hire and a seafood kitchen.',       'Beachfront, Restaurant, Surfboard hire, Solar power',       3, 65.00,  12, '+231 77 100 2002', 1),
(3, 4,  'Atlantic Breeze Resort',     'Beach Road, Buchanan',       'Family-friendly resort on a quiet stretch of sand outside Buchanan.',                              'Wi-Fi, Air conditioning, Restaurant, Bar, Beach access',    3, 80.00,  25, '+231 77 100 2003', 1),
(4, 12, 'Nimba Highlands Guesthouse', 'Hill Station Road, Yekepa',  'Cool mountain guesthouse and base for hikes in the East Nimba Nature Reserve.',                    'Breakfast, Hot water, Guided hikes, Parking',               2, 45.00,  10, '+231 77 100 2004', 0),
(5, 2,  'Kpatawee Eco Lodge',         'Kpatawee Road, near Gbarnga','Thatched lodges in the forest, a short walk from the waterfall.',                                   'Breakfast, Nature trails, Restaurant, Parking',             3, 55.00,  8,  '+231 77 100 2005', 0),
(6, 10, 'Harper Heritage Inn',        'Maryland Avenue, Harper',    'Restored townhouse in the old centre of Harper, near the beach and the lighthouse.',               'Wi-Fi, Air conditioning, Breakfast, Generator',             3, 60.00,  14, '+231 77 100 2006', 0);

INSERT INTO guides (id, full_name, phone, email, languages, experience_years, bio) VALUES
(1, 'Emmanuel Kollie', '+231 88 200 3001', 'emmanuel@visitliberia.test', 'English, Kpelle',        8, 'Born in Bong County, Emmanuel leads waterfall and cultural village tours across central Liberia.'),
(2, 'Fatu Sherman',    '+231 88 200 3002', 'fatu@visitliberia.test',     'English, Vai, French',   6, 'Fatu grew up in Robertsport and knows every beach, surf break and fishing village around Lake Piso.'),
(3, 'Moses Flomo',     '+231 88 200 3003', 'moses@visitliberia.test',    'English, Mano, Gio',     10, 'A trained naturalist who guides forest walks in Nimba and expeditions to Sapo National Park.'),
(4, 'Comfort Dolo',    '+231 88 200 3004', 'comfort@visitliberia.test',  'English, Bassa',         5, 'A Monrovia city guide with a passion for Liberian history, food and markets.');

INSERT INTO packages (id, guide_id, title, summary, description, includes, duration_days, price, max_people, is_featured) VALUES
(1, 4, 'Monrovia Heritage City Tour',
 'One day through the capital''s history: Providence Island, the National Museum and Ducor Hill.',
 'Discover how Liberia began. Start at Providence Island, continue to the National Museum on Broad Street, taste Liberian dishes at a local cookshop and finish with sunset over the city from Ducor Hill.',
 'Guide\nTransport within Monrovia\nEntry fees\nLunch', 1, 45.00, 15, 1),
(2, 2, 'Robertsport Surf and Lake Piso Weekend',
 'Three days of beach, surf lessons and a boat trip on Liberia''s largest lake.',
 'Leave Monrovia on Friday morning for Robertsport. Spend two days on the beach with optional surf lessons, take a canoe trip on Lake Piso and hike Cape Mount before returning on Sunday.',
 'Guide\nReturn transport from Monrovia\n2 nights accommodation\nBreakfast and dinner\nBoat trip', 3, 220.00, 10, 1),
(3, 3, 'Sapo Rainforest Expedition',
 'Five days of guided trekking and canoeing in Liberia''s largest rainforest park.',
 'A true adventure for fit travellers. Travel overland to Sinoe County, camp at the edge of the park and spend three days walking forest trails and paddling the Sinoe River with park rangers.',
 'Naturalist guide\n4x4 transport\nCamping equipment\nAll meals\nPark fees', 5, 650.00, 8, 1),
(4, 1, 'Kpatawee Waterfall Day Trip',
 'A relaxed day out from Monrovia to swim and picnic at Kpatawee Waterfall.',
 'Drive through rubber plantations and farmland to Bong County, walk to the falls, swim in the pools and enjoy a picnic lunch before the return journey.',
 'Guide\nReturn transport from Monrovia\nEntry fee\nPicnic lunch', 1, 60.00, 12, 0),
(5, 3, 'Nimba Highlands Adventure',
 'Four days of mountain hiking and village visits in the Nimba range.',
 'Travel to Yekepa, hike in the East Nimba Nature Reserve with community guides, visit the old mining sites and meet craft makers in nearby towns.',
 'Guide\nTransport\n3 nights accommodation\nBreakfast and dinner\nReserve fees', 4, 480.00, 8, 0),
(6, 4, 'Marshall Monkey Island and Beach Escape',
 'A boat trip to see the chimpanzees of Marshall, followed by an afternoon on the beach.',
 'Only an hour from Monrovia. Cruise the mangrove channels to watch the chimpanzees at feeding time, then relax on the Atlantic beach with a grilled fish lunch.',
 'Guide\nReturn transport from Monrovia\nBoat hire\nLunch', 1, 75.00, 10, 1);

INSERT INTO package_destinations (package_id, destination_id) VALUES
(1,5),(1,10),(1,12),(2,3),(2,4),(3,1),(4,2),(5,6),(6,8);

INSERT INTO events (county_id, title, location, event_date, end_date, description) VALUES
(NULL, 'National Thanksgiving Day', 'Nationwide',                '2026-11-05', NULL, 'A public holiday observed on the first Thursday of November with church services and family gatherings.'),
(11,   'Christmas and New Year Beach Season', 'Beaches around Monrovia', '2026-12-24', '2027-01-02', 'The busiest time of year on the coast, with music, food stalls and family outings on the beaches.'),
(NULL, 'Decoration Day',            'Nationwide',                '2027-03-10', NULL, 'On the second Wednesday of March families clean and decorate the graves of their relatives.'),
(NULL, 'National Unification Day',  'Nationwide',                '2027-05-14', NULL, 'A day celebrating unity among all the peoples of Liberia.'),
(11,   'Independence Day',          'Monrovia and all counties', '2027-07-26', NULL, 'Liberia declared independence on 26 July 1847. The day is marked with parades, official ceremonies and celebrations across the country.'),
(11,   'National Flag Day',         'Monrovia',                  '2027-08-24', NULL, 'Schools and institutions parade through the streets to honour the Lone Star flag.');

INSERT INTO reviews (user_id, destination_id, rating, comment, status) VALUES
(3, 3, 5, 'The waves were perfect and the people in Robertsport made us feel at home. We will be back.', 'approved'),
(3, 2, 4, 'A beautiful place for a family picnic. Go early to avoid the weekend crowd.', 'approved'),
(3, 5, 5, 'Our guide explained the history so well. Every Liberian should visit at least once.', 'approved');

-- ---------- Sample photographs (Wikimedia Commons, credited on credits.php) ----------
UPDATE destinations SET image = 'assets/images/photos/sapo.jpg'              WHERE id = 1;
UPDATE destinations SET image = 'assets/images/photos/kpatawee.jpg'          WHERE id = 2;
UPDATE destinations SET image = 'assets/images/photos/robertsport.jpg'       WHERE id = 3;
UPDATE destinations SET image = 'assets/images/photos/lake-piso.jpg'         WHERE id = 4;
UPDATE destinations SET image = 'assets/images/photos/providence-island.jpg' WHERE id = 5;
UPDATE destinations SET image = 'assets/images/photos/nimba.jpg'             WHERE id = 6;
UPDATE destinations SET image = 'assets/images/photos/monkey-island.jpg'     WHERE id = 8;
UPDATE destinations SET image = 'assets/images/photos/national-museum.jpg'   WHERE id = 10;
UPDATE destinations SET image = 'assets/images/photos/harper.jpg'            WHERE id = 11;
UPDATE destinations SET image = 'assets/images/photos/ducor-hill.jpg'        WHERE id = 12;
-- The hotels are sample entries, so these show scenery of the area, not the hotel
UPDATE hotels SET image = 'assets/images/photos/mesurado-river.jpg'   WHERE id = 1;
UPDATE hotels SET image = 'assets/images/photos/robertsport-surf.jpg' WHERE id = 2;
UPDATE hotels SET image = 'assets/images/photos/marshall-coast.jpg'   WHERE id = 3;
UPDATE hotels SET image = 'assets/images/photos/nimba-highlands.jpg'  WHERE id = 4;
UPDATE hotels SET image = 'assets/images/photos/forest-road.jpg'      WHERE id = 5;
-- Tour packages reuse the photo of their main destination
UPDATE packages SET image = 'assets/images/photos/providence-island.jpg' WHERE id = 1;
UPDATE packages SET image = 'assets/images/photos/robertsport-surf.jpg'  WHERE id = 2;
UPDATE packages SET image = 'assets/images/photos/sapo.jpg'              WHERE id = 3;
UPDATE packages SET image = 'assets/images/photos/kpatawee.jpg'          WHERE id = 4;
UPDATE packages SET image = 'assets/images/photos/nimba-highlands.jpg'   WHERE id = 5;
UPDATE packages SET image = 'assets/images/photos/monkey-island.jpg'     WHERE id = 6;

-- ---------- Navigation links (editable by the admin) ----------
INSERT INTO menu_links (label, url, location, sort_order) VALUES
('Home',          'index.php',              'header', 1),
('Destinations',  'destinations.php',       'header', 2),
('Tour Packages', 'packages.php',           'header', 3),
('Hotels',        'hotels.php',             'header', 4),
('Events',        'events.php',             'header', 5),
('Guides',        'guides.php',             'header', 6),
('About',         'page.php?slug=about',    'header', 7),
('Contact',       'contact.php',            'header', 8),
('Destinations',  'destinations.php',       'explore', 1),
('Tour packages', 'packages.php',           'explore', 2),
('Hotels',        'hotels.php',             'explore', 3),
('Events',        'events.php',             'explore', 4),
('Tour guides',   'guides.php',             'explore', 5),
('About Liberia',      'page.php?slug=about',   'footer', 1),
('Travel Information', 'page.php?slug=travel-information', 'footer', 2),
('Frequently Asked Questions', 'page.php?slug=faq', 'footer', 3),
('Terms and Conditions', 'page.php?slug=terms', 'footer', 4),
('Privacy Policy',     'page.php?slug=privacy', 'footer', 5),
('Contact Us',         'contact.php',           'footer', 6),
('Photo Credits',      'credits.php',           'footer', 7);

-- ---------- Pages (editable by the admin) ----------
-- Formatting: blank line = new paragraph, "## " = heading, "- " = bullet point
INSERT INTO pages (slug, title, content) VALUES
('about', 'About Liberia',
 'Liberia lies on the Atlantic coast of West Africa, bordered by Sierra Leone, Guinea and Côte d''Ivoire. Founded in 1822 and independent since 1847, it is Africa''s oldest republic.\n\n## A land of forest and coast\nAlmost 580 kilometres of coastline, some of West Africa''s largest remaining rainforest, mountains in the north and a warm welcome everywhere make Liberia a rewarding place to explore.\n\n## About this system\nThe Liberia Tourism Management System brings destinations, hotels, tour packages, guides and events together in one place, so that visitors can plan and book a trip online and tourism officers can manage everything from a single dashboard.\n\n## Our goals\n- Promote Liberia''s attractions to local and international visitors\n- Make booking tours and accommodation simple and transparent\n- Support local guides, hotels and communities\n- Give tourism managers accurate, up-to-date records and reports'),
('travel-information', 'Travel Information',
 '## Getting here\nMost international visitors arrive at Roberts International Airport in Margibi County, about an hour from Monrovia.\n\n## Climate\nLiberia is warm all year. The dry season, from about November to April, is the easiest time to travel. The rainy season runs from about May to October and some roads become difficult.\n\n## Money\nBoth the Liberian dollar (LRD) and the United States dollar (USD) are used. Mobile money is widely accepted.\n\n## Language\nEnglish is the official language. More than sixteen indigenous languages are also spoken.\n\n## Before you travel\n- Check visa and vaccination requirements with a Liberian embassy before booking\n- Carry some cash when travelling outside Monrovia\n- Allow extra time for road journeys in the rainy season'),
('faq', 'Frequently Asked Questions',
 '## How do I book a tour?\nCreate a free account, open the tour package you like, choose your date and number of people, then confirm and pay.\n\n## How can I pay?\nYou can pay with MTN Mobile Money, Orange Money or cash on arrival.\n\n## Can I cancel a booking?\nYes. Open My Bookings in your dashboard and cancel any booking that has not yet been completed.\n\n## When is my booking confirmed?\nA tourism officer reviews every booking. You will see the status change to Confirmed in your dashboard.\n\n## Can I leave a review?\nYes. Sign in, open a destination and share your rating. Reviews appear after approval.'),
('terms', 'Terms and Conditions',
 'By using this website you agree to the following terms.\n\n## Bookings\nA booking is only guaranteed once it has been confirmed by a tourism officer.\n\n## Prices\nPrices are shown in United States dollars with an approximate Liberian dollar equivalent.\n\n## Cancellations\nBookings may be cancelled from your dashboard before the start date.\n\n## Conduct\nVisitors are asked to respect local customs, communities and the natural environment.'),
('privacy', 'Privacy Policy',
 'We collect only the information needed to manage your account and bookings: your name, email address, phone number and country.\n\n## How we use it\n- To process and confirm your bookings\n- To contact you about your trip\n- To improve tourism services\n\n## Security\nPasswords are stored in encrypted (hashed) form and are never visible to staff.\n\n## Your choices\nYou can update your details at any time from your profile page.');

-- ---------- Site settings (editable by the admin) ----------
INSERT INTO settings (skey, svalue, label, type, grp, sort_order) VALUES
('site_name',        'Visit Liberia', 'Site name', 'text', 'General', 1),
('site_tagline',     'Tourism Management System', 'Tagline (shown beside the name)', 'text', 'General', 2),
('site_logo',        '', 'Logo image (optional)', 'image', 'General', 3),
('usd_to_lrd',       '190', 'Exchange rate: 1 USD in Liberian dollars', 'number', 'General', 4),
('contact_address',  'Ministry of Information, Cultural Affairs and Tourism, Capitol Hill, Monrovia, Liberia', 'Address', 'textarea', 'Contact', 1),
('contact_phone',    '+231 77 000 0000', 'Phone number', 'text', 'Contact', 2),
('contact_email',    'info@visitliberia.test', 'Email address', 'text', 'Contact', 3),
('contact_hours',    'Monday to Friday, 8:00 am to 5:00 pm', 'Opening hours', 'text', 'Contact', 4),
('contact_intro',    'Have a question about travelling in Liberia? Send us a message and a tourism officer will reply.', 'Contact page introduction', 'textarea', 'Contact', 5),
('hero_title',       'Discover Liberia, the Land of the Lone Star', 'Hero title', 'text', 'Home page', 1),
('hero_subtitle',    'Rainforest, surf beaches, waterfalls and two centuries of history. Plan and book your whole trip in one place.', 'Hero subtitle', 'textarea', 'Home page', 2),
('hero_image',       '', 'Hero background image (optional)', 'image', 'Home page', 3),
('hero_btn_text',    'Explore destinations', 'Hero button text', 'text', 'Home page', 4),
('hero_btn_link',    'destinations.php', 'Hero button link', 'text', 'Home page', 5),
('hero_btn2_text',   'View tour packages', 'Second hero button text', 'text', 'Home page', 6),
('hero_btn2_link',   'packages.php', 'Second hero button link', 'text', 'Home page', 7),
('home_dest_title',  'Featured destinations', 'Destinations section title', 'text', 'Home page', 10),
('home_dest_text',   'From the surf of Robertsport to the rainforest of Sapo, start with the places every visitor should see.', 'Destinations section text', 'textarea', 'Home page', 11),
('home_pkg_title',   'Popular tour packages', 'Packages section title', 'text', 'Home page', 12),
('home_pkg_text',    'Ready-made trips led by licensed local guides. Transport, meals and entry fees are taken care of.', 'Packages section text', 'textarea', 'Home page', 13),
('home_hotel_title', 'Places to stay', 'Hotels section title', 'text', 'Home page', 14),
('home_hotel_text',  'Hotels, lodges and guesthouses across the counties.', 'Hotels section text', 'textarea', 'Home page', 15),
('home_event_title', 'Upcoming events', 'Events section title', 'text', 'Home page', 16),
('home_review_title','What visitors say', 'Reviews section title', 'text', 'Home page', 17),
('about_title',      'Africa''s oldest republic is ready to welcome you', 'About block title', 'text', 'Home page', 20),
('about_text',       'Liberia offers almost 580 kilometres of Atlantic coastline, some of the largest rainforest left in West Africa and a history unlike any other nation on the continent. This system helps you find where to go, where to stay and who to travel with.', 'About block text', 'textarea', 'Home page', 21),
('about_image',      '', 'About block image (optional)', 'image', 'Home page', 22),
('about_btn_text',   'Learn more about Liberia', 'About block button text', 'text', 'Home page', 23),
('about_btn_link',   'page.php?slug=about', 'About block button link', 'text', 'Home page', 24),
('feature1_title',   'Trusted local guides', 'Feature 1 title', 'text', 'Home page', 30),
('feature1_text',    'Every package is led by an experienced Liberian guide who knows the land and its people.', 'Feature 1 text', 'textarea', 'Home page', 31),
('feature2_title',   'Simple online booking', 'Feature 2 title', 'text', 'Home page', 32),
('feature2_text',    'Book tours and hotels in minutes and pay with mobile money or cash.', 'Feature 2 text', 'textarea', 'Home page', 33),
('feature3_title',   'All fifteen counties', 'Feature 3 title', 'text', 'Home page', 34),
('feature3_text',    'Browse attractions county by county, from Grand Cape Mount to Maryland.', 'Feature 3 text', 'textarea', 'Home page', 35),
('cta_title',        'Ready to start your Liberian journey?', 'Call-to-action title', 'text', 'Home page', 40),
('cta_text',         'Create a free account to book tours, reserve hotels and keep all your trips in one place.', 'Call-to-action text', 'textarea', 'Home page', 41),
('cta_btn_text',     'Create an account', 'Call-to-action button text', 'text', 'Home page', 42),
('cta_btn_link',     'register.php', 'Call-to-action button link', 'text', 'Home page', 43),
('footer_about',     'The official demonstration portal for discovering, planning and booking tourism in the Republic of Liberia.', 'Footer description', 'textarea', 'Footer', 1),
('footer_copyright', 'Liberia Tourism Management System. All rights reserved.', 'Copyright text', 'text', 'Footer', 2),
('facebook_url',     'https://facebook.com/', 'Facebook link', 'url', 'Footer', 3),
('instagram_url',    'https://instagram.com/', 'Instagram link', 'url', 'Footer', 4),
('x_url',            '', 'X (Twitter) link', 'url', 'Footer', 5),
('youtube_url',      '', 'YouTube link', 'url', 'Footer', 6);
