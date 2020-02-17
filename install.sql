DROP TABLE wcf1_visitor;
CREATE TABLE wcf1_visitor (
	visitorID	INT(10)			NOT NULL	AUTO_INCREMENT	PRIMARY KEY,
	requestURI	VARCHAR(255)		NOT NULL,
	title		VARCHAR(255)		NOT NULL,
	host		VARCHAR(255)		NOT NULL,
	isRegistered	TINYINT(1)		NOT NULL DEFAULT 0,
	time		INT(10)			NOT NULL
);
