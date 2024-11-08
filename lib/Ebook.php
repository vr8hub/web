<?

use Safe\DateTimeImmutable;

use function Safe\file_get_contents;
use function Safe\filesize;
use function Safe\json_encode;
use function Safe\glob;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\shell_exec;

/**
 * @property array<GitCommit> $GitCommits
 * @property array<EbookTag> $Tags
 * @property array<LocSubject> $LocSubjects
 * @property array<CollectionMembership> $CollectionMemberships
 * @property array<EbookSource> $Sources
 * @property array<Contributor> $Authors
 * @property array<Contributor> $Illustrators
 * @property array<Contributor> $Translators
 * @property array<Contributor> $Contributors
 * @property ?array<string> $TocEntries
 * @property string $Url
 * @property bool $HasDownloads
 * @property string $UrlSafeIdentifier
 * @property string $HeroImageUrl
 * @property string $HeroImageAvifUrl
 * @property string $HeroImage2xUrl
 * @property string $HeroImage2xAvifUrl
 * @property string $CoverImageUrl
 * @property string $CoverImageAvifUrl
 * @property string $CoverImage2xUrl
 * @property string $CoverImage2xAvifUrl
 * @property string $ReadingEaseDescription
 * @property string $ReadingTime
 * @property string $AuthorsHtml
 * @property string $AuthorsUrl
 * @property string $ContributorsHtml
 * @property string $TitleWithCreditsHtml
 * @property string $TextUrl
 * @property string $TextSinglePageUrl
 * @property string $TextSinglePageSizeFormatted
 * @property string $IndexableText
 */
class Ebook{
	use Traits\Accessor;

	public ?int $EbookId = null;
	public string $Identifier;
	public string $WwwFilesystemPath;
	public string $RepoFilesystemPath;
	public ?string $KindleCoverUrl = null;
	public ?string $EpubUrl = null;
	public ?string $AdvancedEpubUrl = null;
	public ?string $KepubUrl = null;
	public ?string $Azw3Url = null;
	public ?string $DistCoverUrl = null;
	public ?string $Title = null;
	public ?string $FullTitle = null;
	public ?string $AlternateTitle = null;
	public ?string $Description = null;
	public ?string $LongDescription = null;
	public ?string $Language = null;
	public int $WordCount;
	public float $ReadingEase;
	public ?string $GitHubUrl = null;
	public ?string $WikipediaUrl = null;
	public DateTimeImmutable $EbookCreated;
	public DateTimeImmutable $EbookUpdated;
	public DateTimeImmutable $Created;
	public DateTimeImmutable $Updated;
	public ?int $TextSinglePageByteCount = null;
	/** @var array<GitCommit> $_GitCommits */
	protected $_GitCommits = null;
	/** @var array<EbookTag> $_Tags */
	protected $_Tags = null;
	/** @var array<LocSubject> $_LocSubjects */
	protected $_LocSubjects = null;
	/** @var array<CollectionMembership> $_CollectionMemberships */
	protected $_CollectionMemberships = null;
	/** @var array<EbookSource> $_Sources */
	protected $_Sources = null;
	/** @var array<Contributor> $_Authors */
	protected $_Authors = null;
	/** @var array<Contributor> $_Illustrators */
	protected $_Illustrators = null;
	/** @var array<Contributor> $_Translators */
	protected $_Translators = null;
	/** @var array<Contributor> $_Contributors */
	protected $_Contributors = null;
	/** @var ?array<string> $_TocEntries */
	protected $_TocEntries = null; // A list of non-Roman ToC entries ONLY IF the work has the 'se:is-a-collection' metadata element, null otherwise.
	protected ?string $_Url = null;
	protected ?bool $_HasDownloads = null;
	protected ?string $_UrlSafeIdentifier = null;
	protected ?string $_HeroImageUrl = null;
	protected ?string $_HeroImageAvifUrl = null;
	protected ?string $_HeroImage2xUrl = null;
	protected ?string $_HeroImage2xAvifUrl = null;
	protected ?string $_CoverImageUrl = null;
	protected ?string $_CoverImageAvifUrl = null;
	protected ?string $_CoverImage2xUrl = null;
	protected ?string $_CoverImage2xAvifUrl = null;
	protected ?string $_ReadingEaseDescription = null;
	protected ?string $_ReadingTime = null;
	protected ?string $_AuthorsHtml = null;
	protected ?string $_AuthorsUrl = null; // This is a single URL even if there are multiple authors; for example, /ebooks/karl-marx_friedrich-engels/
	protected ?string $_ContributorsHtml = null;
	protected ?string $_TitleWithCreditsHtml = null;
	protected ?string $_TextUrl = null;
	protected ?string $_TextSinglePageUrl = null;
	protected ?string $_TextSinglePageSizeFormatted = null;
	protected ?string $_IndexableText = null;

	// *******
	// GETTERS
	// *******

