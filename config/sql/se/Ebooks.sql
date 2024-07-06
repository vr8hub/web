CREATE TABLE `Ebooks` (
  `EbookId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Identifier` varchar(511) NOT NULL,
  `Created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated` timestamp NOT NULL,
  `WwwFilesystemPath` varchar(511) NOT NULL,
  `RepoFilesystemPath` varchar(511) NOT NULL,
  `KindleCoverUrl` varchar(511) NULL,
  `EpubUrl` varchar(511) NULL,
  `AdvancedEpubUrl` varchar(511) NULL,
  `KepubUrl` varchar(511) NULL,
  `Azw3Url` varchar(511) NULL,
  `DistCoverUrl` varchar(511) NULL,
  `Title` varchar(255) NOT NULL,
  `FullTitle` varchar(255) NULL,
  `AlternateTitle` varchar(255) NULL,
  `Description` text NOT NULL,
  `LongDescription` text NOT NULL,
  `Language` varchar(10) NULL,
  `WordCount` int(10) unsigned NOT NULL,
  `ReadingEase` float NOT NULL,
  `GitHubUrl` varchar(255) NULL,
  `WikipediaUrl` varchar(255) NULL,
  `EbookCreated` datetime NOT NULL,
  `EbookUpdated` datetime NOT NULL,
  `TextSinglePageByteCount` bigint unsigned NOT NULL,
  `IndexableText` text NOT NULL,
  PRIMARY KEY (`EbookId`),
  UNIQUE KEY `index1` (`Identifier`),
  KEY `index2` (`EbookCreated`),
  FULLTEXT `idxSearch` (`IndexableText`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
