DROP TABLE wcf1_visitor_daily;
CREATE TABLE wcf1_visitor_daily (
	visitID		INT(10)		NOT NULL	AUTO_INCREMENT PRIMARY KEY,
	date		DATE		NOT NULL,
	counter		INT(10)		NOT NULL DEFAULT 0,
	isRegistered	TINYINT(1)	NOT NULL DEFAULT 0,
	
	UNIQUE KEY (date, isRegistered)
);

DROP TABLE wcf1_visitor_url_daily;
CREATE TABLE wcf1_visitor_url_daily (
	visitID		INT(10)		NOT NULL	AUTO_INCREMENT PRIMARY KEY,
	requestURI	VARCHAR(255)	NOT NULL,
	date		DATE		NOT NULL,
	counter		INT(10)		NOT NULL DEFAULT 0,
	isRegistered	TINYINT(1)	NOT NULL DEFAULT 0,
	
	UNIQUE KEY (requestURI, date, isRegistered)
);

ALTER TABLE wcf1_visitor ADD languageID INT(10) DEFAULT NULL;
ALTER TABLE wcf1_visitor ADD pageID INT(10) DEFAULT NULL;
ALTER TABLE wcf1_visitor ADD pageObjectID INT(10) DEFAULT NULL;

ALTER TABLE wcf1_visitor ADD FOREIGN KEY (languageID) REFERENCES wcf1_language (languageID) ON DELETE SET NULL;
ALTER TABLE wcf1_visitor ADD FOREIGN KEY (pageID) REFERENCES wcf1_page (pageID) ON DELETE SET NULL;