	/**
	 * @return array<GitCommit>
	 */
	protected function GetGitCommits(): array{
		if($this->_GitCommits === null){
			$this->_GitCommits = Db::Query('
							SELECT *
							from GitCommits
							where EbookId = ?
							order by Created desc
						', [$this->EbookId], GitCommit::class);
		}

		return $this->_GitCommits;
	}

	/**
	 * @return array<EbookTag>
	 */
	protected function GetTags(): array{
		if($this->_Tags === null){
			$this->_Tags = Db::Query('
						SELECT t.*
						from Tags t
						inner join EbookTags et using (TagId)
						where EbookId = ?
						order by SortOrder asc
					', [$this->EbookId], EbookTag::class);
		}

		return $this->_Tags;
	}

	/**
	 * @return array<LocSubject>
	 */
	protected function GetLocSubjects(): array{
		if($this->_LocSubjects === null){
			$this->_LocSubjects = Db::Query('
							SELECT l.*
							from LocSubjects l
							inner join EbookLocSubjects el using (LocSubjectId)
							where EbookId = ?
							order by SortOrder asc
					', [$this->EbookId], LocSubject::class);
		}

		return $this->_LocSubjects;
	}

	/**
	 * @return array<CollectionMembership>
	 */
	protected function GetCollectionMemberships(): array{
		if($this->_CollectionMemberships === null){
			$this->_CollectionMemberships = Db::Query('
							SELECT *
							from CollectionEbooks
							where EbookId = ?
							order by SortOrder asc
						', [$this->EbookId], CollectionMembership::class);
		}

		return $this->_CollectionMemberships;
	}

	/**
	 * @return array<EbookSource>
	 */
	protected function GetSources(): array{
		if($this->_Sources === null){
			$this->_Sources = Db::Query('
						SELECT *
						from EbookSources
						where EbookId = ?
						order by SortOrder asc
					', [$this->EbookId], EbookSource::class);
		}

		return $this->_Sources;
	}

	/**
	 * @return array<Contributor>
	 */
	protected function GetAuthors(): array{
		if($this->_Authors === null){
			$this->_Authors = Db::Query('
						SELECT *
						from Contributors
						where EbookId = ?
							and MarcRole = ?
						order by SortOrder asc
					', [$this->EbookId, 'aut'], Contributor::class);
		}

		return $this->_Authors;
	}

	/**
	 * @return array<Contributor>
	 */
	protected function GetIllustrators(): array{
		if($this->_Illustrators === null){
			$this->_Illustrators = Db::Query('
							SELECT *
							from Contributors
							where EbookId = ?
								and MarcRole = ?
							order by SortOrder asc
						', [$this->EbookId, 'ill'], Contributor::class);
		}

		return $this->_Illustrators;
	}

	/**
	 * @return array<Contributor>
	 */
	protected function GetTranslators(): array{
		if($this->_Translators === null){
			$this->_Translators = Db::Query('
							SELECT *
							from Contributors
							where EbookId = ?
								and MarcRole = ?
							order by SortOrder asc
						', [$this->EbookId, 'trl'], Contributor::class);
		}

		return $this->_Translators;
	}

	/**
	 * @return array<Contributor>
	 */
	protected function GetContributors(): array{
		if($this->_Contributors === null){
			$this->_Contributors = Db::Query('
							SELECT *
							from Contributors
							where EbookId = ?
								and MarcRole = ?
							order by SortOrder asc
						', [$this->EbookId, 'ctb'], Contributor::class);
		}

		return $this->_Contributors;
	}

	/**
	 * @return array<string>
	 */
	protected function GetTocEntries(): array{
		if($this->_TocEntries === null){
			$this->_TocEntries = [];

			$result = Db::Query('
					SELECT *
					from TocEntries
					where EbookId = ?
					order by SortOrder asc
				', [$this->EbookId], stdClass::class);

			foreach($result as $row){
				$this->_TocEntries[] = $row->TocEntry;
			}
		}

		return $this->_TocEntries;
	}

	protected function GetUrl(): string{
		if($this->_Url === null){
			$this->_Url = str_replace(WEB_ROOT, '', $this->WwwFilesystemPath);
		}

		return $this->_Url;
	}

	protected function GetHasDownloads(): bool{
		if($this->_HasDownloads === null){
			$this->_HasDownloads = $this->EpubUrl || $this->AdvancedEpubUrl || $this->KepubUrl || $this->Azw3Url;
		}

		return $this->_HasDownloads;
	}

	protected function GetUrlSafeIdentifier(): string{
		if($this->_UrlSafeIdentifier === null){
			$this->_UrlSafeIdentifier = str_replace(['url:https://standardebooks.org/ebooks/', '/'], ['', '_'], $this->Identifier);
		}

		return $this->_UrlSafeIdentifier;
	}

	private function GetLatestCommitHash(): string{
		return substr(sha1($this->GitCommits[0]->Hash), 0, 8);
	}

	protected function GetHeroImageUrl(): string{
		if($this->_HeroImageUrl === null){
			$this->_HeroImageUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-hero.jpg';
		}

		return $this->_HeroImageUrl;
	}

	protected function GetHeroImageAvifUrl(): ?string{
		if($this->_HeroImageAvifUrl === null){
			if(file_exists(WEB_ROOT . '/images/covers/' . $this->UrlSafeIdentifier . '-hero.avif')){
				$this->_HeroImageAvifUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-hero.avif';
			}
		}

		return $this->_HeroImageAvifUrl;
	}

	protected function GetHeroImage2xUrl(): string{
		if($this->_HeroImage2xUrl === null){
			$this->_HeroImage2xUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-hero@2x.jpg';
		}

		return $this->_HeroImage2xUrl;
	}

	protected function GetHeroImage2xAvifUrl(): ?string{
		if($this->_HeroImage2xAvifUrl === null){
			if(file_exists(WEB_ROOT . '/images/covers/' . $this->UrlSafeIdentifier . '-hero@2x.avif')){
				$this->_HeroImage2xAvifUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-hero@2x.avif';
			}
		}

		return $this->_HeroImage2xAvifUrl;
	}

	protected function GetCoverImageUrl(): string{
		if($this->_CoverImageUrl === null){
			$this->_CoverImageUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-cover.jpg';
		}

		return $this->_CoverImageUrl;
	}

	protected function GetCoverImageAvifUrl(): ?string{
		if($this->_CoverImageAvifUrl === null){
			if(file_exists(WEB_ROOT . '/images/covers/' . $this->UrlSafeIdentifier . '-cover.avif')){
				$this->_CoverImageAvifUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-cover.avif';
			}
		}

		return $this->_CoverImageAvifUrl;
	}

	protected function GetCoverImage2xUrl(): string{
		if($this->_CoverImage2xUrl === null){
			$this->_CoverImage2xUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-cover@2x.jpg';
		}

		return $this->_CoverImage2xUrl;
	}

	protected function GetCoverImage2xAvifUrl(): ?string{
		if($this->_CoverImage2xAvifUrl === null){
			if(file_exists(WEB_ROOT . '/images/covers/' . $this->UrlSafeIdentifier . '-cover@2x.avif')){
				$this->_CoverImage2xAvifUrl = '/images/covers/' . $this->UrlSafeIdentifier . '-' . $this->GetLatestCommitHash() . '-cover@2x.avif';
			}
		}

		return $this->_CoverImage2xAvifUrl;
	}

	protected function GetReadingEaseDescription(): string{
		if($this->_ReadingEaseDescription === null){
			if($this->ReadingEase > 89){
				$this->_ReadingEaseDescription = 'very easy';
			}
			elseif($this->ReadingEase >= 79 && $this->ReadingEase <= 89){
				$this->_ReadingEaseDescription = 'easy';
			}
			elseif($this->ReadingEase > 69 && $this->ReadingEase <= 79){
				$this->_ReadingEaseDescription = 'fairly easy';
			}
			elseif($this->ReadingEase > 59 && $this->ReadingEase <= 69){
				$this->_ReadingEaseDescription = 'average difficulty';
			}
			elseif($this->ReadingEase > 49 && $this->ReadingEase <= 59){
				$this->_ReadingEaseDescription = 'fairly difficult';
			}
			elseif($this->ReadingEase > 39 && $this->ReadingEase <= 49){
				$this->_ReadingEaseDescription = 'difficult';
			}
			else{
				$this->_ReadingEaseDescription = 'very difficult';
			}
		}

		return $this->_ReadingEaseDescription;
	}

	protected function GetReadingTime(): string{
		if($this->_ReadingTime === null){
			$readingTime = ceil($this->WordCount / AVERAGE_READING_WORDS_PER_MINUTE);
			$this->_ReadingTime = (string)$readingTime;

			if($readingTime < 60){
				$this->_ReadingTime .= ' minute';
				if($readingTime != 1){
					$this->_ReadingTime .= 's';
				}
			}
			else{
				$readingTimeHours = floor($readingTime / 60);
				$readingTimeMinutes = ceil($readingTime % 60);
				$this->_ReadingTime = $readingTimeHours . ' hour';
				if($readingTimeHours != 1){
					$this->_ReadingTime .= 's';
				}

				if($readingTimeMinutes != 0){
					$this->_ReadingTime .= ' ' . $readingTimeMinutes . ' minute';
					if($readingTimeMinutes != 1){
						$this->_ReadingTime .= 's';
					}
				}
			}
		}

		return $this->_ReadingTime;
	}

	protected function GetAuthorsHtml(): string{
		if($this->_AuthorsHtml === null){
			$this->_AuthorsHtml = Ebook::GenerateContributorList($this->Authors, true);
		}

		return $this->_AuthorsHtml;
	}

	protected function GetAuthorsUrl(): string{
		if($this->_AuthorsUrl === null){
			$this->_AuthorsUrl = preg_replace('|url:https://standardebooks.org/ebooks/([^/]+)/.*|ius', '/ebooks/\1', $this->Identifier);
		}

		return $this->_AuthorsUrl;
	}

	protected function GetContributorsHtml(): string{
		if($this->_ContributorsHtml === null){
			$this->_ContributorsHtml = '';
			if(sizeof($this->Contributors) > 0){
				$this->_ContributorsHtml .= ' with ' . Ebook::GenerateContributorList($this->Contributors, false) . ';';
			}

			if(sizeof($this->Translators) > 0){
				$this->_ContributorsHtml .= ' translated by ' . Ebook::GenerateContributorList($this->Translators, false) . ';';
			}

			if(sizeof($this->Illustrators) > 0){
				$this->_ContributorsHtml .= ' illustrated by ' . Ebook::GenerateContributorList($this->Illustrators, false) . ';';
			}

			if(!empty($this->_ContributorsHtml)){
				$this->_ContributorsHtml = ucfirst(rtrim(trim($this->_ContributorsHtml), ';'));

				if(substr(strip_tags($this->_ContributorsHtml), -1) != '.'){
					$this->_ContributorsHtml .= '.';
				}
			}
		}

		return $this->_ContributorsHtml;
	}

	protected function GetTitleWithCreditsHtml(): string{
		if($this->_TitleWithCreditsHtml === null){
			$titleContributors = '';
			if(sizeof($this->Contributors) > 0){
				$titleContributors .= '. With ' . Ebook::GenerateContributorList($this->Contributors, false);
			}

			if(sizeof($this->Translators) > 0){
				$titleContributors .= '. Translated by ' . Ebook::GenerateContributorList($this->Translators, false);
			}

			if(sizeof($this->Illustrators) > 0){
				$titleContributors .= '. Illustrated by ' . Ebook::GenerateContributorList($this->Illustrators, false);
			}

			$this->_TitleWithCreditsHtml = Formatter::EscapeHtml($this->Title) . ', by ' . str_replace('&amp;', '&', $this->AuthorsHtml . $titleContributors);
		}

		return $this->_TitleWithCreditsHtml;
	}

	protected function GetTextUrl(): string{
		if($this->_TextUrl === null){
			$this->_TextUrl = $this->Url . '/text';
		}

		return $this->_TextUrl;
	}

	protected function GetTextSinglePageUrl(): string{
		if($this->_TextSinglePageUrl === null){
			$this->_TextSinglePageUrl = $this->Url . '/text/single-page';
		}

		return $this->_TextSinglePageUrl;
	}

	protected function GetTextSinglePageSizeFormatted(): string{
		if($this->_TextSinglePageSizeFormatted === null){
			$bytes = $this->TextSinglePageByteCount;
			$sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

			$index = 0;
			while($bytes >= 1024 && $index < count($sizes) - 1){
				$bytes /= 1024;
				$index++;
			}

			if($index == 0){
				// No decimal point for smaller than a KB.
				$this->_TextSinglePageSizeFormatted = sprintf("%d %s", $bytes, $sizes[$index]);
			}else{
				$this->_TextSinglePageSizeFormatted = sprintf("%.1f %s", $bytes, $sizes[$index]);
			}
		}

		return $this->_TextSinglePageSizeFormatted;
	}

	protected function GetIndexableText(): string{
		if($this->_IndexableText === null){
			$this->_IndexableText = $this->FullTitle ?? $this->Title;

			$this->_IndexableText .= ' ' . $this->AlternateTitle;

			foreach($this->CollectionMemberships as $collectionMembership){
				$this->_IndexableText .= ' ' . $collectionMembership->Collection->Name;
			}

			foreach($this->Authors as $author){
				$this->_IndexableText .= ' ' . $author->Name;
			}

			foreach($this->Tags as $tag){
				$this->_IndexableText .= ' ' . $tag->Name;
			}

			foreach($this->LocSubjects as $subject){
				$this->_IndexableText .= ' ' . $subject->Name;
			}

			if($this->TocEntries !== null){
				foreach($this->TocEntries as $item){
					$this->_IndexableText .= ' ' . $item;
				}
			}

			// Remove diacritics and non-alphanumeric characters.
			$this->_IndexableText = trim(preg_replace('|[^a-zA-Z0-9 ]|ius', ' ', Formatter::RemoveDiacritics($this->_IndexableText)));
		}

		return $this->_IndexableText;
	}

	/**
	 * Construct an Ebook from a filesystem path.
	 *
	 * @param string $wwwFilesystemPath The valid readable filesytem path where the ebook is served on the web.
	 *
	 * @return Ebook The populated Ebook object.
	 *
	 * @throws Exceptions\EbookNotFoundException
	 * @throws Exceptions\EbookParsingException
	 * @throws Exceptions\InvalidEbookWwwFilesystemPathException
	 * @throws Exceptions\InvalidGitCommitException
	 */
	public static function FromFilesystem(?string $wwwFilesystemPath = null): Ebook{
		if($wwwFilesystemPath === null){
			throw new Exceptions\InvalidEbookWwwFilesystemPathException($wwwFilesystemPath);
		}

		$ebookFromFilesystem = new Ebook();

		// First, construct a source repo path from our WWW filesystem path.
		if(is_dir($wwwFilesystemPath . '/.git')){
			$ebookFromFilesystem->RepoFilesystemPath = $wwwFilesystemPath;
		}
		else{
			$ebookFromFilesystem->RepoFilesystemPath = str_replace(EBOOKS_DIST_PATH, '', $wwwFilesystemPath);
			$ebookFromFilesystem->RepoFilesystemPath = SITE_ROOT . '/ebooks/' . str_replace('/', '_', $ebookFromFilesystem->RepoFilesystemPath) . '.git';
		}

		if(!is_dir($ebookFromFilesystem->RepoFilesystemPath)){ // On dev systems we might not have the bare repos, so make an adjustment.
			try{
				$ebookFromFilesystem->RepoFilesystemPath = preg_replace('/\.git$/ius', '', $ebookFromFilesystem->RepoFilesystemPath);
			}
			catch(\Exception){
				// We may get an exception from preg_replace if the passed repo wwwFilesystemPath contains invalid UTF-8 characters, whichis  a common injection attack vector.
				throw new Exceptions\EbookNotFoundException('Invalid repo filesystem path: ' . $ebookFromFilesystem->RepoFilesystemPath);
			}
		}

		if(!is_dir($wwwFilesystemPath)){
			throw new Exceptions\EbookNotFoundException('Invalid www filesystem path: ' . $wwwFilesystemPath);
		}

		if(!is_dir($ebookFromFilesystem->RepoFilesystemPath)){
			throw new Exceptions\EbookNotFoundException('Invalid repo filesystem path: ' . $ebookFromFilesystem->RepoFilesystemPath);
		}

		if(!is_file($wwwFilesystemPath . '/content.opf')){
			throw new Exceptions\EbookNotFoundException('Invalid content.opf file: ' . $wwwFilesystemPath . '/content.opf');
		}

		$ebookFromFilesystem->WwwFilesystemPath = $wwwFilesystemPath;

		$rawMetadata = file_get_contents($wwwFilesystemPath . '/content.opf');

		// Get the SE identifier.
		preg_match('|<dc:identifier[^>]*?>(.+?)</dc:identifier>|ius', $rawMetadata, $matches);
		if(sizeof($matches) != 2){
			throw new Exceptions\EbookParsingException('Invalid <dc:identifier> element.');
		}
		$ebookFromFilesystem->Identifier = (string)$matches[1];

		try{
			// PHP Safe throws an exception from filesize() if the file doesn't exist, but PHP still emits a warning. So, just silence the warning.
			$ebookFromFilesystem->TextSinglePageByteCount = @filesize($ebookFromFilesystem->WwwFilesystemPath . '/text/single-page.xhtml');
		}
		catch(\Exception){
			// Single page file doesn't exist, just pass.
		}

		// Generate the Kindle cover URL.
		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/*_EBOK_portrait.jpg');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->KindleCoverUrl = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		// Generate the compatible epub URL.
		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/*.epub');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->EpubUrl = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		// Generate the epub URL.
		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/*_advanced.epub');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->AdvancedEpubUrl = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		// Generate the Kepub URL.
		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/*.kepub.epub');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->KepubUrl = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		// Generate the azw3 URL.
		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/*.azw3');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->Azw3Url = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		$tempPath = glob($ebookFromFilesystem->WwwFilesystemPath . '/downloads/cover.jpg');
		if(sizeof($tempPath) > 0){
			$ebookFromFilesystem->DistCoverUrl = $ebookFromFilesystem->Url . '/downloads/' . basename($tempPath[0]);
		}

		// Fill in the short history of this repo.
		try{
			$historyEntries = explode("\n",  shell_exec('cd ' . escapeshellarg($ebookFromFilesystem->RepoFilesystemPath) . ' && git log -n5 --pretty=format:"%ct %H %s"'));

			$gitCommits = [];
			foreach($historyEntries as $logLine){
				$gitCommits[] = GitCommit::FromLogLine($logLine);
			}
			$ebookFromFilesystem->GitCommits = $gitCommits;
		}
		catch(\Safe\Exceptions\ExecException){
			// Pass.
		}

		// Now do some heavy XML lifting!
		try{
			$xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', $rawMetadata));
		}
		catch(\Exception $ex){
			throw new Exceptions\EbookParsingException($ex->getMessage());
		}

		$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

		$ebookFromFilesystem->Title = Ebook::NullIfEmpty($xml->xpath('/package/metadata/dc:title'));
		if($ebookFromFilesystem->Title === null){
			throw new Exceptions\EbookParsingException('Invalid <dc:title> element.');
		}

		$ebookFromFilesystem->Title = str_replace('\'', '’', $ebookFromFilesystem->Title);

		$ebookFromFilesystem->FullTitle = Ebook::NullIfEmpty($xml->xpath('/package/metadata/dc:title[@id="fulltitle"]'));

		$ebookFromFilesystem->AlternateTitle = Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="dcterms:alternate"][@refines="#title"]'));

		$date = $xml->xpath('/package/metadata/dc:date') ?: [];
		if($date !== false && sizeof($date) > 0){
			/** @throws void */
			$ebookFromFilesystem->EbookCreated = new DateTimeImmutable((string)$date[0]);
		}

		$modifiedDate = $xml->xpath('/package/metadata/meta[@property="dcterms:modified"]') ?: [];
		if($modifiedDate !== false && sizeof($modifiedDate) > 0){
			/** @throws void */
			$ebookFromFilesystem->EbookUpdated = new DateTimeImmutable((string)$modifiedDate[0]);
		}

		// Get SE tags.
		$tags = [];
		foreach($xml->xpath('/package/metadata/meta[@property="se:subject"]') ?: [] as $tag){
			$ebookTag = new EbookTag();
			$ebookTag->Name = $tag;
			$tags[] = $ebookTag;
		}
		$ebookFromFilesystem->Tags = $tags;

		$includeToc = sizeof($xml->xpath('/package/metadata/meta[@property="se:is-a-collection"]') ?: []) > 0;

		// Fill the ToC if necessary.
		if($includeToc){
			$tocEntries = [];
			try{
				$tocDom = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($wwwFilesystemPath . '/toc.xhtml')));
			}
			catch(\Exception $ex){
				throw new Exceptions\EbookParsingException($ex->getMessage());
			}
			$tocDom->registerXPathNamespace('epub', 'http://www.idpf.org/2007/ops');
			foreach($tocDom->xpath('/html/body//nav[@epub:type="toc"]//a[not(contains(@epub:type, "z3998:roman")) and not(text() = "Titlepage" or text() = "Imprint" or text() = "Colophon" or text() = "Endnotes" or text() = "Uncopyright") and not(contains(@href, "halftitle"))]') ?: [] as $item){
				$tocEntries[] = (string)$item;
			}
			$ebookFromFilesystem->TocEntries = $tocEntries;
		}

		// Get SE collections.
		$collectionMemberships = [];
		foreach($xml->xpath('/package/metadata/meta[@property="belongs-to-collection"]') ?: [] as $collection){
			$cm = new CollectionMembership();
			$cm->Collection = Collection::FromName($collection);

			$id = $collection->attributes()->id ?? '';
			foreach($xml->xpath('/package/metadata/meta[@refines="#' . $id . '"][@property="group-position"]') ?: [] as $s){
				$cm->SequenceNumber = (int)$s;
			}
			foreach($xml->xpath('/package/metadata/meta[@refines="#' . $id . '"][@property="collection-type"]') ?: [] as $s){
				$cm->Collection->Type = CollectionType::tryFrom((string)$s) ?? CollectionType::Unknown;
			}
			$collectionMemberships[] = $cm;
		}
		$ebookFromFilesystem->CollectionMemberships = $collectionMemberships;

		// Get LoC tags.
		$locSubjects = [];
		foreach($xml->xpath('/package/metadata/dc:subject') ?: [] as $subject){
			$locSubject = new LocSubject();
			$locSubject->Name = $subject;
			$locSubjects[] = $locSubject;
		}
		$ebookFromFilesystem->LocSubjects = $locSubjects;

		// Figure out authors and contributors.
		$authors = [];
		foreach($xml->xpath('/package/metadata/dc:creator') ?: [] as $author){
			$id = '';

			if($author->attributes() !== null){
				$id = $author->attributes()->id;
			}

			$fileAs = null;
			$fileAsElement = $xml->xpath('/package/metadata/meta[@property="file-as"][@refines="#' . $id . '"]') ?: [];
			if($fileAsElement !== false && sizeof($fileAsElement) > 0){
				$fileAs = (string)$fileAsElement[0];
			}
			else{
				$fileAs = (string)$author;
			}

			$authors[] = Contributor::FromProperties(
						(string)$author,
						$fileAs,
						Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:name.person.full-name"][@refines="#' . $id . '"]')),
						Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.encyclopedia.wikipedia"][@refines="#' . $id . '"]')),
						'aut',
						Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.authority.nacoaf"][@refines="#' . $id . '"]'))
					);
		}
		if(sizeof($authors) == 0){
			throw new Exceptions\EbookParsingException('Invalid <dc:creator> element.');
		}

		$ebookFromFilesystem->Authors = $authors;

		$illustrators = [];
		$translators = [];
		$contributors = [];
		foreach($xml->xpath('/package/metadata/dc:contributor') ?: [] as $contributor){
			$id = '';
			if($contributor->attributes() !== null){
				$id = $contributor->attributes()->id;
			}

			foreach($xml->xpath('/package/metadata/meta[ (@property="role" or @property="se:role") and @refines="#' . $id . '"]') ?: [] as $role){
				$c = Contributor::FromProperties(
							(string)$contributor,
							Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="file-as"][@refines="#' . $id . '"]')),
							Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:name.person.full-name"][@refines="#' . $id . '"]')),
							Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.encyclopedia.wikipedia"][@refines="#' . $id . '"]')),
							$role,
							Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.authority.nacoaf"][@refines="#' . $id . '"]'))
						);

				// A display-sequence of 0 indicates that we don't want to process this contributor.
				$displaySequence = Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="display-seq"][@refines="#' . $id . '"]'));
				if($displaySequence !== '0'){
					if($role == 'trl'){
						$translators[] = $c;
					}

					if($role == 'ill'){
						$illustrators[] = $c;
					}

					if($role == 'ctb'){
						$contributors[] = $c;
					}
				}
			}

			// If we added an illustrator who is also the translator, remove the illustrator credit so the name doesn't appear twice.
			foreach($illustrators as $key => $illustrator){
				foreach($translators as $translator){
					if($translator->Name == $illustrator->Name){
						unset($illustrators[$key]);
						break;
					}
				}
			}

		}
		$ebookFromFilesystem->Illustrators = $illustrators;
		$ebookFromFilesystem->Translators = $translators;
		$ebookFromFilesystem->Contributors = $contributors;

		// Some basic data.
		$ebookFromFilesystem->Description = Ebook::NullIfEmpty($xml->xpath('/package/metadata/dc:description'));
		$ebookFromFilesystem->Language = Ebook::NullIfEmpty($xml->xpath('/package/metadata/dc:language'));
		$ebookFromFilesystem->LongDescription = Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:long-description"]'));

		$wordCount = 0;
		$wordCountElement = $xml->xpath('/package/metadata/meta[@property="se:word-count"]');
		if($wordCountElement !== false && sizeof($wordCountElement) > 0){
			$wordCount = (int)$wordCountElement[0];
		}
		$ebookFromFilesystem->WordCount = $wordCount;

		$readingEase = 0;
		$readingEaseElement = $xml->xpath('/package/metadata/meta[@property="se:reading-ease.flesch"]');
		if($readingEaseElement !== false && sizeof($readingEaseElement) > 0){
			$readingEase = (float)$readingEaseElement[0];
		}
		$ebookFromFilesystem->ReadingEase = $readingEase;

		// First the Wikipedia URLs.
		$ebookFromFilesystem->WikipediaUrl = Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.encyclopedia.wikipedia"][not(@refines)]'));

		// Next the page scan source URLs.
		$sources = [];
		foreach($xml->xpath('/package/metadata/dc:source') ?: [] as $element){
			$ebookSource = new EbookSource();
			$ebookSource->Url = (string)$element;
			$ebookSource->Type = EbookSourceType::Other;

			if(mb_stripos($ebookSource->Url, 'gutenberg.org/') !== false){
				$ebookSource->Type = EbookSourceType::ProjectGutenberg;
			}
			elseif(mb_stripos($ebookSource->Url, 'gutenberg.net.au/') !== false){
				$ebookSource->Type = EbookSourceType::ProjectGutenbergAustralia;
			}
			elseif(mb_stripos($ebookSource->Url, 'gutenberg.ca/') !== false){
				$ebookSource->Type = EbookSourceType::ProjectGutenbergCanada;
			}
			elseif(mb_stripos($ebookSource->Url, 'archive.org/details') !== false){
				// `/details` excludes Wayback Machine URLs which may sometimes occur, for example in Lyrical Ballads.
				$ebookSource->Type = EbookSourceType::InternetArchive;
			}
			elseif(mb_stripos($ebookSource->Url, 'hathitrust.org/') !== false){
				$ebookSource->Type = EbookSourceType::HathiTrust;
			}
			elseif(mb_stripos($ebookSource->Url, 'wikisource.org/') !== false){
				$ebookSource->Type = EbookSourceType::Wikisource;
			}
			elseif(mb_stripos($ebookSource->Url, 'books.google.com/') !== false || mb_stripos($ebookSource->Url, 'google.com/books/') !== false){
				$ebookSource->Type = EbookSourceType::GoogleBooks;
			}
			elseif(mb_stripos($ebookSource->Url, 'www.fadedpage.com') !== false){
				$ebookSource->Type = EbookSourceType::FadedPage;
			}

			$sources[] = $ebookSource;
		}
		$ebookFromFilesystem->Sources = $sources;

		// Next the GitHub URLs.
		$ebookFromFilesystem->GitHubUrl = Ebook::NullIfEmpty($xml->xpath('/package/metadata/meta[@property="se:url.vcs.github"][not(@refines)]'));

		return $ebookFromFilesystem;
	}


	// *******
	// METHODS
	// *******

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function Validate(): void{
		$error = new Exceptions\ValidationException();

		if(isset($this->Identifier)){
			$this->Identifier = trim($this->Identifier);

			if($this->Identifier == ''){
				$error->Add(new Exceptions\EbookIdentifierRequiredException());
			}

			if(strlen($this->Identifier) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook Identifier'));
			}
		}
		else{
			$error->Add(new Exceptions\EbookIdentifierRequiredException());
		}

		if(isset($this->WwwFilesystemPath)){
			$this->WwwFilesystemPath = trim($this->WwwFilesystemPath);

			if($this->WwwFilesystemPath == ''){
				$error->Add(new Exceptions\EbookWwwFilesystemPathRequiredException());
			}

			if(strlen($this->WwwFilesystemPath) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook WwwFilesystemPath'));
			}

			if(!is_readable($this->WwwFilesystemPath)){
				$error->Add(new Exceptions\InvalidEbookWwwFilesystemPathException($this->WwwFilesystemPath));
			}
		}
		else{
			$error->Add(new Exceptions\EbookWwwFilesystemPathRequiredException());
		}

		if(isset($this->RepoFilesystemPath)){
			$this->RepoFilesystemPath = trim($this->RepoFilesystemPath);

			if($this->RepoFilesystemPath == ''){
				$error->Add(new Exceptions\EbookRepoFilesystemPathRequiredException());
			}

			if(strlen($this->RepoFilesystemPath) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook RepoFilesystemPath'));
			}

			if(!is_readable($this->RepoFilesystemPath)){
				$error->Add(new Exceptions\InvalidEbookRepoFilesystemPathException($this->RepoFilesystemPath));
			}
		}
		else{
			$error->Add(new Exceptions\EbookRepoFilesystemPathRequiredException());
		}

		$this->KindleCoverUrl = trim($this->KindleCoverUrl ?? '');
		if($this->KindleCoverUrl == ''){
			$this->KindleCoverUrl = null;
		}

		if(isset($this->KindleCoverUrl)){
			if(!preg_match('|/*_EBOK_portrait.jpg$|ius', $this->KindleCoverUrl)){
				$error->Add(new Exceptions\InvalidEbookKindleCoverUrlException('Invalid Ebook KindleCoverUrl: ' . $this->KindleCoverUrl));
			}

			if(strlen($this->KindleCoverUrl) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook KindleCoverUrl'));
			}
		}

		$this->EpubUrl = trim($this->EpubUrl ?? '');
		if($this->EpubUrl == ''){
			$this->EpubUrl = null;
		}

		if(isset($this->EpubUrl)){
			if(!preg_match('|/*.epub$|ius', $this->EpubUrl)){
				$error->Add(new Exceptions\InvalidEbookEpubUrlException('Invalid Ebook EpubUrl: ' . $this->EpubUrl));
			}

			if(strlen($this->EpubUrl) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook EpubUrl'));
			}
		}

		$this->AdvancedEpubUrl = trim($this->AdvancedEpubUrl ?? '');
		if($this->AdvancedEpubUrl == ''){
			$this->AdvancedEpubUrl = null;
		}

		if(isset($this->AdvancedEpubUrl)){
			if(!preg_match('|/*_advanced.epub$|ius', $this->AdvancedEpubUrl)){
				$error->Add(new Exceptions\InvalidEbookAdvancedEpubUrlException('Invalid Ebook AdvancedEpubUrl: ' . $this->AdvancedEpubUrl));
			}

			if(strlen($this->AdvancedEpubUrl) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook AdvancedEpubUrl'));
			}
		}


		$this->KepubUrl = trim($this->KepubUrl ?? '');
		if($this->KepubUrl == ''){
			$this->KepubUrl = null;
		}

		if(isset($this->KepubUrl)){
			if(!preg_match('|/*.kepub.epub$|ius', $this->KepubUrl)){
				$error->Add(new Exceptions\InvalidEbookKepubUrlException('Invalid Ebook KepubUrl: ' . $this->KepubUrl));
			}

			if(strlen($this->KepubUrl) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook KepubUrl'));
			}
		}

		$this->Azw3Url = trim($this->Azw3Url ?? '');
		if($this->Azw3Url == ''){
			$this->Azw3Url = null;
		}

		if(isset($this->Azw3Url)){
			if(!preg_match('|/*.azw3$|ius', $this->Azw3Url)){
				$error->Add(new Exceptions\InvalidEbookAzw3UrlException('Invalid Ebook Azw3Url: ' . $this->Azw3Url));
			}

			if(strlen($this->Azw3Url) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook Azw3Url'));
			}
		}

		$this->DistCoverUrl = trim($this->DistCoverUrl ?? '');
		if($this->DistCoverUrl == ''){
			$this->DistCoverUrl = null;
		}

		if(isset($this->DistCoverUrl)){
			if(!preg_match('|/*cover.jpg$|ius', $this->DistCoverUrl)){
				$error->Add(new Exceptions\InvalidEbookDistCoverUrlException('Invalid Ebook DistCoverUrl: ' . $this->DistCoverUrl));
			}

			if(strlen($this->DistCoverUrl) > EBOOKS_MAX_LONG_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook DistCoverUrl'));
			}
		}

		if(isset($this->Title)){
			$this->Title = trim($this->Title ?? '');

			if($this->Title == ''){
				$error->Add(new Exceptions\EbookTitleRequiredException());
			}

			if(strlen($this->Title) > EBOOKS_MAX_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook Title'));
			}
		}
		else{
			$error->Add(new Exceptions\EbookTitleRequiredException());
		}

		$this->FullTitle = trim($this->FullTitle ?? '');
		if($this->FullTitle == ''){
			$this->FullTitle = null;
		}

		if(isset($this->FullTitle) && strlen($this->FullTitle) > EBOOKS_MAX_STRING_LENGTH){
			$error->Add(new Exceptions\StringTooLongException('Ebook FullTitle'));
		}

		$this->AlternateTitle = trim($this->AlternateTitle ?? '');
		if($this->AlternateTitle == ''){
			$this->AlternateTitle = null;
		}

		if(isset($this->AlternateTitle) && strlen($this->AlternateTitle) > EBOOKS_MAX_STRING_LENGTH){
			$error->Add(new Exceptions\StringTooLongException('Ebook AlternateTitle'));
		}

		if(isset($this->Description)){
			$this->Description = trim($this->Description ?? '');

			if($this->Description == ''){
				$error->Add(new Exceptions\EbookDescriptionRequiredException());
			}
		}
		else{
			$error->Add(new Exceptions\EbookDescriptionRequiredException());
		}

		if(isset($this->LongDescription)){
			$this->LongDescription = trim($this->LongDescription ?? '');

			if($this->LongDescription == ''){
				$error->Add(new Exceptions\EbookLongDescriptionRequiredException());
			}
		}
		else{
			$error->Add(new Exceptions\EbookLongDescriptionRequiredException());
		}

		if(isset($this->Language)){
			$this->Language = trim($this->Language ?? '');

			if($this->Language == ''){
				$error->Add(new Exceptions\EbookLanguageRequiredException());
			}

			if(strlen($this->Language) > 10){
				$error->Add(new Exceptions\StringTooLongException('Ebook Language: ' . $this->Language));
			}
		}
		else{
			$error->Add(new Exceptions\EbookLanguageRequiredException());
		}

		if(isset($this->WordCount)){
			if($this->WordCount <= 0){
				$error->Add(new Exceptions\InvalidEbookWordCountException('Invalid Ebook WordCount: ' . $this->WordCount));
			}
		}
		else{
			$error->Add(new Exceptions\EbookWordCountRequiredException());
		}

		if(isset($this->ReadingEase)){
			// In theory, Flesch reading ease can be negative, but in practice it's positive.
			if($this->ReadingEase <= 0){
				$error->Add(new Exceptions\InvalidEbookReadingEaseException('Invalid Ebook ReadingEase: ' . $this->ReadingEase));
			}
		}
		else{
			$error->Add(new Exceptions\EbookReadingEaseRequiredException());
		}

		$this->GitHubUrl = trim($this->GitHubUrl ?? '');
		if($this->GitHubUrl == ''){
			$this->GitHubUrl = null;
		}

		if(isset($this->GitHubUrl)){
			if(!preg_match('|^https://github.com/standardebooks/\w+|ius', $this->GitHubUrl)){
				$error->Add(new Exceptions\InvalidEbookGitHubUrlException('Invalid Ebook GitHubUrl: ' . $this->GitHubUrl));
			}

			if(strlen($this->GitHubUrl) > EBOOKS_MAX_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook GitHubUrl'));
			}
		}

		$this->WikipediaUrl = trim($this->WikipediaUrl ?? '');
		if($this->WikipediaUrl == ''){
			$this->WikipediaUrl = null;
		}

		if(isset($this->WikipediaUrl)){
			if(!preg_match('|^https://.*wiki.*|ius', $this->WikipediaUrl)){
				$error->Add(new Exceptions\InvalidEbookWikipediaUrlException('Invalid Ebook WikipediaUrl: ' . $this->WikipediaUrl));
			}

			if(strlen($this->WikipediaUrl) > EBOOKS_MAX_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook WikipediaUrl'));
			}
		}

		if(isset($this->EbookCreated)){
			if($this->EbookCreated > NOW){
				$error->Add(new Exceptions\InvalidEbookCreatedDatetimeException($this->EbookCreated));
			}
		}
		else{
			$error->Add(new Exceptions\EbookCreatedDatetimeRequiredException());
		}

		if(isset($this->EbookUpdated)){
			if($this->EbookUpdated > NOW){
				$error->Add(new Exceptions\InvalidEbookUpdatedDatetimeException($this->EbookUpdated));

			}
		}
		else{
			$error->Add(new Exceptions\EbookUpdatedDatetimeRequiredException());
		}

		if(isset($this->TextSinglePageByteCount)){
			if($this->TextSinglePageByteCount <= 0){
				$error->Add(new Exceptions\InvalidEbookTextSinglePageByteCountException('Invalid Ebook TextSinglePageByteCount: ' . $this->TextSinglePageByteCount));
			}
		}
		else{
			$error->Add(new Exceptions\EbookTextSinglePageByteCountRequiredException());
		}

		if(isset($this->IndexableText)){
			$this->IndexableText = trim($this->IndexableText ?? '');

			if($this->IndexableText == ''){
				$error->Add(new Exceptions\EbookIndexableTextRequiredException());
			}
		}
		else{
			$error->Add(new Exceptions\EbookIndexableTextRequiredException());
		}

		if($error->HasExceptions){
			throw $error;
		}
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function CreateOrUpdate(): void{
		try{
			$existingEbook = Ebook::GetByIdentifier($this->Identifier);
			$this->EbookId = $existingEbook->EbookId;
			$this->Save();
		}
		catch(Exceptions\EbookNotFoundException){
			$this->Create();
		}
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function CreateTags(): void{
		$tags = [];
		foreach($this->Tags as $ebookTag){
			$tags[] = $ebookTag->GetByNameOrCreate($ebookTag->Name);
		}
		$this->Tags = $tags;
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function CreateLocSubjects(): void{
		$subjects = [];
		foreach($this->LocSubjects as $locSubject){
			$subjects[] = $locSubject->GetByNameOrCreate($locSubject->Name);
		}
		$this->LocSubjects = $subjects;
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function CreateCollections(): void{
		$collectionMemberships = [];
		foreach($this->CollectionMemberships as $collectionMembership){
			$collection = $collectionMembership->Collection;
			// The updated collection has the CollectionId set for newly-created Collection objects.
			$updatedCollection = $collection->GetByUrlNameOrCreate($collection->UrlName);
			$collectionMembership->Collection = $updatedCollection;
			$collectionMemberships[] = $collectionMembership;
		}
		$this->CollectionMemberships = $collectionMemberships;
	}

	public function GetCollectionPosition(Collection $collection): ?int{
		foreach($this->CollectionMemberships as $cm){
			if($cm->Collection->Name == $collection->Name){
				return $cm->SequenceNumber;
			}
		}

		return null;
	}

	public function GenerateJsonLd(): string{
		$output = new stdClass();
		$output->{'@context'} = 'https://schema.org';
		$output->{'@type'} = 'Book';
		$output->bookFormat = 'EBook';

		$organizationObject = new stdClass();
		$organizationObject->{'@type'} = 'Organization';
		$organizationObject->name = 'Standard Ebooks';
		$organizationObject->logo = 'https://standardebooks.org/images/logo-full.svg';
		$organizationObject->url = 'https://standardebooks.org';
		$output->publisher = $organizationObject;

		$output->name = $this->Title;
		$output->image = SITE_URL . $this->DistCoverUrl;
		$output->thumbnailUrl = SITE_URL . $this->Url . '/downloads/cover-thumbnail.jpg';
		$output->url = SITE_URL . $this->Url;
		$output->{'@id'} = SITE_URL . $this->Url;
		$output->description = $this->Description;
		$output->inLanguage = $this->Language;

		if($this->WikipediaUrl){
			$output->sameAs = $this->WikipediaUrl;
		}

		$output->author = [];

		foreach($this->Authors as $contributor){
			$output->author[] = $this->GenerateContributorJsonLd($contributor);
		}

		$output->encoding = [];

		if($this->EpubUrl){
			$encodingObject = new stdClass();
			$encodingObject->{'@type'} = 'MediaObject';
			$encodingObject->encodingFormat = 'epub';
			$encodingObject->contentUrl = SITE_URL . $this->EpubUrl;
			$output->encoding[] = $encodingObject;
		}

		if($this->KepubUrl){
			$encodingObject = new stdClass();
			$encodingObject->{'@type'} = 'MediaObject';
			$encodingObject->encodingFormat = 'kepub';
			$encodingObject->contentUrl = SITE_URL . $this->KepubUrl;
			$output->encoding[] = $encodingObject;
		}

		if($this->AdvancedEpubUrl){
			$encodingObject = new stdClass();
			$encodingObject->{'@type'} = 'MediaObject';
			$encodingObject->encodingFormat = 'epub';
			$encodingObject->contentUrl = SITE_URL . $this->AdvancedEpubUrl;
			$output->encoding[] = $encodingObject;
		}

		if($this->Azw3Url){
			$encodingObject = new stdClass();
			$encodingObject->{'@type'} = 'MediaObject';
			$encodingObject->encodingFormat = 'azw3';
			$encodingObject->contentUrl = SITE_URL . $this->Azw3Url;
			$output->encoding[] = $encodingObject;
		}

		if(sizeof($this->Translators) > 0){
			$output->translator = [];
			foreach($this->Translators as $contributor){
				$output->translator[] = $this->GenerateContributorJsonLd($contributor);
			}
		}

		if(sizeof($this->Illustrators) > 0){
			$output->illustrator = [];
			foreach($this->Illustrators as $contributor){
				$output->illustrator[] = $this->GenerateContributorJsonLd($contributor);
			}
		}

		return json_encode($output, JSON_PRETTY_PRINT);
	}

	private function GenerateContributorJsonLd(Contributor $contributor): stdClass{
		$object = new stdClass();
		$object->{'@type'} = 'Person';
		$object->name = $contributor->Name;

		if($contributor->WikipediaUrl){
			$object->sameAs = $contributor->WikipediaUrl;
		}

		if($contributor->FullName){
			$object->alternateName = $contributor->FullName;
		}

		return $object;
	}

	/**
	 * @param array<Contributor> $contributors
	 * @param bool $includeRdfa
	 */
	private static function GenerateContributorList(array $contributors, bool $includeRdfa): string{
		$string = '';
		$i = 0;

		foreach($contributors as $contributor){
			$role = 'schema:contributor';
			switch($contributor->MarcRole){
				case 'trl':
					$role = 'schema:translator';
					break;
				case 'ill':
					$role = 'schema:illustrator';
					break;
			}

			if($contributor->WikipediaUrl){
				if($includeRdfa){
					$string .= '<a property="' . $role . '" typeof="schema:Person" href="' . Formatter::EscapeHtml($contributor->WikipediaUrl) .'"><span property="schema:name">' . Formatter::EscapeHtml($contributor->Name) . '</span>';

					if($contributor->NacoafUrl){
						$string .= '<meta property="schema:sameAs" content="' . Formatter::EscapeHtml($contributor->NacoafUrl) . '"/>';
					}
				}
				else{
					$string .= '<a href="' . Formatter::EscapeHtml($contributor->WikipediaUrl) .'">' . Formatter::EscapeHtml($contributor->Name);
				}

				$string .= '</a>';
			}
			else{
				if($includeRdfa){
					$string .= '<span property="' . $role . '" typeof="schema:Person"><span property="schema:name">' . Formatter::EscapeHtml($contributor->Name) . '</span>';

					if($contributor->NacoafUrl){
						$string .= '<meta property="schema:sameAs" content="' . Formatter::EscapeHtml($contributor->NacoafUrl) . '"/>';
					}

					$string .= '</span>';
				}
				else{
					$string .= Formatter::EscapeHtml($contributor->Name);
				}
			}

			if($i == sizeof($contributors) - 2 && sizeof($contributors) > 2){
				$string .= ', and ';
			}
			elseif($i == sizeof($contributors) - 2){
				$string .= ' and ';
			}
			elseif($i != sizeof($contributors) - 1){
				$string .= ', ';
			}

			$i++;
		}

		return $string;
	}

	public function GenerateContributorsRdfa(): string{
		$string = '';
		$i = 0;

		foreach($this->Translators as $contributor){
			$role = 'schema:contributor';
			switch($contributor->MarcRole){
				case 'trl':
					$role = 'schema:translator';
					break;
				case 'ill':
					$role = 'schema:illustrator';
					break;
			}

			if($contributor->WikipediaUrl){
				$string .= '<div property="' . $role . '" typeof="schema:Person" resource="/contributors/' . Formatter::MakeUrlSafe($contributor->Name) .'">' . "\n";
			}
			else{
				$string .= '<div property="' . $role . '" typeof="schema:Person">' . "\n";
			}

			$string .= '<meta property="schema:name" content="' . Formatter::EscapeHtml($contributor->Name) . '"/>' . "\n";

			if($contributor->WikipediaUrl){
				$string .= '<meta property="schema:sameAs" content="' . Formatter::EscapeHtml($contributor->WikipediaUrl) . '"/>' . "\n";
			}

			if($contributor->NacoafUrl){
				$string .= '<meta property="schema:sameAs" content="' . Formatter::EscapeHtml($contributor->NacoafUrl) . '"/>' . "\n";
			}

			$string .= '</div>';

			$i++;
		}

		return $string;
	}


	/**
	 * @param array<SimpleXMLElement>|false|null $elements
	 */
	private static function NullIfEmpty($elements): ?string{
		if($elements === false){
			return null;
		}

		// Helper function when getting values from SimpleXml.
		// Checks if the result is set, and returns the value if so; if the value is the empty string, return null.
		if(isset($elements[0])){
			$str = (string)$elements[0];
			if($str !== ''){
				return $str;
			}
		}

		return null;
	}

	// ***********
	// ORM METHODS
	// ***********

	/**
	 * @throws Exceptions\EbookNotFoundException
	 */
	public static function GetByIdentifier(?string $identifier): Ebook{
		if($identifier === null){
			throw new Exceptions\EbookNotFoundException('Invalid identifier: ' . $identifier);
		}

		$result = Db::Query('
				SELECT *
				from Ebooks
				where Identifier = ?
			', [$identifier], Ebook::class);

		if(sizeof($result) == 0){
			throw new Exceptions\EbookNotFoundException('Invalid identifier: ' . $identifier);
		}

		return $result[0];
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function Create(): void{
		$this->Validate();

		$this->CreateTags();
		$this->CreateLocSubjects();
		$this->CreateCollections();

		Db::Query('
			INSERT into Ebooks (Identifier, WwwFilesystemPath, RepoFilesystemPath, KindleCoverUrl, EpubUrl,
				AdvancedEpubUrl, KepubUrl, Azw3Url, DistCoverUrl, Title, FullTitle, AlternateTitle,
				Description, LongDescription, Language, WordCount, ReadingEase, GitHubUrl, WikipediaUrl,
				EbookCreated, EbookUpdated, TextSinglePageByteCount, IndexableText)
			values (?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?)
		', [$this->Identifier, $this->WwwFilesystemPath, $this->RepoFilesystemPath, $this->KindleCoverUrl, $this->EpubUrl,
				$this->AdvancedEpubUrl, $this->KepubUrl, $this->Azw3Url, $this->DistCoverUrl, $this->Title,
				$this->FullTitle, $this->AlternateTitle, $this->Description, $this->LongDescription,
				$this->Language, $this->WordCount, $this->ReadingEase, $this->GitHubUrl, $this->WikipediaUrl,
				$this->EbookCreated, $this->EbookUpdated, $this->TextSinglePageByteCount, $this->IndexableText]);

		$this->EbookId = Db::GetLastInsertedId();

		$this->AddTags();
		$this->AddLocSubjects();
		$this->AddCollectionMemberships();
		$this->AddGitCommits();
		$this->AddSources();
		$this->AddContributors();
		$this->AddTocEntries();
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function Save(): void{
		$this->Validate();

		$this->CreateTags();
		$this->CreateLocSubjects();
		$this->CreateCollections();

		Db::Query('
			UPDATE Ebooks
			set
			Identifier = ?,
			WwwFilesystemPath = ?,
			RepoFilesystemPath = ?,
			KindleCoverUrl = ?,
			EpubUrl = ?,
			AdvancedEpubUrl = ?,
			KepubUrl = ?,
			Azw3Url = ?,
			DistCoverUrl = ?,
			Title = ?,
			FullTitle = ?,
			AlternateTitle = ?,
			Description = ?,
			LongDescription = ?,
			Language = ?,
			WordCount = ?,
			ReadingEase = ?,
			GitHubUrl = ?,
			WikipediaUrl = ?,
			EbookCreated = ?,
			EbookUpdated = ?,
			TextSinglePageByteCount = ?,
			IndexableText = ?
			where
			EbookId = ?
		', [$this->Identifier, $this->WwwFilesystemPath, $this->RepoFilesystemPath, $this->KindleCoverUrl, $this->EpubUrl,
				$this->AdvancedEpubUrl, $this->KepubUrl, $this->Azw3Url, $this->DistCoverUrl, $this->Title,
				$this->FullTitle, $this->AlternateTitle, $this->Description, $this->LongDescription,
				$this->Language, $this->WordCount, $this->ReadingEase, $this->GitHubUrl, $this->WikipediaUrl,
				$this->EbookCreated, $this->EbookUpdated, $this->TextSinglePageByteCount, $this->IndexableText,
				$this->EbookId]);

		$this->RemoveTags();
		$this->AddTags();

		$this->RemoveLocSubjects();
		$this->AddLocSubjects();

		$this->RemoveCollectionMemberships();
		$this->AddCollectionMemberships();

		$this->RemoveGitCommits();
		$this->AddGitCommits();

		$this->RemoveSources();
		$this->AddSources();

		$this->RemoveContributors();
		$this->AddContributors();

		$this->RemoveTocEntries();
		$this->AddTocEntries();
	}

	private function RemoveTags(): void{
		Db::Query('
			DELETE from EbookTags
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	private function AddTags(): void{
		foreach($this->Tags as $sortOrder => $tag){
			try{
				Db::Query('
					INSERT into EbookTags (EbookId, TagId, SortOrder)
					values (?,
						?,
						?)
				', [$this->EbookId, $tag->TagId, $sortOrder]);
			}
			catch(Exceptions\DuplicateDatabaseKeyException){
				// The Ebook already has the Tag, which is fine.
			}
		}
	}

	private function RemoveLocSubjects(): void{
		Db::Query('
			DELETE from EbookLocSubjects
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	private function AddLocSubjects(): void{
		foreach($this->LocSubjects as $sortOrder => $locSubject){
			try{
				Db::Query('
					INSERT into EbookLocSubjects (EbookId, LocSubjectId, SortOrder)
					values (?,
						?,
						?)
				', [$this->EbookId, $locSubject->LocSubjectId, $sortOrder]);
			}
			catch(Exceptions\DuplicateDatabaseKeyException){
				// The Ebook already has the LocSubject, which is fine.
			}
		}
	}

	private function RemoveCollectionMemberships(): void{
		Db::Query('
			DELETE from CollectionEbooks
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	private function AddCollectionMemberships(): void{
		foreach($this->CollectionMemberships as $sortOrder => $collectionMembership){
			$collectionMembership->EbookId = $this->EbookId;
			$collectionMembership->CollectionId = $collectionMembership->Collection->CollectionId;
			$collectionMembership->SortOrder = $sortOrder;

			try{
				Db::Query('
					INSERT into CollectionEbooks (EbookId, CollectionId, SequenceNumber, SortOrder)
					values (?,
						?,
						?,
						?)
				', [$collectionMembership->EbookId, $collectionMembership->CollectionId, $collectionMembership->SequenceNumber,
						$collectionMembership->SortOrder]);
			}
			catch(Exceptions\DuplicateDatabaseKeyException){
				// The Ebook is already a member of this Collection.
			}
		}
	}

	private function RemoveGitCommits(): void{
		Db::Query('
			DELETE from GitCommits
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function AddGitCommits(): void{
		foreach($this->GitCommits as $commit){
			$commit->EbookId = $this->EbookId;
			$commit->Create();
		}
	}

	private function RemoveSources(): void{
		Db::Query('
			DELETE from EbookSources
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function AddSources(): void{
		foreach($this->Sources as $sortOrder => $source){
			$source->EbookId = $this->EbookId;
			$source->SortOrder = $sortOrder;
			$source->Create();
		}
	}

	private function RemoveContributors(): void{
		Db::Query('
			DELETE from Contributors
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	private function AddContributors(): void{
		$allContributors = array_merge($this->Authors, $this->Illustrators, $this->Translators, $this->Contributors);
		foreach($allContributors as $sortOrder => $contributor){
			$contributor->EbookId = $this->EbookId;
			$contributor->SortOrder = $sortOrder;
			$contributor->Create();
		}
	}

	private function RemoveTocEntries(): void{
		Db::Query('
			DELETE from TocEntries
			where EbookId = ?
		', [$this->EbookId]
		);
	}

	private function AddTocEntries(): void{
		if($this->TocEntries !== null){
			foreach($this->TocEntries as $sortOrder => $tocEntry){
				Db::Query('
					INSERT into TocEntries (EbookId, TocEntry, SortOrder)
					values (?,
						?,
						?)
				', [$this->EbookId, $tocEntry, $sortOrder]);
			}
		}
	}
}
