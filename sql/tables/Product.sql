create table Product (
	id serial,
	catalog int not null references Catalog(id) on delete cascade,
	title varchar(255) not null,
	html_title varchar(255),
	bodytext text,
	meta_description text,
	createdate timestamp,
	shortname varchar(255),
	keywords varchar(255),
	ppc_ad_headline varchar(25),
	ppc_ad_description1 varchar(35),
	ppc_ad_description2 varchar(35),
	primary key (id)
);

CREATE INDEX Product_catalog_index ON Product(catalog);
